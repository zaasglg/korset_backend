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
        Schema::table('products', function (Blueprint $table) {
            // Проверяем, существуют ли поля перед добавлением
            if (!Schema::hasColumn('products', 'publication_price_id')) {
                $table->foreignId('publication_price_id')->nullable()->after('user_id')->constrained('publication_prices')->onDelete('set null');
            }
            if (!Schema::hasColumn('products', 'paid_amount')) {
                $table->decimal('paid_amount', 10, 2)->default(0)->after('publication_price_id')->comment('Сумма, которая была списана за публикацию');
            }
            if (!Schema::hasColumn('products', 'payment_reference')) {
                $table->string('payment_reference')->nullable()->after('paid_amount')->comment('Референс платежа');
            }
            if (!Schema::hasColumn('products', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('payment_reference')->comment('Дата истечения публикации');
            }
            if (!Schema::hasColumn('products', 'is_promoted')) {
                $table->boolean('is_promoted')->default(false)->after('expires_at')->comment('Продвигается ли объявление');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'publication_price_id')) {
                $table->dropForeign(['publication_price_id']);
                $table->dropColumn('publication_price_id');
            }
            if (Schema::hasColumn('products', 'paid_amount')) {
                $table->dropColumn('paid_amount');
            }
            if (Schema::hasColumn('products', 'payment_reference')) {
                $table->dropColumn('payment_reference');
            }
            if (Schema::hasColumn('products', 'expires_at')) {
                $table->dropColumn('expires_at');
            }
            if (Schema::hasColumn('products', 'is_promoted')) {
                $table->dropColumn('is_promoted');
            }
        });
    }
};
