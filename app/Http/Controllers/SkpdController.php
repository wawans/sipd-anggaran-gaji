<?php

namespace App\Http\Controllers;

use App\Models\Skpd;
use App\Models\Anggaran;
use App\Models\Realisasi;
use App\Traits\MencatatAudit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SkpdController extends Controller
{
    use MencatatAudit;

    private function konfigurasi(): array
    {
        return [
            'judul' => 'SKPD',
            'rute' => 'skpd',
            'kolom' => [
                ['kunci' => 'kode', 'label' => 'Kode'],
                ['kunci' => 'nama', 'label' => 'Nama'],
                ['kunci' => 'aktif', 'label' => 'Status', 'tipe' => 'aktif'],
            ],
            'field' => [
                ['nama' => 'kode', 'label' => 'Kode', 'tipe' => 'teks', 'wajib' => true],
                ['nama' => 'nama', 'label' => 'Nama', 'tipe' => 'teks', 'wajib' => true],
                ['nama' => 'aktif', 'label' => 'Aktif', 'tipe' => 'cek'],
            ],
        ];
    }

    public function index(): Response
    {
        return Inertia::render('Master/Index', array_merge($this->konfigurasi(), [
            'data' => Skpd::orderBy('kode')->paginate(20),
        ]));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kode' => 'required|string|max:30|unique:skpd,kode',
            'nama' => 'required|string|max:200',
            'aktif' => 'boolean',
        ]);

        $skpd = Skpd::create($data);
        $this->catatAudit('buat', 'skpd', $skpd->id, "SKPD {$skpd->kode} - {$skpd->nama}");

        return back()->with('sukses', 'SKPD tersimpan.');
    }

    public function update(Request $request, Skpd $skpd)
    {
        $data = $request->validate([
            'kode' => 'required|string|max:30|unique:skpd,kode,'.$skpd->id,
            'nama' => 'required|string|max:200',
            'aktif' => 'boolean',
        ]);

        $skpd->update($data);
        $this->catatAudit('ubah', 'skpd', $skpd->id, "SKPD {$skpd->kode} - {$skpd->nama}");

        return back()->with('sukses', 'SKPD diperbarui.');
    }

    public function destroy(Skpd $skpd)
    {
        $dipakaiAnggaran = Anggaran::where('skpd_id', $skpd->id)->count();
        $dipakaiRealisasi = Realisasi::where('skpd_id', $skpd->id)->count();

        if ($dipakaiAnggaran > 0 || $dipakaiRealisasi > 0) {
            $rincian = [];
            if ($dipakaiAnggaran > 0) {
                $rincian[] = $dipakaiAnggaran.' baris anggaran';
            }
            if ($dipakaiRealisasi > 0) {
                $rincian[] = $dipakaiRealisasi.' baris realisasi';
            }

            return back()->with('galat', 'Tidak dapat menghapus SKPD '.$skpd->kode.' - '.$skpd->nama.' karena masih dipakai di '.implode(' dan ', $rincian).'.');
        }

        $keterangan = 'SKPD '.$skpd->kode.' - '.$skpd->nama;
        $id = $skpd->id;
        $skpd->delete();
        $this->catatAudit('hapus', 'skpd', $id, $keterangan);

        return back()->with('sukses', 'SKPD dihapus.');
    }
}
