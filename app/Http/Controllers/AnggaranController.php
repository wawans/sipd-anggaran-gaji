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

    public function index(Tahap $tahap): Response
    {
        $daftar = Anggaran::with(['skpd:id,kode,nama', 'rekening:id,kode,nama', 'kebutuhan', 'kebutuhanTambahan'])
            ->where('tahap_id', $tahap->id)
            ->join('skpd as s', 's.id', '=', 'anggaran.skpd_id')
            ->join('rekening as r', 'r.id', '=', 'anggaran.rekening_id')
            ->orderBy('s.kode')->orderBy('r.urutan')->orderBy('r.kode')
            ->select('anggaran.*')
            ->get();

        $hitung = DB::table('v_anggaran_baris')->where('tahap_id', $tahap->id)->get()->keyBy('id');

        $baris = $daftar->map(function ($a) use ($hitung) {
            $h = $hitung[$a->id] ?? null;

            return [
                'id' => $a->id,
                'skpd_kode' => $a->skpd->kode,
                'skpd_nama' => $a->skpd->nama,
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
        });

        $pengguna = auth()->user();

        return Inertia::render('Tahap/Anggaran', [
            'tahap' => $tahap,
            'baris' => $baris,
            'total_tahap' => (float) $baris->sum('total_pagu'),
            'skpd' => Skpd::where('aktif', true)->orderBy('kode')->get(['id', 'kode', 'nama']),
            'rekening' => Rekening::where('aktif', true)->orderBy('urutan')->orderBy('kode')->get(['id', 'kode', 'nama']),
            'jenis_kebutuhan' => JenisKebutuhan::where('aktif', true)->orderBy('urutan')->get(['id', 'nama']),
            'jenis_tambahan' => JenisKebutuhanTambahan::where('aktif', true)->orderBy('urutan')->get(['id', 'nama']),
            'bisa_ubah' => $pengguna->punyaHakAkses('anggaran.edit') && !$tahap->terkunci(),
        ]);
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
