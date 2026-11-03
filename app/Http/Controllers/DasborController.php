<?php

namespace App\Http\Controllers;

use App\Models\LogAudit;
use App\Models\Skpd;
use App\Models\Tahap;
use Inertia\Inertia;
use Inertia\Response;

class DasborController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Dasbor', [
            'ringkas' => [
                'jml_skpd' => Skpd::where('aktif', true)->count(),
                'jml_tahap' => Tahap::count(),
                'jml_tahap_terkunci' => Tahap::where('status', 'terkunci')->count(),
            ],
            'tahap_terbaru' => Tahap::orderByDesc('tgl_mulai')->limit(5)
                ->get(['id', 'tahun', 'nama', 'status', 'tgl_mulai', 'tgl_selesai']),
            'audit_terbaru' => LogAudit::with('pengguna:id,nama')
                ->orderByDesc('waktu')->limit(8)
                ->get(['id', 'pengguna_id', 'waktu', 'aksi', 'entitas', 'keterangan']),
        ]);
    }
}
