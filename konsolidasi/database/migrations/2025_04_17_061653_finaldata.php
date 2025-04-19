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
            $table->decimal('final_inflasi', 15, 2)->nullable()->after('inflasi');
            $table->decimal('final_andil', 15, 4)->nullable()->after('andil');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
