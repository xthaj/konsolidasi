<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user', function (Blueprint $table) {
            // Make password nullable
            $table->string('password')->nullable()->change();

            // Add akun_sso as boolean, default false (0)
            $table->boolean('akun_sso')->default(0);
        });

        // Ensure all existing users get akun_sso = 0
        DB::table('user')->update(['akun_sso' => 0]);
    }

    public function down(): void
    {
        Schema::table('user', function (Blueprint $table) {
            $table->dropColumn('akun_sso');

            // Revert password to not nullable if needed (optional)
            $table->string('password')->nullable(false)->change();
        });
    }
};
