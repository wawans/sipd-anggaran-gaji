<?php

namespace Database\Seeders;

use App\Models\HakAkses;
use App\Models\Peran;
use Illuminate\Database\Seeder;

class PeranSeeder extends Seeder
{
    public function run(): void
    {
        $semua = HakAkses::pluck('name')->all();

        $superadmin = Peran::findOrCreate('Superadmin', 'web');
        $superadmin->syncPermissions($semua);

        $penyusun = Peran::findOrCreate('Penyusun', 'web');
        $penyusun->syncPermissions(array_values(array_diff($semua, [
            'tahap.lock',
            'pengguna.view',
            'pengguna.manage',
            'peran.manage',
            'audit.view',
        ])));

        $verifikator = Peran::findOrCreate('Verifikator', 'web');
        $verifikator->syncPermissions([
            'laporan.view',
            'tahap.view',
            'tahap.lock',
            'anggaran.view',
            'realisasi.view',
        ]);

        $viewer = Peran::findOrCreate('Viewer', 'web');
        $viewer->syncPermissions(['laporan.view']);
    }
}
