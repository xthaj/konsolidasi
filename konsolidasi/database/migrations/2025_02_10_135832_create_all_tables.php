<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wilayah', function (Blueprint $table) {
            $table->string('kd_wilayah', 10)->primary();
            $table->string('nama_wilayah', 255);
            $table->tinyInteger('flag');
            $table->string('parent_kd', 10)->nullable();
            $table->timestamps();
        });

        // Add the self-referencing foreign key AFTER the table is created
        Schema::table('wilayah', function (Blueprint $table) {
            $table->foreign('parent_kd')
                ->references('kd_wilayah')
                ->on('wilayah')
                ->onDelete('NO ACTION');
        });

        Schema::create('user', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('username', 255)->unique();
            $table->string('password', 255);
            $table->string('nama_lengkap', 255);
            $table->tinyInteger('is_pusat');
            $table->tinyInteger('is_admin');
            $table->string('kd_wilayah', 10)->nullable();
            $table->timestamps(); // created_at and updated_at added
            $table->foreign('kd_wilayah')->references('kd_wilayah')->on('wilayah')->nullOnDelete();
        });

        Schema::create('komoditas', function (Blueprint $table) {
            $table->string('kd_komoditas', 3)->primary();
            $table->string('nama_komoditas', 255);
            $table->timestamps(); // created_at and updated_at added
        });

        Schema::create('bulan_tahun', function (Blueprint $table) {
            $table->id('bulan_tahun_id');
            $table->tinyInteger('bulan');
            $table->smallInteger('tahun');
            $table->tinyInteger('aktif');
            $table->timestamps(); // created_at and updated_at added
        });

        Schema::create('level_harga', function (Blueprint $table) {
            $table->string('kd_level', 2)->primary();
            $table->string('nama_level', 255);
            $table->timestamps(); // created_at and updated_at added
        });

        Schema::create('inflasi', function (Blueprint $table) {
            $table->id('inflasi_id');
            $table->string('kd_komoditas', 3);
            $table->string('kd_wilayah', 10);
            $table->unsignedBigInteger('bulan_tahun_id');
            $table->string('kd_level', 2);
            $table->decimal('inflasi', 15, 2)->nullable();
            $table->decimal('andil', 15, 2)->nullable();
            $table->timestamps();

            $table->foreign('kd_komoditas')->references('kd_komoditas')->on('komoditas')->cascadeOnDelete();
            $table->foreign('kd_wilayah')->references('kd_wilayah')->on('wilayah')->cascadeOnDelete();
            $table->foreign('bulan_tahun_id')->references('bulan_tahun_id')->on('bulan_tahun')->noActionOnUpdate();
            $table->foreign('kd_level')->references('kd_level')->on('level_harga')->cascadeOnDelete();
        });

        Schema::create('rekonsiliasi', function (Blueprint $table) {
            $table->id('rekonsiliasi_id');
            $table->unsignedBigInteger('inflasi_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('bulan_tahun_id');
            $table->dateTime('terakhir_diedit')->nullable();
            $table->string('alasan', 500)->nullable();
            $table->string('detail', 255)->nullable();
            $table->string('media', 255)->nullable();
            $table->timestamps(); // created_at and updated_at added

            $table->foreign('bulan_tahun_id')->references('bulan_tahun_id')->on('bulan_tahun')->cascadeOnDelete();
            $table->foreign('inflasi_id')->references('inflasi_id')->on('inflasi')->cascadeOnDelete();
            $table->foreign('user_id')->references('user_id')->on('user')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekonsiliasi');
        Schema::dropIfExists('inflasi');
        Schema::dropIfExists('level_harga');
        Schema::dropIfExists('bulan_tahun');
        Schema::dropIfExists('komoditas');
        Schema::dropIfExists('user');
        Schema::dropIfExists('wilayah'); // Drop wilayah last due to foreign key constraints
    }
};
