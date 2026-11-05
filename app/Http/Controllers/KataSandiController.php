<?php

namespace App\Http\Controllers;

use App\Traits\MencatatAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class KataSandiController extends Controller
{
    use MencatatAudit;

    public function edit(): Response
    {
        return Inertia::render('Profil/KataSandi');
    }

    public function update(Request $request)
    {
        // WAJIB: hanya user yang sedang login yang boleh diubah.
        // Jangan pernah membaca id user dari input/body/query di sini.
        $pengguna = $request->user();

        $data = $request->validate([
            'kata_sandi_lama' => 'required|string',
            'kata_sandi' => 'required|string|min:6|confirmed',
        ], [
            'kata_sandi_lama.required' => 'Kata sandi lama wajib diisi.',
            'kata_sandi.required' => 'Kata sandi baru wajib diisi.',
            'kata_sandi.min' => 'Kata sandi baru minimal 6 karakter.',
            'kata_sandi.confirmed' => 'Konfirmasi kata sandi tidak sama.',
        ]);

        if (!Hash::check($data['kata_sandi_lama'], $pengguna->kata_sandi)) {
            return back()->withErrors(['kata_sandi_lama' => 'Kata sandi lama salah.']);
        }

        if (Hash::check($data['kata_sandi'], $pengguna->kata_sandi)) {
            return back()->withErrors(['kata_sandi' => 'Kata sandi baru tidak boleh sama dengan kata sandi lama.']);
        }

        $pengguna->update(['kata_sandi' => Hash::make($data['kata_sandi'])]);
        $this->catatAudit('ubah', 'pengguna', $pengguna->id, "Ubah kata sandi sendiri ({$pengguna->username})");

        return back()->with('sukses', 'Kata sandi berhasil diubah.');
    }
}
