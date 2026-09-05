<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Pasien / keluarga pasien yang memakai layanan pendampingan.
 */
class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Budi Santoso',
                'email' => 'customer@bookingboo.test',
                'phone' => '081234567890',
                'patient_name' => 'Sukirman Santoso',
                'patient_birth_date' => '1952-03-14',
                'patient_gender' => 'male',
                'patient_blood_type' => 'A',
                'patient_allergies' => 'Penisilin, seafood',
                'patient_condition' => 'Hipertensi & diabetes tipe 2, riwayat stroke ringan 2023. Butuh pengawasan minum obat dan bantuan mobilitas.',
                'patient_weight_kg' => 68.5,
                'patient_height_cm' => 168,
                'address' => 'Jl. Melati No. 12, Tebet, Jakarta Selatan',
                'patient_needs' => 'Pendampingan kontrol dokter penyakit dalam untuk ayah (74 thn), kursi roda, pengawasan minum obat rutin.',
                'emergency_contact' => 'Siti Rahayu (Istri)',
                'emergency_contact_phone' => '081234567891',
            ],
            [
                'name' => 'Anisa Maharani',
                'email' => 'anisa@bookingboo.test',
                'phone' => '081298765432',
                'patient_name' => 'Ratna Wulandari',
                'patient_birth_date' => '1968-07-02',
                'patient_gender' => 'female',
                'patient_blood_type' => 'O',
                'patient_allergies' => null,
                'patient_condition' => 'Pasca stroke ringan, fisioterapi rutin untuk pemulihan gerak kaki kanan.',
                'patient_weight_kg' => 58,
                'patient_height_cm' => 155,
                'address' => 'Apartemen Mediterania Tower B No. 15, Jakarta Barat',
                'patient_needs' => 'Fisioterapi pasca stroke ringan untuk ibu (58 thn), latihan jalan mandiri 2x per minggu.',
                'emergency_contact' => 'Herman (Anak)',
                'emergency_contact_phone' => '081298765433',
            ],
            [
                'name' => 'Dimas Darmawan',
                'email' => 'dimas@bookingboo.test',
                'phone' => '081377712345',
                'patient_name' => 'Dimas Darmawan',
                'patient_birth_date' => '1990-11-25',
                'patient_gender' => 'male',
                'patient_blood_type' => 'B',
                'patient_allergies' => 'Lateks',
                'patient_condition' => 'Gagal ginjal kronis, menjalani hemodialisis rutin.',
                'patient_weight_kg' => 71,
                'patient_height_cm' => 172,
                'address' => 'Perumahan Griya Asri Blok C2 No. 9, Bekasi Timur',
                'patient_needs' => 'Pendampingan cuci darah rutin 2x seminggu di RS, antar-jemput dari rumah.',
                'emergency_contact' => 'Ratna Darmawan (Ibu)',
                'emergency_contact_phone' => '081377712346',
            ],
        ];

        foreach ($users as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'role' => User::ROLE_CUSTOMER,
                    'status' => User::STATUS_ACTIVE,
                    'email_verified_at' => now(),
                    'password' => 'password',
                ],
            );

            Customer::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'patient_name' => $data['patient_name'] ?? $data['name'],
                    'patient_birth_date' => $data['patient_birth_date'] ?? null,
                    'patient_gender' => $data['patient_gender'] ?? null,
                    'patient_blood_type' => $data['patient_blood_type'] ?? null,
                    'patient_allergies' => $data['patient_allergies'] ?? null,
                    'patient_condition' => $data['patient_condition'] ?? null,
                    'patient_weight_kg' => $data['patient_weight_kg'] ?? null,
                    'patient_height_cm' => $data['patient_height_cm'] ?? null,
                    'address' => $data['address'],
                    'patient_needs' => $data['patient_needs'],
                    'emergency_contact' => $data['emergency_contact'],
                    'emergency_contact_phone' => $data['emergency_contact_phone'],
                ],
            );
        }
    }
}
