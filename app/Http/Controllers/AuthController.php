<?php

namespace App\Http\Controllers;

use App\Traits\MencatatAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    use MencatatAudit;

    public function tampilMasuk(): Response
    {
        return Inertia::render('Auth/Masuk');
    }

    public function masuk(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string',
            'kata_sandi' => 'required|string',
        ], [
            'username.required' => 'Username wajib diisi.',
            'kata_sandi.required' => 'Kata sandi wajib diisi.',
        ]);

        $ingat = $request->boolean('ingat');

        if (!Auth::attempt(['username' => $data['username'], 'password' => $data['kata_sandi']], $ingat)) {
            return back()->withErrors(['username' => 'Username atau kata sandi salah.']);
        }

        $pengguna = Auth::user();

        if (!$pengguna->aktif) {
            Auth::logout();

            return back()->withErrors(['username' => 'Akun Anda dinonaktifkan. Hubungi administrator.']);
        }

        $request->session()->regenerate();
        $this->catatAudit('login', 'pengguna', $pengguna->id, "Masuk aplikasi ({$pengguna->username})");

        return redirect()->intended('/dasbor');
    }

    public function keluar(Request $request)
    {
        $this->catatAudit('logout', 'pengguna', Auth::id(), 'Keluar aplikasi');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/masuk');
    }
}
