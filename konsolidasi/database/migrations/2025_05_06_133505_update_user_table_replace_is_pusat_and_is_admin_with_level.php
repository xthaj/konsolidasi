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
        Schema::table('user', function (Blueprint $table) {
            $table->tinyInteger('level')->after('nama_lengkap')->default(3); // default to Operator Provinsi
        });

        // Optional: migrate old data (set level = 0 for is_pusat = 1)
        DB::table('user')->where('is_pusat', 1)->update(['level' => 0]);

        Schema::table('user', function (Blueprint $table) {
            $table->dropColumn(['is_pusat', 'is_admin']);
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
