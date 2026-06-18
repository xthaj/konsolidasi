<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('komoditas', function (Blueprint $table) {
            $table->boolean('is_harga')->default(false)->after('nama_komoditas');
        });
    }

    public function down(): void
    {
        Schema::table('komoditas', function (Blueprint $table) {
            $table->dropColumn('is_harga');
        });
    }
};
