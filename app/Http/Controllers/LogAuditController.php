<?php

namespace App\Http\Controllers;

use App\Models\LogAudit;
use App\Models\Pengguna;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LogAuditController extends Controller
{
    public function index(Request $request): Response
    {
        $query = LogAudit::with('pengguna:id,nama')->orderByDesc('waktu');

        if ($request->filled('aksi')) {
            $query->where('aksi', $request->aksi);
        }
        if ($request->filled('entitas')) {
            $query->where('entitas', $request->entitas);
        }
        if ($request->filled('pengguna_id')) {
            $query->where('pengguna_id', $request->pengguna_id);
        }
        if ($request->filled('dari')) {
            $query->whereDate('waktu', '>=', $request->dari);
        }
        if ($request->filled('sampai')) {
            $query->whereDate('waktu', '<=', $request->sampai);
        }

        return Inertia::render('Audit/Index', [
            'data' => $query->paginate(25)->withQueryString(),
            'filter' => $request->only(['aksi', 'entitas', 'pengguna_id', 'dari', 'sampai']),
            'daftar_aksi' => LogAudit::select('aksi')->distinct()->orderBy('aksi')->pluck('aksi'),
            'daftar_entitas' => LogAudit::select('entitas')->distinct()->orderBy('entitas')->pluck('entitas'),
            'daftar_pengguna' => Pengguna::orderBy('nama')->get(['id', 'nama']),
        ]);
    }
}
