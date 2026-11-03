<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $pengguna = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'pengguna' => $pengguna ? [
                    'id' => $pengguna->id,
                    'nama' => $pengguna->nama,
                    'username' => $pengguna->username,
                    'peran' => $pengguna->getRoleNames()->values(),
                ] : null,
                'hak_akses' => $pengguna ? $pengguna->getAllPermissions()->pluck('name')->values() : [],
            ],
            'flash' => [
                'sukses' => fn () => $request->session()->get('sukses'),
                'galat' => fn () => $request->session()->get('galat'),
            ],
        ];
    }
}
