<?php

namespace App\Http\Controllers;

use App\Models\Caregiver;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class CaregiverPublicController extends Controller
{
    /**
     * Daftar caregiver terverifikasi dengan pencarian dan filter.
     * Mendukung GPS: query params lat/lng memungkinkan sort "terdekat"
     * (jarak Haversine dihitung di PHP — aman untuk SQLite).
     */
    public function index(Request $request): View
    {
        $query = Caregiver::query()
            ->where('verification_status', Caregiver::VERIFICATION_VERIFIED)
            ->whereHas('user', fn ($q) => $q->where('status', 'active'))
            ->with('user');

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"))
                    ->orWhere('skills', 'like', "%{$search}%")
                    ->orWhere('bio', 'like', "%{$search}%");
            });
        }

        if ($area = trim((string) $request->query('area'))) {
            $query->where('service_area', 'like', "%{$area}%");
        }

        if ($minRate = $request->query('min_rate')) {
            $query->where('hourly_rate', '>=', (float) $minRate);
        }

        if ($maxRate = $request->query('max_rate')) {
            $query->where('hourly_rate', '<=', (float) $maxRate);
        }

        $sort = $request->query('sort', 'rating');
        $coords = $this->resolveCoords($request);

        if ($sort === 'distance' && $coords !== null) {
            // Sort jarak dihitung PHP-side (SQLite tidak punya fungsi trigonometri),
            // jadi ambil semua hasil filter dulu lalu paginate manual.
            // Caregiver tanpa koordinat tetap tampil di urutan terakhir.
            $matched = $query->get()
                ->each(fn (Caregiver $c) => $c->setAttribute(
                    'distance_km',
                    $c->latitude !== null ? $c->distanceFromKm($coords[0], $coords[1]) : null
                ))
                ->sortBy(fn (Caregiver $c) => $c->distance_km ?? PHP_FLOAT_MAX)
                ->values();

            $page = max(1, (int) $request->query('page', 1));
            $perPage = 12;
            $caregivers = new LengthAwarePaginator(
                $matched->forPage($page, $perPage),
                $matched->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            match ($sort) {
                'rate_asc' => $query->orderBy('hourly_rate'),
                'rate_desc' => $query->orderByDesc('hourly_rate'),
                default => $query->orderByDesc('rating'),
            };

            $caregivers = $query->paginate(12)->withQueryString();

            if ($coords !== null) {
                // Jarak tetap dilampirkan agar kartu bisa menampilkan "x km"
                // meskipun urutan default (rating) dipakai.
                $caregivers->getCollection()->each(
                    fn (Caregiver $c) => $c->setAttribute(
                        'distance_km',
                        $c->latitude !== null ? $c->distanceFromKm($coords[0], $coords[1]) : null
                    )
                );
            }
        }

        return view('caregivers.index', compact('caregivers'));
    }

    /**
     * Ambil koordinat GPS dari query params; null jika tidak ada atau tidak valid.
     *
     * @return array{0: float, 1: float}|null [lat, lng]
     */
    private function resolveCoords(Request $request): ?array
    {
        if ($request->query('lat') === null || $request->query('lng') === null) {
            return null;
        }

        $lat = filter_var($request->query('lat'), FILTER_VALIDATE_FLOAT);
        $lng = filter_var($request->query('lng'), FILTER_VALIDATE_FLOAT);

        if ($lat === false || $lng === false || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return null;
        }

        return [(float) $lat, (float) $lng];
    }

    /**
     * Tampilkan profil publik caregiver beserta rating dan review.
     */
    public function show(Caregiver $caregiver): View
    {
        // Hanya caregiver terverifikasi yang tampil di publik
        if ($caregiver->verification_status !== 'verified') {
            abort(404);
        }

        // Review publik = review dari customer ke caregiver (visibility public, belum disembunyikan)
        $reviews = Review::where('reviewee_id', $caregiver->user_id)
            ->where('visibility', 'public')
            ->where('status', Review::STATUS_PUBLISHED)
            ->with('reviewer')
            ->latest()
            ->paginate(10);

        $rating = $caregiver->rating;

        return view('caregiver.public', compact('caregiver', 'reviews', 'rating'));
    }
}
