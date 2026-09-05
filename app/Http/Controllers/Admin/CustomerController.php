<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\View\View;

/**
 * Detail customer (pasien) untuk staf: support, finance, dan admin.
 * Menampilkan info pasien lengkap + riwayat booking untuk kebutuhan
 * penanganan komplain / sengketa / verifikasi.
 */
class CustomerController extends Controller
{
    public function show(Customer $customer): View
    {
        $customer->load([
            'user',
            'bookings' => fn ($q) => $q->with(['caregiver.user'])->latest()->limit(10),
        ]);

        return view('admin.customers.show', compact('customer'));
    }
}
