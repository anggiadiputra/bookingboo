<?php

namespace App\Http\Controllers;

use App\Models\Caregiver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CaregiverProfileController extends Controller
{
    /**
     * Tampilkan halaman edit profil caregiver.
     */
    public function edit(Request $request): View
    {
        $caregiver = $request->user()->caregiver;

        if (! $caregiver) {
            abort(404, 'Profil caregiver tidak ditemukan.');
        }

        return view('caregiver.profile', compact('caregiver'));
    }

    /**
     * Simpan perubahan profil caregiver.
     */
    public function update(Request $request): RedirectResponse
    {
        $caregiver = $request->user()->caregiver;

        if (! $caregiver) {
            abort(404, 'Profil caregiver tidak ditemukan.');
        }

        $validated = $request->validate([
            'skills' => ['nullable', 'string', 'max:1000'],
            'service_area' => ['nullable', 'string', 'max:255'],
            'hourly_rate' => ['required', 'numeric', 'min:0'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('photo')) {
            // Hapus foto lama jika ada
            if ($caregiver->photo) {
                Storage::disk('public')->delete($caregiver->photo);
            }

            $validated['photo'] = $request->file('photo')->store('caregiver-photos', 'public');
        }

        $caregiver->update($validated);

        return back()->with('status', 'Profil caregiver berhasil diperbarui.');
    }
}
