<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->boolean('needs_replacement')->default(false)->after('cancelled_at');
            $table->timestamp('replacement_offered_at')->nullable()->after('needs_replacement');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['needs_replacement', 'replacement_offered_at']);
        });
    }
};
