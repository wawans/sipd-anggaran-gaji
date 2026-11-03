<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
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

        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_tahap_ringkas AS
            SELECT
              t.id AS tahap_id, t.tahun, t.nama, t.status,
              COALESCE(SUM(v.total_pagu), 0) AS total_anggaran
            FROM tahap t
            LEFT JOIN v_anggaran_baris v ON v.tahap_id = t.id
            GROUP BY t.id
            SQL);

        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_realisasi_tahap AS
            SELECT
              t.id AS tahap_id,
              r.skpd_id,
              r.rekening_id,
              SUM(r.jumlah) AS total_realisasi
            FROM tahap t
            JOIN realisasi r
              ON r.periode >= to_char(t.tgl_mulai, 'YYYY-MM')
             AND r.periode <= to_char(t.tgl_selesai, 'YYYY-MM')
            GROUP BY t.id, r.skpd_id, r.rekening_id
            SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_realisasi_tahap');
        DB::statement('DROP VIEW IF EXISTS v_tahap_ringkas');
        DB::statement('DROP VIEW IF EXISTS v_anggaran_baris');
    }
};
