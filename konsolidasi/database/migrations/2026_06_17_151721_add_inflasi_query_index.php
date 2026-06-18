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
        Schema::table('inflasi', function (Blueprint $table) {
            $table->index(['bulan_tahun_id', 'kd_komoditas', 'kd_level', 'kd_wilayah'], 'idx_inflasi_main_query');
        });
    }

    public function down(): void
    {
        Schema::table('inflasi', function (Blueprint $table) {
            $table->dropIndex('idx_inflasi_main_query');
        });
    }
};
