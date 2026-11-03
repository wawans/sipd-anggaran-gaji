<?php

namespace App\Http\Controllers;

use App\Models\HakAkses;
use App\Models\Peran;
use App\Traits\MencatatAudit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PeranController extends Controller
{
    use MencatatAudit;

    public function index(): Response
    {
        $peran = Peran::orderBy('name')->get()->map(function ($p) {
            return [
                'id' => $p->id,
                'nama' => $p->name,
                'jml_hak' => $p->permissions->count(),
                'jml_pengguna' => $p->users()->count(),
            ];
        });

        return Inertia::render('Peran/Index', ['data' => $peran]);
    }

    public function edit(Peran $peran): Response
    {
        $semua = HakAkses::orderBy('name')->get(['id', 'name', 'nama']);

        return Inertia::render('Peran/Ubah', [
            'peran' => ['id' => $peran->id, 'nama' => $peran->name],
            'hak_milik' => $peran->permissions->pluck('name')->values(),
            'semua_hak' => $semua->map(fn ($h) => ['kode' => $h->name, 'nama' => $h->nama]),
        ]);
    }

    public function update(Request $request, Peran $peran)
    {
        if ($peran->name === 'Superadmin') {
            return back()->with('galat', 'Peran Superadmin selalu memiliki semua hak akses.');
        }

        $data = $request->validate([
            'hak_akses' => 'nullable|array',
            'hak_akses.*' => 'exists:hak_akses,name',
        ]);

        $peran->syncPermissions($data['hak_akses'] ?? []);
        $this->catatAudit('ubah', 'peran', $peran->id, "Hak akses peran {$peran->name} diperbarui");

        return back()->with('sukses', 'Hak akses peran diperbarui.');
    }
}
