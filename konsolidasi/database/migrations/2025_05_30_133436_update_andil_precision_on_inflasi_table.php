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
            $table->dropColumn(['andil', 'final_inflasi', 'final_andil']);
        });

        Schema::table('inflasi', function (Blueprint $table) {
            $table->decimal('andil', 15, 2)->nullable()->after('nilai_inflasi');
            $table->decimal('final_inflasi', 15, 2)->nullable()->after('andil');
            $table->decimal('final_andil', 15, 2)->nullable()->after('final_inflasi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inflasi', function (Blueprint $table) {
            $table->dropColumn(['andil', 'final_inflasi', 'final_andil']);
        });

        Schema::table('inflasi', function (Blueprint $table) {
            $table->decimal('andil', 15, 2)->nullable()->after('nilai_inflasi');
            $table->decimal('final_inflasi', 15, 2)->nullable()->after('andil');
            $table->decimal('final_andil', 15, 2)->nullable()->after('final_inflasi');
        });
    }
};
