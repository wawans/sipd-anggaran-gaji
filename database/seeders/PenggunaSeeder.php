<?php

namespace Database\Seeders;

use App\Models\Pengguna;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PenggunaSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Pengguna::firstOrCreate(
            ['username' => 'admin'],
            ['nama' => 'Administrator', 'kata_sandi' => Hash::make('admin123'), 'aktif' => true],
        );
        $admin->syncRoles(['Superadmin']);
    }
}
