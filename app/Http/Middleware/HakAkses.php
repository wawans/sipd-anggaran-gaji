<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HakAkses
{
    /**
     * Pastikan pengguna memiliki hak akses (kode, mis. anggaran.edit).
     * Dipakai sebagai middleware route: ->middleware('hak_akses:anggaran.edit')
     */
    public function handle(Request $request, Closure $next, string $kode): Response
    {
        $pengguna = $request->user();

        if (!$pengguna || !$pengguna->aktif || !$pengguna->punyaHakAkses($kode)) {
            abort(403, 'Anda tidak memiliki hak akses: '.$kode);
        }

        return $next($request);
    }
}
