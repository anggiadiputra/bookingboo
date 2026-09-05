<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('refund_amount', 10, 2)->default(0)->after('total_amount');
            $table->decimal('cancellation_fee', 10, 2)->default(0)->after('refund_amount');
            $table->unsignedTinyInteger('refund_percent')->default(0)->after('cancellation_fee');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['refund_amount', 'cancellation_fee', 'refund_percent']);
        });
    }
};
