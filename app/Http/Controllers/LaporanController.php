<?php

namespace App\Http\Controllers;

use App\Exports\LaporanExport;
use App\Models\Tahap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LaporanController extends Controller
{
    public function index(Request $request): Response
    {
        $daftarTahap = Tahap::orderByDesc('tgl_mulai')->get(['id', 'tahun', 'nama', 'status']);
        $tahapId = $request->input('tahap_id') ?? $daftarTahap->first()?->id;
        $tahap = $tahapId ? Tahap::find($tahapId) : null;

        $laporan = null;
        if ($tahap) {
            $laporan = $this->susunLaporan($tahap);
        }

        return Inertia::render('Laporan/Index', [
            'daftar_tahap' => $daftarTahap,
            'tahap_aktif' => $tahap,
            'laporan' => $laporan,
        ]);
    }

    public function ekspor(Request $request): BinaryFileResponse
    {
        $tahap = Tahap::findOrFail($request->input('tahap_id'));

        return Excel::download(
            new LaporanExport($tahap, $this->susunLaporan($tahap)),
            'laporan-'.$tahap->tahun.'-'.str($tahap->nama)->slug().'.xlsx'
        );
    }

    /** @return array{ringkas: array, per_skpd: array, per_rekening: array} */
    public function susunLaporan(Tahap $tahap): array
    {
        $ringkas = DB::table('v_tahap_ringkas')->where('tahap_id', $tahap->id)->first();
        $totalRealisasi = (float) DB::table('v_realisasi_tahap')->where('tahap_id', $tahap->id)->sum('total_realisasi');

        $perSkpd = DB::table('v_anggaran_baris as v')
            ->join('skpd as s', 's.id', '=', 'v.skpd_id')
            ->leftJoin('v_realisasi_tahap as r', function ($j) {
                $j->on('r.tahap_id', '=', 'v.tahap_id')
                    ->on('r.skpd_id', '=', 'v.skpd_id')
                    ->on('r.rekening_id', '=', 'v.rekening_id');
            })
            ->where('v.tahap_id', $tahap->id)
            ->groupBy('s.id', 's.kode', 's.nama')
            ->select('s.kode', 's.nama')
            ->selectRaw('SUM(v.total_pagu) as total_anggaran')
            ->selectRaw('COALESCE(SUM(r.total_realisasi), 0) as total_realisasi')
            ->orderBy('s.kode')
            ->get()
            ->map(fn ($r) => [
                'kode' => $r->kode,
                'nama' => $r->nama,
                'total_anggaran' => (float) $r->total_anggaran,
                'total_realisasi' => (float) $r->total_realisasi,
                'selisih' => (float) $r->total_anggaran - (float) $r->total_realisasi,
            ])->all();

        // Rollup hierarki: tiap rekening induk mencakup nilainya sendiri + seluruh keturunannya
        // (via v_rekening_leluhur); rekening tanpa anak berperilaku seperti sebelumnya.
        $perRekening = DB::table('v_anggaran_baris as v')
            ->join('v_rekening_leluhur as h', 'h.rekening_id', '=', 'v.rekening_id')
            ->join('rekening as r2', 'r2.id', '=', 'h.leluhur_id')
            ->leftJoin('v_realisasi_tahap as r', function ($j) {
                $j->on('r.tahap_id', '=', 'v.tahap_id')
                    ->on('r.skpd_id', '=', 'v.skpd_id')
                    ->on('r.rekening_id', '=', 'v.rekening_id');
            })
            ->where('v.tahap_id', $tahap->id)
            ->groupBy('r2.id', 'r2.kode', 'r2.nama', 'r2.urutan', 'r2.tingkat')
            ->select('r2.kode', 'r2.nama', 'r2.tingkat')
            ->selectRaw('SUM(v.total_pagu) as total_anggaran')
            ->selectRaw('COALESCE(SUM(r.total_realisasi), 0) as total_realisasi')
            ->orderBy('r2.urutan')->orderBy('r2.kode')
            ->get()
            ->map(fn ($r) => [
                'kode' => $r->kode,
                'nama' => $r->nama,
                'total_anggaran' => (float) $r->total_anggaran,
                'total_realisasi' => (float) $r->total_realisasi,
                'selisih' => (float) $r->total_anggaran - (float) $r->total_realisasi,
            ])->all();

        $totalAnggaran = (float) ($ringkas->total_anggaran ?? 0);

        return [
            'ringkas' => [
                'total_anggaran' => $totalAnggaran,
                'total_realisasi' => $totalRealisasi,
                'selisih' => $totalAnggaran - $totalRealisasi,
            ],
            'per_skpd' => $perSkpd,
            'per_rekening' => $perRekening,
        ];
    }
}
