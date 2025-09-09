<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Для SQLite нужно пересоздать таблицу с новым enum
        DB::statement("
            CREATE TABLE publication_prices_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                type TEXT CHECK(type IN ('story', 'announcement', 'booking_commission')) NOT NULL,
                name TEXT NOT NULL,
                description TEXT,
                price DECIMAL(10,2) NOT NULL,
                duration_hours INTEGER DEFAULT 24 NOT NULL,
                is_active BOOLEAN DEFAULT 1 NOT NULL,
                features TEXT,
                sort_order INTEGER DEFAULT 0 NOT NULL,
                created_at TIMESTAMP,
                updated_at TIMESTAMP
            )
        ");
        
        DB::statement("INSERT INTO publication_prices_new SELECT * FROM publication_prices");
        DB::statement("DROP TABLE publication_prices");
        DB::statement("ALTER TABLE publication_prices_new RENAME TO publication_prices");
        
        // Восстанавливаем индексы
        DB::statement("CREATE INDEX publication_prices_type_is_active_index ON publication_prices(type, is_active)");
        DB::statement("CREATE INDEX publication_prices_sort_order_index ON publication_prices(sort_order)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Возвращаем к старому enum
        DB::statement("
            CREATE TABLE publication_prices_old (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                type TEXT CHECK(type IN ('story', 'announcement')) NOT NULL,
                name TEXT NOT NULL,
                description TEXT,
                price DECIMAL(10,2) NOT NULL,
                duration_hours INTEGER DEFAULT 24 NOT NULL,
                is_active BOOLEAN DEFAULT 1 NOT NULL,
                features TEXT,
                sort_order INTEGER DEFAULT 0 NOT NULL,
                created_at TIMESTAMP,
                updated_at TIMESTAMP
            )
        ");
        
        DB::statement("INSERT INTO publication_prices_old SELECT * FROM publication_prices WHERE type != 'booking_commission'");
        DB::statement("DROP TABLE publication_prices");
        DB::statement("ALTER TABLE publication_prices_old RENAME TO publication_prices");
        
        // Восстанавливаем индексы
        DB::statement("CREATE INDEX publication_prices_type_is_active_index ON publication_prices(type, is_active)");
        DB::statement("CREATE INDEX publication_prices_sort_order_index ON publication_prices(sort_order)");
    }
};
