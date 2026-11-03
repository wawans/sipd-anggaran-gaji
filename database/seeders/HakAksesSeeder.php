<?php

namespace Database\Seeders;

use App\Models\HakAkses;
use Illuminate\Database\Seeder;

class HakAksesSeeder extends Seeder
{
    /** @return array<int, array{kode: string, nama: string}> */
    public static function daftar(): array
    {
        return [
            ['kode' => 'laporan.view', 'nama' => 'Lihat laporan & dashboard'],
            ['kode' => 'laporan.export', 'nama' => 'Ekspor laporan'],
            ['kode' => 'skpd.view', 'nama' => 'Lihat master SKPD'],
            ['kode' => 'skpd.manage', 'nama' => 'Kelola master SKPD'],
            ['kode' => 'rekening.view', 'nama' => 'Lihat master rekening'],
            ['kode' => 'rekening.manage', 'nama' => 'Kelola master rekening'],
            ['kode' => 'jenis_kebutuhan.view', 'nama' => 'Lihat master jenis kebutuhan'],
            ['kode' => 'jenis_kebutuhan.manage', 'nama' => 'Kelola master jenis kebutuhan'],
            ['kode' => 'kebutuhan_tambahan.view', 'nama' => 'Lihat master jenis kebutuhan tambahan'],
            ['kode' => 'kebutuhan_tambahan.manage', 'nama' => 'Kelola master jenis kebutuhan tambahan'],
            ['kode' => 'tahap.view', 'nama' => 'Lihat tahap'],
            ['kode' => 'tahap.create', 'nama' => 'Buat tahap'],
            ['kode' => 'tahap.edit', 'nama' => 'Ubah tahap (sebelum dikunci)'],
            ['kode' => 'tahap.lock', 'nama' => 'Kunci/finalkan tahap'],
            ['kode' => 'anggaran.view', 'nama' => 'Lihat anggaran'],
            ['kode' => 'anggaran.edit', 'nama' => 'Input/ubah anggaran (tahap belum dikunci)'],
            ['kode' => 'anggaran.salin', 'nama' => 'Salin anggaran antar tahap'],
            ['kode' => 'realisasi.view', 'nama' => 'Lihat realisasi'],
            ['kode' => 'realisasi.import', 'nama' => 'Impor realisasi'],
            ['kode' => 'mapping.view', 'nama' => 'Lihat template mapping'],
            ['kode' => 'mapping.manage', 'nama' => 'Kelola template mapping'],
            ['kode' => 'pengguna.view', 'nama' => 'Lihat pengguna'],
            ['kode' => 'pengguna.manage', 'nama' => 'Kelola pengguna'],
            ['kode' => 'peran.manage', 'nama' => 'Kelola peran & hak akses'],
            ['kode' => 'audit.view', 'nama' => 'Lihat log audit'],
        ];
    }

    public function run(): void
    {
        foreach (self::daftar() as $hak) {
            HakAkses::updateOrCreate(
                ['name' => $hak['kode'], 'guard_name' => 'web'],
                ['nama' => $hak['nama']],
            );
        }
    }
}
