<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('phone_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number');
            $table->string('code', 6);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('expires_at');
            $table->json('registration_data')->nullable(); // Временные данные регистрации
            $table->timestamps();
            
            $table->index(['phone_number', 'code']);
            $table->index(['phone_number', 'is_verified']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phone_verifications');
    }
};
