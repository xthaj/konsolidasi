<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inflasi', function (Blueprint $table) {
            $table->decimal('harga', 15, 2)->nullable()->after('final_andil');
            $table->decimal('rentang_bawah', 15, 2)->nullable()->after('harga');
            $table->decimal('rentang_atas', 15, 2)->nullable()->after('rentang_bawah');
        });
    }

    public function down(): void
    {
        Schema::table('inflasi', function (Blueprint $table) {
            $table->dropColumn(['harga', 'rentang_bawah', 'rentang_atas']);
        });
    }
};
