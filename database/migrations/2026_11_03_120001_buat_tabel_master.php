<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skpd', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama', 200);
            $table->boolean('aktif')->default(true);
            $table->timestampTz('dibuat_pada')->useCurrent();
            $table->timestampTz('diubah_pada')->useCurrent();
        });

        Schema::create('rekening', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama', 200);
            $table->string('kelompok', 20);
            $table->integer('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestampTz('dibuat_pada')->useCurrent();
            $table->timestampTz('diubah_pada')->useCurrent();
        });

        Schema::create('jenis_kebutuhan', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->unique();
            $table->integer('urutan')->default(0);
            $table->boolean('aktif')->default(true);
        });

        Schema::create('jenis_kebutuhan_tambahan', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->unique();
            $table->integer('urutan')->default(0);
            $table->boolean('aktif')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jenis_kebutuhan_tambahan');
        Schema::dropIfExists('jenis_kebutuhan');
        Schema::dropIfExists('rekening');
        Schema::dropIfExists('skpd');
    }
};
