<?php

namespace App\Http\Controllers;

use App\Models\Anggaran;
use App\Models\AnggaranKebutuhan;
use App\Models\AnggaranKebutuhanTambahan;
use App\Models\JenisKebutuhan;
use App\Models\JenisKebutuhanTambahan;
use App\Models\Rekening;
use App\Models\Skpd;
use App\Models\Tahap;
use App\Traits\MencatatAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AnggaranController extends Controller
{
    use MencatatAudit;

    /**
     * Ringkasan matriks: tabel semua SKPD + total pagu masing-masing di tahap ini.
     * Nama SKPD bisa diklik untuk melihat sub-tabel rincian rekening (read-only).
     */
    public function index(Tahap $tahap): Response
    {
        $daftarSkpd = Skpd::orderBy('kode')->get(['id', 'kode', 'nama']);

        $hitung = DB::table('v_anggaran_baris as v')
            ->join('rekening as r', 'r.id', '=', 'v.rekening_id')
            ->where('v.tahap_id', $tahap->id)
            ->orderBy('r.urutan')->orderBy('r.kode')
            ->select('v.*', 'r.kode as rekening_kode', 'r.nama as rekening_nama')
            ->get()
            ->groupBy('skpd_id');

        $ringkasan = $daftarSkpd->map(function ($s) use ($hitung) {
            $rows = $hitung[$s->id] ?? collect();

            return [
                'id' => $s->id,
                'kode' => $s->kode,
                'nama' => $s->nama,
                'jml_baris' => $rows->count(),
                'total_pagu' => (float) $rows->sum('total_pagu'),
                'rincian' => $rows->map(fn ($h) => [
                    'id' => $h->id,
                    'rekening_kode' => $h->rekening_kode,
                    'rekening_nama' => $h->rekening_nama,
                    'nilai' => (float) $h->nilai,
                    'volume' => (float) $h->volume,
                    'volume_nol' => ((float) $h->volume) == 0,
                    'total_pagu' => (float) $h->total_pagu,
                ])->values(),
            ];
        });

        $pengguna = auth()->user();

        return Inertia::render('Tahap/Anggaran', [
            'tahap' => $tahap,
            'ringkasan_skpd' => $ringkasan,
            'total_tahap' => (float) $ringkasan->sum('total_pagu'),
            'bisa_ubah' => $pengguna->punyaHakAkses('anggaran.edit') && !$tahap->terkunci(),
        ]);
    }

    /**
     * Layar input matriks untuk satu SKPD saja.
     */
    public function matriksSkpd(Tahap $tahap, Skpd $skpd): Response
    {
        $daftar = Anggaran::with(['rekening:id,kode,nama', 'kebutuhan', 'kebutuhanTambahan'])
            ->where('tahap_id', $tahap->id)
            ->where('skpd_id', $skpd->id)
            ->join('rekening as r', 'r.id', '=', 'anggaran.rekening_id')
            ->orderBy('r.urutan')->orderBy('r.kode')
            ->select('anggaran.*')
            ->get();

        $hitung = DB::table('v_anggaran_baris')
            ->where('tahap_id', $tahap->id)
            ->where('skpd_id', $skpd->id)
            ->get()->keyBy('id');

        $baris = $daftar->map(fn ($a) => $this->formatBaris($a, $hitung[$a->id] ?? null, $skpd));

        $sudahAda = $daftar->pluck('rekening_id')->all();
        $qRek = Rekening::where('aktif', true)->orderBy('urutan')->orderBy('kode');
        if ($sudahAda) {
            $qRek->whereNotIn('id', $sudahAda);
        }

        $pengguna = auth()->user();

        return Inertia::render('Tahap/MatriksSkpd', [
            'tahap' => $tahap,
            'skpd' => ['id' => $skpd->id, 'kode' => $skpd->kode, 'nama' => $skpd->nama],
            'baris' => $baris,
            'total_skpd' => (float) $baris->sum('total_pagu'),
            'rekening' => $qRek->get(['id', 'kode', 'nama']),
            'jenis_kebutuhan' => JenisKebutuhan::where('aktif', true)->orderBy('urutan')->get(['id', 'nama']),
            'jenis_tambahan' => JenisKebutuhanTambahan::where('aktif', true)->orderBy('urutan')->get(['id', 'nama']),
            'bisa_ubah' => $pengguna->punyaHakAkses('anggaran.edit') && !$tahap->terkunci(),
        ]);
    }

    protected function formatBaris(Anggaran $a, $h, Skpd $skpd): array
    {
        return [
            'id' => $a->id,
            'skpd_kode' => $skpd->kode,
            'skpd_nama' => $skpd->nama,
            'rekening_kode' => $a->rekening->kode,
            'rekening_nama' => $a->rekening->nama,
            'nilai' => (float) $a->nilai,
            'volume' => (float) $a->volume,
            'kebutuhan' => $a->kebutuhan->mapWithKeys(fn ($k) => [$k->jenis_kebutuhan_id => (float) $k->nominal]),
            'tambahan' => $a->kebutuhanTambahan->mapWithKeys(fn ($k) => [$k->jenis_kebutuhan_tambahan_id => (float) $k->nominal]),
            'kebutuhan_bulan' => (float) ($h->kebutuhan_bulan ?? 0),
            'total_kebutuhan' => (float) ($h->total_kebutuhan ?? 0),
            'total_kebutuhan_tambahan' => (float) ($h->total_kebutuhan_tambahan ?? 0),
            'total_pagu' => (float) ($h->total_pagu ?? 0),
            'volume_nol' => ((float) $a->volume) == 0,
        ];
    }

    public function store(Request $request, Tahap $tahap)
    {
        if ($tahap->terkunci()) {
            return back()->with('galat', 'Tahap terkunci: anggaran tidak bisa ditambah.');
        }

        $data = $request->validate([
            'skpd_id' => 'required|exists:skpd,id',
            'rekening_id' => 'required|exists:rekening,id',
            'nilai' => 'required|numeric|min:0',
            'volume' => 'required|numeric|min:0',
        ]);

        $ada = Anggaran::where('tahap_id', $tahap->id)
            ->where('skpd_id', $data['skpd_id'])
            ->where('rekening_id', $data['rekening_id'])
            ->exists();

        if ($ada) {
            return back()->with('galat', 'Baris SKPD × rekening ini sudah ada di tahap ini.');
        }

        $anggaran = Anggaran::create($data + ['tahap_id' => $tahap->id]);
        $this->simpanRincian($anggaran, $request);
        $this->catatAudit('buat', 'anggaran', $anggaran->id, "Baris anggaran tahap {$tahap->nama}");

        return back()->with('sukses', 'Baris anggaran ditambahkan.');
    }

    public function update(Request $request, Anggaran $anggaran)
    {
        if ($anggaran->tahap->terkunci()) {
            return back()->with('galat', 'Tahap terkunci: anggaran tidak bisa diubah.');
        }

        $data = $request->validate([
            'nilai' => 'required|numeric|min:0',
            'volume' => 'required|numeric|min:0',
            'kebutuhan' => 'nullable|array',
            'kebutuhan.*.jenis_kebutuhan_id' => 'required|exists:jenis_kebutuhan,id',
            'kebutuhan.*.nominal' => 'required|numeric|min:0',
            'tambahan' => 'nullable|array',
            'tambahan.*.jenis_kebutuhan_tambahan_id' => 'required|exists:jenis_kebutuhan_tambahan,id',
            'tambahan.*.nominal' => 'required|numeric|min:0',
        ]);

        $anggaran->update(['nilai' => $data['nilai'], 'volume' => $data['volume']]);
        $this->simpanRincian($anggaran, $request);
        $this->catatAudit('ubah', 'anggaran', $anggaran->id, "Baris anggaran #{$anggaran->id} tahap {$anggaran->tahap->nama}");

        return back()->with('sukses', 'Baris anggaran diperbarui.');
    }

    public function destroy(Anggaran $anggaran)
    {
        if ($anggaran->tahap->terkunci()) {
            return back()->with('galat', 'Tahap terkunci: anggaran tidak bisa dihapus.');
        }

        $id = $anggaran->id;
        $namaTahap = $anggaran->tahap->nama;
        $anggaran->delete();
        $this->catatAudit('hapus', 'anggaran', $id, "Baris anggaran #{$id} tahap {$namaTahap}");

        return back()->with('sukses', 'Baris anggaran dihapus.');
    }

    /**
     * Salin seluruh baris anggaran (beserta rinciannya) dari tahap sumber
     * ke tahap tujuan. Baris yang sudah ada di tujuan dilewati.
     */
    public function salin(Request $request, Tahap $tahap)
    {
        if ($tahap->terkunci()) {
            return back()->with('galat', 'Tahap terkunci: tidak bisa menerima salinan.');
        }

        $data = $request->validate([
            'sumber_id' => 'required|exists:tahap,id',
        ]);

        if ((int) $data['sumber_id'] === $tahap->id) {
            return back()->with('galat', 'Tahap sumber dan tujuan tidak boleh sama.');
        }

        $sumber = Tahap::findOrFail($data['sumber_id']);
        $disalin = 0;
        $dilewati = 0;

        DB::transaction(function () use ($sumber, $tahap, &$disalin, &$dilewati) {
            $sumber->load('anggaran.kebutuhan', 'anggaran.kebutuhanTambahan');

            foreach ($sumber->anggaran as $src) {
                $sudahAda = Anggaran::where('tahap_id', $tahap->id)
                    ->where('skpd_id', $src->skpd_id)
                    ->where('rekening_id', $src->rekening_id)
                    ->exists();

                if ($sudahAda) {
                    $dilewati++;

                    continue;
                }

                $baru = Anggaran::create([
                    'tahap_id' => $tahap->id,
                    'skpd_id' => $src->skpd_id,
                    'rekening_id' => $src->rekening_id,
                    'nilai' => $src->nilai,
                    'volume' => $src->volume,
                ]);

                foreach ($src->kebutuhan as $k) {
                    AnggaranKebutuhan::create([
                        'anggaran_id' => $baru->id,
                        'jenis_kebutuhan_id' => $k->jenis_kebutuhan_id,
                        'nominal' => $k->nominal,
                    ]);
                }

                foreach ($src->kebutuhanTambahan as $k) {
                    AnggaranKebutuhanTambahan::create([
                        'anggaran_id' => $baru->id,
                        'jenis_kebutuhan_tambahan_id' => $k->jenis_kebutuhan_tambahan_id,
                        'nominal' => $k->nominal,
                    ]);
                }

                $disalin++;
            }
        });

        $this->catatAudit('salin', 'anggaran', null, "Salin {$disalin} baris dari tahap {$sumber->nama} ke {$tahap->nama} ({$dilewati} dilewati)");

        return back()->with('sukses', "Disalin {$disalin} baris dari tahap {$sumber->nama}. {$dilewati} baris dilewati (sudah ada).");
    }

    /**
     * Simpan rincian kebutuhan & kebutuhan tambahan: nominal > 0 di-upsert,
     * nominal 0 dihapus agar tabel tetap bersih.
     */
    protected function simpanRincian(Anggaran $anggaran, Request $request): void
    {
        foreach ($request->input('kebutuhan', []) as $row) {
            $nominal = (float) ($row['nominal'] ?? 0);
            $kunci = ['anggaran_id' => $anggaran->id, 'jenis_kebutuhan_id' => $row['jenis_kebutuhan_id']];

            if ($nominal > 0) {
                AnggaranKebutuhan::updateOrCreate($kunci, ['nominal' => $nominal]);
            } else {
                AnggaranKebutuhan::where($kunci)->delete();
            }
        }

        foreach ($request->input('tambahan', []) as $row) {
            $nominal = (float) ($row['nominal'] ?? 0);
            $kunci = ['anggaran_id' => $anggaran->id, 'jenis_kebutuhan_tambahan_id' => $row['jenis_kebutuhan_tambahan_id']];

            if ($nominal > 0) {
                AnggaranKebutuhanTambahan::updateOrCreate($kunci, ['nominal' => $nominal]);
            } else {
                AnggaranKebutuhanTambahan::where($kunci)->delete();
            }
        }
    }
}
