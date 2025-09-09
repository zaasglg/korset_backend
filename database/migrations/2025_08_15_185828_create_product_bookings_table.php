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
        Schema::create('product_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('Пользователь, который бронирует');
            $table->foreignId('publication_price_id')->nullable()->constrained('publication_prices')->onDelete('set null')->comment('Тариф комиссии за бронирование');
            $table->decimal('commission_amount', 10, 2)->default(0)->comment('Сумма комиссии за бронирование');
            $table->string('payment_reference')->nullable()->comment('Референс платежа комиссии');
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending')->comment('Статус бронирования');
            $table->timestamp('booked_at')->useCurrent()->comment('Дата и время бронирования');
            $table->timestamp('expires_at')->nullable()->comment('Дата истечения бронирования');
            $table->text('notes')->nullable()->comment('Заметки к бронированию');
            $table->timestamps();
            
            // Индексы
            $table->index(['product_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('booked_at');
            
            // Уникальное активное бронирование на продукт
            $table->unique(['product_id'], 'unique_active_booking');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_bookings');
    }
};
