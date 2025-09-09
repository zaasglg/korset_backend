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
        Schema::table('chats', function (Blueprint $table) {
            // Добавляем поле для продавца (владельца товара)
            $table->unsignedBigInteger('seller_id')->nullable()->after('user_id');
            
            // Добавляем индексы для быстрого поиска
            $table->index(['user_id', 'seller_id', 'product_id']);
            $table->index(['seller_id']);
            
            // Добавляем внешний ключ для продавца
            $table->foreign('seller_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->dropForeign(['seller_id']);
            $table->dropIndex(['user_id', 'seller_id', 'product_id']);
            $table->dropIndex(['seller_id']);
            $table->dropColumn('seller_id');
        });
    }
};