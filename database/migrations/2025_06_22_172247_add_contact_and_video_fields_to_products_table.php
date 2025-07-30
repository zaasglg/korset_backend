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
            $table->string('whatsapp_number')->nullable()->after('address');
            $table->string('phone_number')->nullable()->after('whatsapp_number');
            $table->boolean('ready_for_video_demo')->default(false)->after('is_video_call_available');
            $table->bigInteger('views_count')->default(0)->after('ready_for_video_demo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_number',
                'phone_number', 
                'ready_for_video_demo',
                'views_count'
            ]);
        });
    }
};
