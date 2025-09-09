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
        Schema::table('stories', function (Blueprint $table) {
            // Проверяем, существуют ли поля перед добавлением
            if (!Schema::hasColumn('stories', 'publication_price_id')) {
                $table->foreignId('publication_price_id')->nullable()->after('user_id')->constrained('publication_prices')->onDelete('set null');
            }
            if (!Schema::hasColumn('stories', 'paid_amount')) {
                $table->decimal('paid_amount', 10, 2)->default(0)->after('publication_price_id')->comment('Сумма, которая была списана за публикацию');
            }
            if (!Schema::hasColumn('stories', 'payment_reference')) {
                $table->string('payment_reference')->nullable()->after('paid_amount')->comment('Референс платежа');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            if (Schema::hasColumn('stories', 'publication_price_id')) {
                $table->dropForeign(['publication_price_id']);
                $table->dropColumn('publication_price_id');
            }
            if (Schema::hasColumn('stories', 'paid_amount')) {
                $table->dropColumn('paid_amount');
            }
            if (Schema::hasColumn('stories', 'payment_reference')) {
                $table->dropColumn('payment_reference');
            }
        });
    }
};
