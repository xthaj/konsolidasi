<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE inflasi ALTER COLUMN harga BIGINT NULL');
        DB::statement('ALTER TABLE inflasi ALTER COLUMN rentang_bawah BIGINT NULL');
        DB::statement('ALTER TABLE inflasi ALTER COLUMN rentang_atas BIGINT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE inflasi ALTER COLUMN harga DECIMAL(15,2) NULL');
        DB::statement('ALTER TABLE inflasi ALTER COLUMN rentang_bawah DECIMAL(15,2) NULL');
        DB::statement('ALTER TABLE inflasi ALTER COLUMN rentang_atas DECIMAL(15,2) NULL');
    }
};
