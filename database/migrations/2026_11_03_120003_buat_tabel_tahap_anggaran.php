<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahap', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('tahun');
            $table->string('nama', 100);
            $table->date('tgl_mulai');
            $table->date('tgl_selesai');
            $table->string('status', 20)->default('draf');
            $table->text('catatan')->nullable();
            $table->timestampTz('dibuat_pada')->useCurrent();
            $table->timestampTz('diubah_pada')->useCurrent();
        });
        DB::statement("ALTER TABLE tahap ADD CONSTRAINT tahap_tgl_urut CHECK (tgl_mulai <= tgl_selesai)");
        DB::statement("ALTER TABLE tahap ADD CONSTRAINT tahap_status_valid CHECK (status in ('draf','final','terkunci'))");

        Schema::create('anggaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tahap_id')->constrained('tahap');
            $table->foreignId('skpd_id')->constrained('skpd');
            $table->foreignId('rekening_id')->constrained('rekening');
            $table->decimal('nilai', 18, 2)->default(0);
            $table->decimal('volume', 10, 2)->default(0);
            $table->timestampTz('dibuat_pada')->useCurrent();
            $table->timestampTz('diubah_pada')->useCurrent();
            $table->unique(['tahap_id', 'skpd_id', 'rekening_id']);
        });
        DB::statement('ALTER TABLE anggaran ADD CONSTRAINT anggaran_nilai_nonnegatif CHECK (nilai >= 0)');
        DB::statement('ALTER TABLE anggaran ADD CONSTRAINT anggaran_volume_nonnegatif CHECK (volume >= 0)');

        Schema::create('anggaran_kebutuhan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anggaran_id')->constrained('anggaran')->cascadeOnDelete();
            $table->foreignId('jenis_kebutuhan_id')->constrained('jenis_kebutuhan');
            $table->decimal('nominal', 18, 2)->default(0);
            $table->unique(['anggaran_id', 'jenis_kebutuhan_id']);
        });
        DB::statement('ALTER TABLE anggaran_kebutuhan ADD CONSTRAINT anggaran_kebutuhan_nominal_nonnegatif CHECK (nominal >= 0)');

        Schema::create('anggaran_kebutuhan_tambahan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anggaran_id')->constrained('anggaran')->cascadeOnDelete();
            $table->foreignId('jenis_kebutuhan_tambahan_id')->constrained('jenis_kebutuhan_tambahan');
            $table->decimal('nominal', 18, 2)->default(0);
            $table->unique(['anggaran_id', 'jenis_kebutuhan_tambahan_id']);
        });
        DB::statement('ALTER TABLE anggaran_kebutuhan_tambahan ADD CONSTRAINT anggaran_kebutuhan_tambahan_nominal_nonnegatif CHECK (nominal >= 0)');

        Schema::table('anggaran', function (Blueprint $table) {
            $table->index('tahap_id', 'idx_anggaran_tahap');
            $table->index('skpd_id', 'idx_anggaran_skpd');
        });
        Schema::table('tahap', function (Blueprint $table) {
            $table->index('tahun', 'idx_tahap_tahun');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anggaran_kebutuhan_tambahan');
        Schema::dropIfExists('anggaran_kebutuhan');
        Schema::dropIfExists('anggaran');
        Schema::dropIfExists('tahap');
    }
};
