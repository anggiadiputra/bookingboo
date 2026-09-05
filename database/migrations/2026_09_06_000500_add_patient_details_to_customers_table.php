<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Profil pasien yang lebih lengkap: identitas pasien (bukan hanya pemesan),
     * data medis dasar, dan info kontak. Seluruh kolom opsional agar data lama
     * (customer yang belum melengkapi) tetap aman.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('patient_name')->nullable()->after('user_id');
            $table->date('patient_birth_date')->nullable()->after('patient_name');
            $table->string('patient_gender', 10)->nullable()->after('patient_birth_date');
            $table->string('patient_blood_type', 5)->nullable()->after('patient_gender');
            $table->string('patient_allergies')->nullable()->after('patient_blood_type');
            $table->text('patient_condition')->nullable()->after('patient_allergies');
            $table->decimal('patient_weight_kg', 5, 1)->unsigned()->nullable()->after('patient_condition');
            $table->decimal('patient_height_cm', 5, 1)->unsigned()->nullable()->after('patient_weight_kg');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'patient_name',
                'patient_birth_date',
                'patient_gender',
                'patient_blood_type',
                'patient_allergies',
                'patient_condition',
                'patient_weight_kg',
                'patient_height_cm',
            ]);
        });
    }
};
