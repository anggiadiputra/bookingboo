<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->string('status')->default('published')->after('visibility');
            $table->foreignId('hidden_by')->nullable()->after('status');
            $table->text('hidden_reason')->nullable()->after('hidden_by');
            $table->timestamp('hidden_at')->nullable()->after('hidden_reason');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->dropColumn(['status', 'hidden_by', 'hidden_reason', 'hidden_at']);
        });
    }
};
