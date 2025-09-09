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
        Schema::create('publication_prices', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['story', 'announcement'])->comment('Тип публикации: story - сторис, announcement - объявление');
            $table->string('name')->comment('Название тарифа');
            $table->text('description')->nullable()->comment('Описание тарифа');
            $table->decimal('price', 10, 2)->comment('Цена в KZT');
            $table->integer('duration_hours')->default(24)->comment('Длительность публикации в часах');
            $table->boolean('is_active')->default(true)->comment('Активен ли тариф');
            $table->json('features')->nullable()->comment('Дополнительные возможности (JSON)');
            $table->integer('sort_order')->default(0)->comment('Порядок сортировки');
            $table->timestamps();
            
            $table->index(['type', 'is_active']);
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publication_prices');
    }
};
