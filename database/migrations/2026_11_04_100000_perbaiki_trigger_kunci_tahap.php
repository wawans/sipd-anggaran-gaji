<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Perbaikan bug kritis: fungsi trigger sebelumnya me-return OLD pada BEFORE UPDATE,
// sehingga SEMUA perubahan baris anggaran/rincian dibuang diam-diam oleh database
// (bukan hanya saat tahap terkunci) — update() tetap me-return true.
// Yang benar: DELETE → RETURN OLD, UPDATE → RETURN NEW.
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
              IF TG_OP = 'DELETE' THEN
                RETURN OLD;
              END IF;
              RETURN NEW;
            END;
            $$ LANGUAGE plpgsql
            SQL);

        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION cegah_ubah_kebutuhan_tahap_terkunci()
            RETURNS trigger AS $$
            BEGIN
              IF EXISTS (SELECT 1 FROM anggaran a JOIN tahap t ON t.id = a.tahap_id
                         WHERE a.id = OLD.anggaran_id AND t.status = 'terkunci') THEN
                RAISE EXCEPTION 'Tahap sudah terkunci: rincian kebutuhan tidak boleh diubah/dihapus';
              END IF;
              IF TG_OP = 'DELETE' THEN
                RETURN OLD;
              END IF;
              RETURN NEW;
            END;
            $$ LANGUAGE plpgsql
            SQL);
    }

    public function down(): void
    {
        // Tidak ada rollback yang aman ke versi buggy; biarkan kosong.
    }
};
