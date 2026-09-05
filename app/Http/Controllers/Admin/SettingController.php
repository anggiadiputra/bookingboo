<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Panel Pengaturan platform: kontak & bantuan, payment gateway, komisi & refund.
 * Nilai disimpan sebagai Setting (key-value) dan meng-override config saat boot.
 */
class SettingController extends Controller
{
    public function __construct(private AuditLogService $audit) {}

    /**
     * Tampilkan halaman pengaturan.
     */
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'general');

        abort_unless(in_array($tab, ['general', 'payment', 'commission', 'security'], true), 404);

        $keyed = Setting::allKeyed();

        $general = [
            'hotline' => $keyed['support.hotline'] ?? '',
            'whatsapp' => $keyed['support.whatsapp'] ?? '',
            'email' => $keyed['support.email'] ?? '',
            'hours' => $keyed['support.hours'] ?? '',
        ];

        $payment = [
            'server_key' => $keyed['midtrans.server_key'] ?? '',
            'client_key' => $keyed['midtrans.client_key'] ?? '',
            'is_production' => (bool) ($keyed['midtrans.is_production'] ?? false),
            'enabled' => (bool) config('midtrans.enabled'),
        ];

        $security = [
            'enabled' => (bool) ($keyed['turnstile.enabled'] ?? config('turnstile.enabled')),
            'site_key' => $keyed['turnstile.site_key'] ?? (string) config('turnstile.site_key', ''),
            'secret_key' => $keyed['turnstile.secret_key'] ?? (string) config('turnstile.secret_key', ''),
        ];

        $commission = [
            'platform_percent' => $keyed['booking.commission.platform_percent'] ?? (string) config('booking.commission.platform_percent'),
            'default_refund_percent' => $keyed['booking.cancellation.default_refund_percent'] ?? (string) config('booking.cancellation.default_refund_percent'),
            'caregiver_refund_percent' => $keyed['booking.cancellation.caregiver_refund_percent'] ?? (string) config('booking.cancellation.caregiver_refund_percent'),
            'grace_hours' => $keyed['booking.checkin.grace_hours'] ?? (string) config('booking.checkin.grace_hours'),
            'tiers' => $this->tierRows($keyed),
        ];

        return view('admin.settings.index', compact('tab', 'general', 'payment', 'security', 'commission'));
    }

    /**
     * Simpan perubahan dari tab yang aktif.
     */
    public function update(Request $request): RedirectResponse
    {
        $tab = $request->input('tab', 'general');

        $validated = match ($tab) {
            'general' => $request->validate([
                'hotline' => ['nullable', 'string', 'max:30'],
                'whatsapp' => ['nullable', 'string', 'max:30'],
                'email' => ['nullable', 'email', 'max:255'],
                'hours' => ['nullable', 'string', 'max:255'],
            ]),
            'payment' => $request->validate([
                'server_key' => ['nullable', 'string', 'max:255'],
                'client_key' => ['nullable', 'string', 'max:255'],
                'is_production' => ['nullable', 'in:0,1'],
            ]),
            'security' => $request->validate([
                'enabled' => ['nullable', 'in:0,1'],
                'site_key' => ['nullable', 'string', 'max:255'],
                'secret_key' => ['nullable', 'string', 'max:255'],
            ]),
            'commission' => $request->validate([
                'platform_percent' => ['required', 'integer', 'min:0', 'max:100'],
                'default_refund_percent' => ['required', 'integer', 'min:0', 'max:100'],
                'caregiver_refund_percent' => ['required', 'integer', 'min:0', 'max:100'],
                'grace_hours' => ['required', 'integer', 'min:0', 'max:72'],
                'tiers' => ['required', 'array', 'min:1'],
                'tiers.*.hours' => ['required', 'integer', 'min:0'],
                'tiers.*.percent' => ['required', 'integer', 'min:0', 'max:100'],
            ]),
            default => abort(404),
        };

        $this->persist($tab, $validated, $request);

        return redirect()->route('admin.settings.index', ['tab' => $tab])
            ->with('status', 'Pengaturan berhasil disimpan.');
    }

    /**
     * Simpan nilai yang valid ke tabel settings sekaligus catat audit log.
     *
     * @param  array<string, mixed>  $validated
     */
    private function persist(string $tab, array $validated, Request $request): void
    {
        DB::transaction(function () use ($tab, $validated, $request) {
            $changes = [];

            if ($tab === 'general') {
                $map = [
                    'support.hotline' => $validated['hotline'] ?? '',
                    'support.whatsapp' => $validated['whatsapp'] ?? '',
                    'support.email' => $validated['email'] ?? '',
                    'support.hours' => $validated['hours'] ?? '',
                ];
            } elseif ($tab === 'payment') {
                $map = [
                    'midtrans.server_key' => $validated['server_key'] ?? '',
                    'midtrans.client_key' => $validated['client_key'] ?? '',
                    'midtrans.is_production' => isset($validated['is_production']) && (int) $validated['is_production'] === 1 ? '1' : '0',
                ];
            } elseif ($tab === 'security') {
                $map = [
                    'turnstile.enabled' => isset($validated['enabled']) && (int) $validated['enabled'] === 1 ? '1' : '0',
                    'turnstile.site_key' => $validated['site_key'] ?? '',
                    'turnstile.secret_key' => $validated['secret_key'] ?? '',
                ];
            } else {
                $map = [
                    'booking.commission.platform_percent' => (string) $validated['platform_percent'],
                    'booking.cancellation.default_refund_percent' => (string) $validated['default_refund_percent'],
                    'booking.cancellation.caregiver_refund_percent' => (string) $validated['caregiver_refund_percent'],
                    'booking.checkin.grace_hours' => (string) $validated['grace_hours'],
                    'booking.cancellation.customer_refund_tiers' => json_encode($this->normalizeTiers($validated['tiers']), JSON_THROW_ON_ERROR),
                ];
            }

            foreach ($map as $key => $value) {
                Setting::updateOrCreate(['key' => $key], ['value' => $value === '' ? null : $value]);
                $changes[] = $key;
            }

            $this->audit->log(
                $request->user(),
                'settings.updated',
                'Memperbarui pengaturan: '.implode(', ', $changes).'.',
                null,
                ['tab' => $tab, 'keys' => $changes],
            );
        });
    }

    /**
     * Baris tier refund (jam -> persen) untuk form; default dari config.
     *
     * @param  array<string, string|null>  $keyed
     * @return array<int, array{hours: int, percent: int}>
     */
    private function tierRows(array $keyed): array
    {
        $raw = $keyed['booking.cancellation.customer_refund_tiers'] ?? null;

        if ($raw) {
            $decoded = json_decode((string) $raw, true);

            if (is_array($decoded)) {
                return collect($decoded)
                    ->map(fn ($tier) => [
                        'hours' => (int) ($tier['hours'] ?? 0),
                        'percent' => (int) ($tier['percent'] ?? 0),
                    ])
                    ->all();
            }
        }

        return collect(config('booking.cancellation.customer_refund_tiers', []))
            ->map(fn ($tier) => ['hours' => (int) $tier['hours'], 'percent' => (int) $tier['percent']])
            ->all();
    }

    /**
     * Urutkan tier dari ambang jam terbesar dan pastikan berbentuk [hours, percent].
     *
     * @param  array<int, array{hours?: int|string, percent?: int|string}>  $tiers
     * @return array<int, array{hours: int, percent: int}>
     */
    private function normalizeTiers(array $tiers): array
    {
        return collect($tiers)
            ->map(fn ($tier) => [
                'hours' => (int) ($tier['hours'] ?? 0),
                'percent' => (int) ($tier['percent'] ?? 0),
            ])
            ->sortByDesc('hours')
            ->values()
            ->all();
    }
}
