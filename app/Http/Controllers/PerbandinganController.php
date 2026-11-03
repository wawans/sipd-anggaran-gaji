<?php

namespace App\Http\Controllers;

use App\Models\Tahap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PerbandinganController extends Controller
{
    public function index(Request $request): Response
    {
        $daftar = Tahap::orderByDesc('tgl_mulai')->get(['id', 'tahun', 'nama', 'status', 'tgl_mulai', 'tgl_selesai']);
        $dipilih = array_values(array_unique(array_map('intval', (array) $request->input('tahap', []))));

        $hasil = null;
        if (count($dipilih) >= 2) {
            $hasil = $this->bandingkan($dipilih);
        }

        return Inertia::render('Perbandingan/Index', [
            'daftar_tahap' => $daftar,
            'dipilih' => $dipilih,
            'hasil' => $hasil,
        ]);
    }

    protected function bandingkan(array $ids): array
    {
        $tahaps = Tahap::whereIn('id', $ids)->orderBy('tgl_mulai')->get();

        $ringkas = DB::table('v_tahap_ringkas')->whereIn('tahap_id', $ids)->get()->keyBy('tahap_id');
        $realisasi = DB::table('v_realisasi_tahap')
            ->whereIn('tahap_id', $ids)
            ->selectRaw('tahap_id, SUM(total_realisasi) as total')
            ->groupBy('tahap_id')
            ->pluck('total', 'tahap_id');

        $perTahap = $tahaps->map(function ($t) use ($ringkas, $realisasi) {
            $anggaran = (float) ($ringkas[$t->id]->total_anggaran ?? 0);
            $real = (float) ($realisasi[$t->id] ?? 0);

            return [
                'id' => $t->id,
                'label' => $t->tahun.' - '.$t->nama,
                'status' => $t->status,
                'total_anggaran' => $anggaran,
                'total_realisasi' => $real,
                'selisih' => $anggaran - $real,
            ];
        })->all();

        // Matriks per SKPD × tahap (total pagu)
        $sel = DB::table('v_anggaran_baris as v')
            ->join('skpd as s', 's.id', '=', 'v.skpd_id')
            ->whereIn('v.tahap_id', $ids)
            ->groupBy('s.id', 's.kode', 's.nama', 'v.tahap_id')
            ->select('s.kode', 's.nama', 'v.tahap_id')
            ->selectRaw('SUM(v.total_pagu) as total')
            ->get();

        $matriks = [];
        foreach ($sel as $row) {
            $kunci = $row->kode.'|'.$row->nama;
            $matriks[$kunci] ??= ['kode' => $row->kode, 'nama' => $row->nama, 'nilai' => []];
            $matriks[$kunci]['nilai'][$row->tahap_id] = (float) $row->total;
        }

        return [
            'tahap' => $perTahap,
            'matriks_skpd' => array_values($matriks),
        ];
    }
}
