<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caregivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('skills')->nullable();
            $table->string('service_area')->nullable();
            $table->decimal('hourly_rate', 10, 2)->default(0);
            $table->decimal('rating', 3, 2)->default(0);
            $table->string('verification_status')->default('pending'); // pending, verified, rejected
            $table->text('bio')->nullable();
            $table->string('photo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caregivers');
    }
};
