<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Pengaman lapis kedua di database: tahap terkunci tidak bisa diubah.
// Aturan utama tetap ditegakkan di lapisan aplikasi (cek status + hak akses).
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION cegah_ubah_tahap_terkunci()
            RETURNS trigger AS $$
            BEGIN
              IF EXISTS (SELECT 1 FROM tahap WHERE id = OLD.tahap_id AND status = 'terkunci') THEN
                RAISE EXCEPTION 'Tahap sudah terkunci: baris anggaran tidak boleh diubah/dihapus';
              END IF;
              RETURN OLD;
            END;
            $$ LANGUAGE plpgsql
            SQL);

        DB::statement(<<<'SQL'
            CREATE TRIGGER trg_anggaran_kunci
            BEFORE UPDATE OR DELETE ON anggaran
            FOR EACH ROW EXECUTE FUNCTION cegah_ubah_tahap_terkunci()
            SQL);

        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION cegah_ubah_kebutuhan_tahap_terkunci()
            RETURNS trigger AS $$
            BEGIN
              IF EXISTS (SELECT 1 FROM anggaran a JOIN tahap t ON t.id = a.tahap_id
                         WHERE a.id = OLD.anggaran_id AND t.status = 'terkunci') THEN
                RAISE EXCEPTION 'Tahap sudah terkunci: rincian kebutuhan tidak boleh diubah/dihapus';
              END IF;
              RETURN OLD;
            END;
            $$ LANGUAGE plpgsql
            SQL);

        DB::statement(<<<'SQL'
            CREATE TRIGGER trg_anggaran_kebutuhan_kunci
            BEFORE UPDATE OR DELETE ON anggaran_kebutuhan
            FOR EACH ROW EXECUTE FUNCTION cegah_ubah_kebutuhan_tahap_terkunci()
            SQL);

        DB::statement(<<<'SQL'
            CREATE TRIGGER trg_anggaran_kebutuhan_tambahan_kunci
            BEFORE UPDATE OR DELETE ON anggaran_kebutuhan_tambahan
            FOR EACH ROW EXECUTE FUNCTION cegah_ubah_kebutuhan_tahap_terkunci()
            SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS trg_anggaran_kebutuhan_tambahan_kunci ON anggaran_kebutuhan_tambahan');
        DB::statement('DROP TRIGGER IF EXISTS trg_anggaran_kebutuhan_kunci ON anggaran_kebutuhan');
        DB::statement('DROP TRIGGER IF EXISTS trg_anggaran_kunci ON anggaran');
        DB::statement('DROP FUNCTION IF EXISTS cegah_ubah_kebutuhan_tahap_terkunci()');
        DB::statement('DROP FUNCTION IF EXISTS cegah_ubah_tahap_terkunci()');
    }
};
