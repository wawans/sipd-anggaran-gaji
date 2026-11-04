<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hierarki rekening: ganti kolom kelompok (label teks) dengan
     * parent_rekening_id (relasi multi-level) + kolom tingkat otomatis
     * (jumlah karakter kode, apa adanya) + view v_rekening_leluhur
     * untuk rollup agregat per induk.
     */
    public function up(): void
    {
        Schema::table('rekening', function (Blueprint $table) {
            $table->dropColumn('kelompok');
            $table->foreignId('parent_rekening_id')->nullable()->constrained('rekening')->restrictOnDelete();
            $table->integer('tingkat')->storedAs('LENGTH(kode)');
        });

        DB::statement('ALTER TABLE rekening ADD CONSTRAINT rekening_parent_bukan_diri CHECK (parent_rekening_id IS DISTINCT FROM id)');

        DB::statement(<<<'SQL'
            CREATE VIEW v_rekening_leluhur AS
            WITH RECURSIVE t(rekening_id, leluhur_id) AS (
                SELECT id, id FROM rekening
                UNION
                SELECT t.rekening_id, r.parent_rekening_id
                FROM t
                JOIN rekening r ON r.id = t.leluhur_id
                WHERE r.parent_rekening_id IS NOT NULL
            )
            SELECT rekening_id, leluhur_id FROM t
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_rekening_leluhur');
        DB::statement('ALTER TABLE rekening DROP CONSTRAINT IF EXISTS rekening_parent_bukan_diri');

        Schema::table('rekening', function (Blueprint $table) {
            $table->dropForeign(['parent_rekening_id']);
            $table->dropColumn(['parent_rekening_id', 'tingkat']);
            $table->string('kelompok', 20)->nullable();
        });
    }
};
