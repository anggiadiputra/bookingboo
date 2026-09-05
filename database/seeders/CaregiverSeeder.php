<?php

namespace Database\Seeders;

use App\Models\Caregiver;
use App\Models\CaregiverDocument;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Mitra caregiver dengan berbagai status verifikasi:
 * verified (siap dibooking), pending (menunggu review dokumen), rejected.
 */
class CaregiverSeeder extends Seeder
{
    public function run(): void
    {
        $caregivers = [
            [
                'user' => [
                    'name' => 'Sari Wulandari, S.Kep',
                    'email' => 'caregiver@bookingboo.test',
                    'phone' => '081355000001',
                    'email_verified_at' => now(),
                ],
                'profile' => [
                    'skills' => 'Perawatan lansia, pendampingan kontrol, perawatan luka, infus',
                    'service_area' => 'Jakarta Selatan, Jakarta Pusat',
                    'latitude' => -6.2446,
                    'longitude' => 106.8006,
                    'hourly_rate' => 50000,
                    'rating' => 4.8,
                    'verification_status' => Caregiver::VERIFICATION_VERIFIED,
                    'bio' => 'Perawat lulusan Ners berpengalaman 5 tahun di RS swasta ternama. Terbiasa merawat lansia dengan demensia dan pendampingan cuci darah.',
                ],
                'documents' => [
                    ['type' => CaregiverDocument::TYPE_KTP, 'status' => CaregiverDocument::STATUS_APPROVED],
                    ['type' => CaregiverDocument::TYPE_STR, 'status' => CaregiverDocument::STATUS_APPROVED],
                ],
            ],
            [
                'user' => [
                    'name' => 'Rian Pratama, S.Kep',
                    'email' => 'rian@bookingboo.test',
                    'phone' => '081355000002',
                    'email_verified_at' => now(),
                ],
                'profile' => [
                    'skills' => 'Pasca Operasi, Rumah Sakit, Rawat Luka Diabetes, Kateter',
                    'service_area' => 'Jakarta Selatan, Jakarta Timur, Depok',
                    'latitude' => -6.2754,
                    'longitude' => 106.8841,
                    'hourly_rate' => 65000,
                    'rating' => 4.9,
                    'verification_status' => Caregiver::VERIFICATION_VERIFIED,
                    'bio' => 'Perawat spesialis bedah dan rawat luka modern (moist wound healing). Siap siaga mendampingi rawat inap 24 jam di rumah sakit.',
                ],
                'documents' => [
                    ['type' => CaregiverDocument::TYPE_KTP, 'status' => CaregiverDocument::STATUS_APPROVED],
                    ['type' => CaregiverDocument::TYPE_STR, 'status' => CaregiverDocument::STATUS_APPROVED],
                ],
            ],
            [
                'user' => [
                    'name' => 'Nurul Aini, A.Md.FT',
                    'email' => 'nurul@bookingboo.test',
                    'phone' => '081355000003',
                    'email_verified_at' => now(),
                ],
                'profile' => [
                    'skills' => 'Fisioterapi, Terapi Gerak, Pasca Stroke, Pemulihan Cedera',
                    'service_area' => 'Jakarta Barat, Jakarta Pusat, Tangerang',
                    'latitude' => -6.1557,
                    'longitude' => 106.7430,
                    'hourly_rate' => 75000,
                    'rating' => 5.0,
                    'verification_status' => Caregiver::VERIFICATION_VERIFIED,
                    'bio' => 'Fisioterapis berlisensi STR aktif. Berpengalaman menangani mobilisasi pasien pasca tirah baring lama dan terapi stroke di rumah.',
                ],
                'documents' => [
                    ['type' => CaregiverDocument::TYPE_KTP, 'status' => CaregiverDocument::STATUS_APPROVED],
                    ['type' => CaregiverDocument::TYPE_STR, 'status' => CaregiverDocument::STATUS_APPROVED],
                ],
            ],
            [
                // Menunggu verifikasi dokumen: muncul di antrean verifikasi admin
                'user' => [
                    'name' => 'Ahmad Fauzi',
                    'email' => 'ahmad@bookingboo.test',
                    'phone' => '081355000004',
                    'email_verified_at' => now(),
                ],
                'profile' => [
                    'skills' => 'Perawatan lansia, pendampingan kontrol',
                    'service_area' => 'Bekasi, Jakarta Timur',
                    'latitude' => -6.2383,
                    'longitude' => 106.9926,
                    'hourly_rate' => 45000,
                    'rating' => 0,
                    'verification_status' => Caregiver::VERIFICATION_PENDING,
                    'bio' => 'Caregiver bersertifikat pelatihan pendampingan lansia dinas sosial.',
                ],
                'documents' => [
                    ['type' => CaregiverDocument::TYPE_KTP, 'status' => CaregiverDocument::STATUS_PENDING],
                    ['type' => CaregiverDocument::TYPE_CERTIFICATE, 'status' => CaregiverDocument::STATUS_PENDING],
                ],
            ],
            [
                // Ditolak: dokumen tidak valid, contoh keputusan verifikasi admin
                'user' => [
                    'name' => 'Tono Wijaya',
                    'email' => 'tono@bookingboo.test',
                    'phone' => '081355000005',
                    'email_verified_at' => now(),
                ],
                'profile' => [
                    'skills' => 'Pendampingan berobat',
                    'service_area' => 'Tangerang Selatan',
                    'latitude' => -6.2936,
                    'longitude' => 106.7019,
                    'hourly_rate' => 40000,
                    'rating' => 0,
                    'verification_status' => Caregiver::VERIFICATION_REJECTED,
                    'bio' => 'Fresh graduate, ingin memulai karier sebagai caregiver.',
                ],
                'documents' => [
                    [
                        'type' => CaregiverDocument::TYPE_KTP,
                        'status' => CaregiverDocument::STATUS_REJECTED,
                        'rejection_reason' => 'Foto KTP tidak jelas, silakan unggah ulang dengan pencahayaan cukup.',
                    ],
                ],
            ],
        ];

        foreach ($caregivers as $data) {
            $user = User::create($data['user'] + [
                'role' => User::ROLE_CAREGIVER,
                'status' => User::STATUS_ACTIVE,
                'password' => 'password',
            ]);

            $caregiver = Caregiver::create($data['profile'] + ['user_id' => $user->id]);

            foreach ($data['documents'] as $doc) {
                CaregiverDocument::create([
                    'caregiver_id' => $caregiver->id,
                    'type' => $doc['type'],
                    'file_path' => 'documents/sample_'.str_replace('_', '_', $doc['type']).'_'.strtolower(explode(' ', $user->name)[0]).'.pdf',
                    'status' => $doc['status'],
                    'rejection_reason' => $doc['rejection_reason'] ?? null,
                    'reviewed_at' => in_array($doc['status'], [CaregiverDocument::STATUS_APPROVED, CaregiverDocument::STATUS_REJECTED]) ? now()->subDays(7) : null,
                ]);
            }
        }
    }
}
