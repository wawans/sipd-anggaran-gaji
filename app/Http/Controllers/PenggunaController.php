<?php

namespace App\Http\Controllers;

use App\Models\Pengguna;
use App\Models\Peran;
use App\Traits\MencatatAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class PenggunaController extends Controller
{
    use MencatatAudit;

    public function index(): Response
    {
        return Inertia::render('Pengguna/Index', [
            'data' => Pengguna::with('daftarPeran:id,name')->orderBy('nama')->paginate(20),
            'daftar_peran' => Peran::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:200',
            'username' => 'required|string|max:50|unique:pengguna,username',
            'kata_sandi' => 'required|string|min:6|confirmed',
            'aktif' => 'boolean',
            'peran' => 'required|array|min:1',
            'peran.*' => 'exists:peran,id',
        ], [
            'kata_sandi.confirmed' => 'Konfirmasi kata sandi tidak sama.',
        ]);

        $pengguna = Pengguna::create([
            'nama' => $data['nama'],
            'username' => $data['username'],
            'kata_sandi' => Hash::make($data['kata_sandi']),
            'aktif' => $data['aktif'] ?? true,
        ]);
        $pengguna->syncRoles(Peran::whereIn('id', $data['peran'])->pluck('name'));

        $this->catatAudit('buat', 'pengguna', $pengguna->id, "Pengguna {$pengguna->username}");

        return back()->with('sukses', 'Pengguna tersimpan.');
    }

    public function update(Request $request, Pengguna $pengguna)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:200',
            'username' => 'required|string|max:50|unique:pengguna,username,'.$pengguna->id,
            'kata_sandi' => 'nullable|string|min:6|confirmed',
            'aktif' => 'boolean',
            'peran' => 'required|array|min:1',
            'peran.*' => 'exists:peran,id',
        ], [
            'kata_sandi.confirmed' => 'Konfirmasi kata sandi tidak sama.',
        ]);

        if ($pengguna->id === auth()->id() && !($data['aktif'] ?? true)) {
            return back()->with('galat', 'Anda tidak bisa menonaktifkan akun sendiri.');
        }

        $namaPeran = Peran::whereIn('id', $data['peran'])->pluck('name');
        if ($this->satuSatunyaSuperadmin($pengguna) && !$namaPeran->contains('Superadmin')) {
            return back()->with('galat', 'Tidak bisa mencabut peran Superadmin: ini satu-satunya superadmin.');
        }

        $pengguna->update([
            'nama' => $data['nama'],
            'username' => $data['username'],
            'aktif' => $data['aktif'] ?? true,
        ]);

        if (!empty($data['kata_sandi'])) {
            $pengguna->update(['kata_sandi' => Hash::make($data['kata_sandi'])]);
        }

        $pengguna->syncRoles($namaPeran);
        $this->catatAudit('ubah', 'pengguna', $pengguna->id, "Pengguna {$pengguna->username}");

        return back()->with('sukses', 'Pengguna diperbarui.');
    }

    protected function satuSatunyaSuperadmin(Pengguna $pengguna): bool
    {
        if (!$pengguna->hasRole('Superadmin')) {
            return false;
        }

        return Peran::findByName('Superadmin')->users()->count() <= 1;
    }
}
