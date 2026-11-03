<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengguna', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 200);
            $table->string('username', 50)->unique();
            $table->string('kata_sandi', 255);
            $table->boolean('aktif')->default(true);
            $table->timestampTz('dibuat_pada')->useCurrent();
            $table->timestampTz('diubah_pada')->useCurrent();
        });

        // Tabel spatie/laravel-permission dengan nama Indonesia.
        // Kolom name/guard_name mengikuti konvensi spatie (dipakai di query internal);
        // kolom 'nama' di hak_akses menyimpan label bahasa Indonesia.
        Schema::create('peran', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('guard_name');
        });

        Schema::create('hak_akses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique(); // kode, mis. anggaran.edit
            $table->string('guard_name');
            $table->string('nama', 200);          // label Indonesia
        });

        Schema::create('pengguna_peran', function (Blueprint $table) {
            $table->unsignedBigInteger('peran_id');
            $table->string('model_type');
            $table->unsignedBigInteger('pengguna_id');
            $table->primary(['pengguna_id', 'peran_id']);
            $table->foreign('peran_id')->references('id')->on('peran')->cascadeOnDelete();
            $table->foreign('pengguna_id')->references('id')->on('pengguna')->cascadeOnDelete();
        });

        Schema::create('peran_hak_akses', function (Blueprint $table) {
            $table->unsignedBigInteger('peran_id');
            $table->unsignedBigInteger('hak_akses_id');
            $table->primary(['peran_id', 'hak_akses_id']);
            $table->foreign('peran_id')->references('id')->on('peran')->cascadeOnDelete();
            $table->foreign('hak_akses_id')->references('id')->on('hak_akses')->cascadeOnDelete();
        });

        // Kebutuhan internal spatie (izin langsung ke pengguna); tidak dipakai aplikasi.
        Schema::create('pengguna_hak_akses', function (Blueprint $table) {
            $table->unsignedBigInteger('hak_akses_id');
            $table->string('model_type');
            $table->unsignedBigInteger('pengguna_id');
            $table->primary(['pengguna_id', 'hak_akses_id']);
            $table->foreign('hak_akses_id')->references('id')->on('hak_akses')->cascadeOnDelete();
            $table->foreign('pengguna_id')->references('id')->on('pengguna')->cascadeOnDelete();
        });

        // Tabel bawaan scaffold yang tidak dipakai (diganti 'pengguna').
        Schema::dropIfExists('users');
    }

    public function down(): void
    {
        Schema::dropIfExists('pengguna_hak_akses');
        Schema::dropIfExists('peran_hak_akses');
        Schema::dropIfExists('pengguna_peran');
        Schema::dropIfExists('hak_akses');
        Schema::dropIfExists('peran');
        Schema::dropIfExists('pengguna');
    }
};
