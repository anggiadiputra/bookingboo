<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('suspended_by')->nullable()->after('approved_at');
            $table->text('suspended_reason')->nullable()->after('suspended_by');
            $table->timestamp('suspended_at')->nullable()->after('suspended_reason');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['suspended_by', 'suspended_reason', 'suspended_at']);
        });
    }
};
