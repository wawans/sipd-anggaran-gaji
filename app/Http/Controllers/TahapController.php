<?php

namespace App\Http\Controllers;

use App\Models\Anggaran;
use App\Models\JenisKebutuhan;
use App\Models\JenisKebutuhanTambahan;
use App\Models\Tahap;
use App\Traits\MencatatAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TahapController extends Controller
{
    use MencatatAudit;

    public function index(): Response
    {
        $tahap = Tahap::orderByDesc('tahun')->orderByDesc('tgl_mulai')->paginate(15);

        $ringkas = DB::table('v_tahap_ringkas')
            ->whereIn('tahap_id', $tahap->pluck('id'))
            ->pluck('total_anggaran', 'tahap_id');

        $jmlBaris = Anggaran::whereIn('tahap_id', $tahap->pluck('id'))
            ->selectRaw('tahap_id, count(*) as jml')
            ->groupBy('tahap_id')
            ->pluck('jml', 'tahap_id');

        $tahap->getCollection()->transform(function ($t) use ($ringkas, $jmlBaris) {
            $t->total_anggaran = (float) ($ringkas[$t->id] ?? 0);
            $t->jml_baris = (int) ($jmlBaris[$t->id] ?? 0);

            return $t;
        });

        return Inertia::render('Tahap/Index', [
            'data' => $tahap,
            'bisa_buat' => auth()->user()->punyaHakAkses('tahap.create'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tahun' => 'required|integer|min:2000|max:2100',
            'nama' => 'required|string|max:100',
            'tgl_mulai' => 'required|date',
            'tgl_selesai' => 'required|date|after_or_equal:tgl_mulai',
            'catatan' => 'nullable|string',
        ], [
            'tgl_selesai.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
        ]);

        $tahap = Tahap::create($data + ['status' => Tahap::STATUS_DRAF]);
        $this->catatAudit('buat', 'tahap', $tahap->id, "Tahap {$tahap->tahun} - {$tahap->nama}");

        return redirect("/tahap/{$tahap->id}")->with('sukses', 'Tahap dibuat.');
    }

    public function show(Tahap $tahap): Response
    {
        $ringkas = DB::table('v_tahap_ringkas')->where('tahap_id', $tahap->id)->first();

        return Inertia::render('Tahap/Tampil', [
            'tahap' => $tahap,
            'total_anggaran' => (float) ($ringkas->total_anggaran ?? 0),
            'jml_baris' => Anggaran::where('tahap_id', $tahap->id)->count(),
            'tahap_lain' => Tahap::where('id', '!=', $tahap->id)
                ->orderByDesc('tgl_mulai')->get(['id', 'tahun', 'nama', 'status']),
            'bisa_ubah' => auth()->user()->punyaHakAkses('tahap.edit') && !$tahap->terkunci(),
            'bisa_kunci' => auth()->user()->punyaHakAkses('tahap.lock') && !$tahap->terkunci(),
            'bisa_salin' => auth()->user()->punyaHakAkses('anggaran.salin') && !$tahap->terkunci(),
            'bisa_impor' => auth()->user()->punyaHakAkses('anggaran.edit') && !$tahap->terkunci(),
            'jenis_kebutuhan' => JenisKebutuhan::where('aktif', true)->orderBy('urutan')->get(['id', 'nama']),
            'jenis_tambahan' => JenisKebutuhanTambahan::where('aktif', true)->orderBy('urutan')->get(['id', 'nama']),
        ]);
    }

    public function update(Request $request, Tahap $tahap)
    {
        if ($tahap->terkunci()) {
            return back()->with('galat', 'Tahap yang terkunci tidak bisa diubah.');
        }

        $data = $request->validate([
            'tahun' => 'required|integer|min:2000|max:2100',
            'nama' => 'required|string|max:100',
            'tgl_mulai' => 'required|date',
            'tgl_selesai' => 'required|date|after_or_equal:tgl_mulai',
            'catatan' => 'nullable|string',
        ], [
            'tgl_selesai.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
        ]);

        $tahap->update($data);
        $this->catatAudit('ubah', 'tahap', $tahap->id, "Tahap {$tahap->tahun} - {$tahap->nama}");

        return back()->with('sukses', 'Tahap diperbarui.');
    }

    public function finalkan(Tahap $tahap)
    {
        if ($tahap->terkunci()) {
            return back()->with('galat', 'Tahap yang terkunci tidak bisa diubah.');
        }
        if ($tahap->status !== Tahap::STATUS_DRAF) {
            return back()->with('galat', 'Hanya tahap berstatus draf yang bisa difinalkan.');
        }

        $tahap->update(['status' => Tahap::STATUS_FINAL]);
        $this->catatAudit('finalkan', 'tahap', $tahap->id, "Tahap {$tahap->tahun} - {$tahap->nama} difinalkan");

        return back()->with('sukses', 'Tahap difinalkan.');
    }

    public function kunci(Tahap $tahap)
    {
        if ($tahap->terkunci()) {
            return back()->with('galat', 'Tahap sudah terkunci.');
        }

        $tahap->update(['status' => Tahap::STATUS_TERKUNCI]);
        $this->catatAudit('kunci', 'tahap', $tahap->id, "Tahap {$tahap->tahun} - {$tahap->nama} dikunci (disahkan)");

        return back()->with('sukses', 'Tahap dikunci. Anggaran tahap ini tidak bisa diubah lagi.');
    }
}
