<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Acress sebagai field khusus per baris anggaran (berdiri sendiri,
     * sejajar nilai/volume). Tipe: nominal (Rp langsung) atau persen (% dari subtotal).
     */
    public function up(): void
    {
        Schema::table('anggaran', function (Blueprint $table) {
            $table->string('acress_type', 10)->default('nominal');
            $table->decimal('acress', 18, 2)->default(0);
        });
        DB::statement("ALTER TABLE anggaran ADD CONSTRAINT anggaran_acress_type_check CHECK (acress_type IN ('nominal','persen'))");

        // Kolom baru diletakkan di AKHIR agar CREATE OR REPLACE VIEW valid
        // (view turunan v_tahap_ringkas membaca total_pagu).
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_anggaran_baris AS
            WITH k AS (
              SELECT anggaran_id, SUM(nominal) AS total_jenis_kebutuhan
              FROM anggaran_kebutuhan GROUP BY anggaran_id
            ),
            t AS (
              SELECT anggaran_id, SUM(nominal) AS total_kebutuhan_tambahan
              FROM anggaran_kebutuhan_tambahan GROUP BY anggaran_id
            ),
            b AS (
              SELECT
                a.id, a.tahap_id, a.skpd_id, a.rekening_id,
                a.nilai, a.volume, a.acress_type, a.acress,
                COALESCE(k.total_jenis_kebutuhan, 0) AS total_jenis_kebutuhan,
                a.nilai + COALESCE(k.total_jenis_kebutuhan, 0) AS kebutuhan_bulan,
                (a.nilai + COALESCE(k.total_jenis_kebutuhan, 0)) * a.volume AS total_kebutuhan,
                COALESCE(t.total_kebutuhan_tambahan, 0) AS total_kebutuhan_tambahan
              FROM anggaran a
              LEFT JOIN k ON k.anggaran_id = a.id
              LEFT JOIN t ON t.anggaran_id = a.id
            )
            SELECT
              id, tahap_id, skpd_id, rekening_id,
              nilai, volume,
              total_jenis_kebutuhan, kebutuhan_bulan, total_kebutuhan, total_kebutuhan_tambahan,
              total_kebutuhan + total_kebutuhan_tambahan
                + CASE WHEN acress_type = 'persen'
                       THEN (total_kebutuhan + total_kebutuhan_tambahan) * acress / 100
                       ELSE acress END AS total_pagu,
              acress_type, acress,
              total_kebutuhan + total_kebutuhan_tambahan AS subtotal
            FROM b
            SQL);
    }

    public function down(): void
    {
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_anggaran_baris AS
            SELECT
              a.id, a.tahap_id, a.skpd_id, a.rekening_id,
              a.nilai, a.volume,
              COALESCE(k.total_jenis_kebutuhan, 0)      AS total_jenis_kebutuhan,
              a.nilai + COALESCE(k.total_jenis_kebutuhan, 0) AS kebutuhan_bulan,
              (a.nilai + COALESCE(k.total_jenis_kebutuhan, 0)) * a.volume AS total_kebutuhan,
              COALESCE(t.total_kebutuhan_tambahan, 0)   AS total_kebutuhan_tambahan,
              (a.nilai + COALESCE(k.total_jenis_kebutuhan, 0)) * a.volume
                + COALESCE(t.total_kebutuhan_tambahan, 0) AS total_pagu
            FROM anggaran a
            LEFT JOIN (
              SELECT anggaran_id, SUM(nominal) AS total_jenis_kebutuhan
              FROM anggaran_kebutuhan GROUP BY anggaran_id
            ) k ON k.anggaran_id = a.id
            LEFT JOIN (
              SELECT anggaran_id, SUM(nominal) AS total_kebutuhan_tambahan
              FROM anggaran_kebutuhan_tambahan GROUP BY anggaran_id
            ) t ON t.anggaran_id = a.id
            SQL);

        DB::statement('ALTER TABLE anggaran DROP CONSTRAINT IF EXISTS anggaran_acress_type_check');
        Schema::table('anggaran', function (Blueprint $table) {
            $table->dropColumn(['acress_type', 'acress']);
        });
    }
};
