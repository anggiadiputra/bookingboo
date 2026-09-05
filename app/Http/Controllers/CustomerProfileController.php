<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerProfileController extends Controller
{
    /**
     * Tampilkan form edit profil customer.
     */
    public function edit(Request $request): View
    {
        $customer = $request->user()->customer;

        if (! $customer) {
            abort(404, 'Profil customer tidak ditemukan.');
        }

        return view('customer.profile', compact('customer'));
    }

    /**
     * Simpan perubahan profil customer.
     */
    public function update(Request $request): RedirectResponse
    {
        $customer = $request->user()->customer;

        if (! $customer) {
            abort(404, 'Profil customer tidak ditemukan.');
        }

        $validated = $request->validate([
            'patient_name' => ['nullable', 'string', 'max:255'],
            'patient_birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'patient_gender' => ['nullable', 'in:male,female'],
            'patient_blood_type' => ['nullable', 'in:A,B,AB,O'],
            'patient_allergies' => ['nullable', 'string', 'max:1000'],
            'patient_condition' => ['nullable', 'string', 'max:2000'],
            'patient_weight_kg' => ['nullable', 'numeric', 'min:0.5', 'max:400'],
            'patient_height_cm' => ['nullable', 'numeric', 'min:30', 'max:250'],
            'address' => ['nullable', 'string', 'max:255'],
            'patient_needs' => ['nullable', 'string', 'max:2000'],
            'emergency_contact' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
        ]);

        // Nama pasien default = nama pemesan, agar profil tidak pernah kosong.
        if (blank($validated['patient_name'] ?? null)) {
            $validated['patient_name'] = $customer->user->name;
        }

        $customer->update($validated);

        return back()->with('status', 'Profil customer berhasil diperbarui.');
    }
}
