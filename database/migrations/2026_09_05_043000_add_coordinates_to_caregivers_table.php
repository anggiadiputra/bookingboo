<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Koordinat GPS caregiver untuk pencarian "terdekat dari lokasi saya".
     * Keduanya nullable karena lokasi opsional.
     */
    public function up(): void
    {
        Schema::table('caregivers', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('service_area');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('caregivers', function (Blueprint $table) {
            $table->dropIndex(['latitude', 'longitude']);
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
