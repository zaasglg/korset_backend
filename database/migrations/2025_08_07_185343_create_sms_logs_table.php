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
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number');
            $table->text('message');
            $table->string('type')->default('general'); // welcome, verification, general
            $table->boolean('success')->default(false);
            $table->string('sms_id')->nullable(); // SMSC response ID
            $table->integer('sms_count')->nullable(); // Number of SMS parts
            $table->text('error_message')->nullable();
            $table->string('error_code')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['phone_number', 'created_at']);
            $table->index(['type', 'success']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
