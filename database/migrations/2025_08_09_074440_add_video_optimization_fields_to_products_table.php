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
            $table->string('video_thumbnail')->nullable()->after('video');
            $table->bigInteger('original_video_size')->nullable()->after('video_thumbnail');
            $table->bigInteger('optimized_video_size')->nullable()->after('original_video_size');
            $table->decimal('compression_ratio', 5, 2)->nullable()->after('optimized_video_size');
            $table->integer('video_duration')->nullable()->after('compression_ratio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'video_thumbnail',
                'original_video_size', 
                'optimized_video_size',
                'compression_ratio',
                'video_duration'
            ]);
        });
    }
};
