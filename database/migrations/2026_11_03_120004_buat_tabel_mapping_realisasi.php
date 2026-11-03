<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mapping_template', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->unique();
            $table->string('kolom_skpd', 100);
            $table->timestampTz('dibuat_pada')->useCurrent();
            $table->timestampTz('diubah_pada')->useCurrent();
        });

        Schema::create('mapping_template_kolom', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('mapping_template')->cascadeOnDelete();
            $table->string('kolom_excel', 100);
            $table->foreignId('rekening_id')->constrained('rekening');
            $table->unique(['template_id', 'kolom_excel']);
            $table->unique(['template_id', 'rekening_id']);
        });

        Schema::create('realisasi', function (Blueprint $table) {
            $table->id();
            $table->char('periode', 7);
            $table->foreignId('skpd_id')->constrained('skpd');
            $table->foreignId('rekening_id')->constrained('rekening');
            $table->decimal('jumlah', 18, 2)->default(0);
            $table->string('file_sumber', 255)->nullable();
            $table->foreignId('diimpor_oleh')->nullable()->constrained('pengguna');
            $table->timestampTz('diimpor_pada')->useCurrent();
            $table->unique(['periode', 'skpd_id', 'rekening_id']);
            $table->index('periode', 'idx_realisasi_periode');
            $table->index('skpd_id', 'idx_realisasi_skpd');
        });
        DB::statement('ALTER TABLE realisasi ADD CONSTRAINT realisasi_jumlah_nonnegatif CHECK (jumlah >= 0)');
        DB::statement("ALTER TABLE realisasi ADD CONSTRAINT realisasi_periode_format CHECK (periode ~ '^[0-9]{4}-(0[1-9]|1[0-2])$')");
    }

    public function down(): void
    {
        Schema::dropIfExists('realisasi');
        Schema::dropIfExists('mapping_template_kolom');
        Schema::dropIfExists('mapping_template');
    }
};
