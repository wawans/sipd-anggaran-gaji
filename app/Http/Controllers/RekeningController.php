<?php

namespace App\Http\Controllers;

use App\Models\Rekening;
use App\Models\Anggaran;
use App\Models\Realisasi;
use App\Models\MappingTemplateKolom;
use App\Traits\MencatatAudit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RekeningController extends Controller
{
    use MencatatAudit;

    private function konfigurasi(): array
    {
        return [
            'judul' => 'Rekening',
            'rute' => 'rekening',
            'kolom' => [
                ['kunci' => 'kode', 'label' => 'Kode'],
                ['kunci' => 'nama', 'label' => 'Nama'],
                ['kunci' => 'kelompok', 'label' => 'Kelompok'],
                ['kunci' => 'urutan', 'label' => 'Urutan'],
                ['kunci' => 'aktif', 'label' => 'Status', 'tipe' => 'aktif'],
            ],
            'field' => [
                ['nama' => 'kode', 'label' => 'Kode (mis. 1.1)', 'tipe' => 'teks', 'wajib' => true],
                ['nama' => 'nama', 'label' => 'Nama', 'tipe' => 'teks', 'wajib' => true],
                ['nama' => 'kelompok', 'label' => 'Kelompok (mis. 1)', 'tipe' => 'teks', 'wajib' => true],
                ['nama' => 'urutan', 'label' => 'Urutan tampil', 'tipe' => 'angka'],
                ['nama' => 'aktif', 'label' => 'Aktif', 'tipe' => 'cek'],
            ],
        ];
    }

    public function index(): Response
    {
        return Inertia::render('Master/Index', array_merge($this->konfigurasi(), [
            'data' => Rekening::orderBy('urutan')->orderBy('kode')->paginate(20),
        ]));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kode' => 'required|string|max:30|unique:rekening,kode',
            'nama' => 'required|string|max:200',
            'kelompok' => 'required|string|max:20',
            'urutan' => 'nullable|integer|min:0',
            'aktif' => 'boolean',
        ]);

        $rekening = Rekening::create($data);
        $this->catatAudit('buat', 'rekening', $rekening->id, "Rekening {$rekening->kode} - {$rekening->nama}");

        return back()->with('sukses', 'Rekening tersimpan.');
    }

    public function update(Request $request, Rekening $rekening)
    {
        $data = $request->validate([
            'kode' => 'required|string|max:30|unique:rekening,kode,'.$rekening->id,
            'nama' => 'required|string|max:200',
            'kelompok' => 'required|string|max:20',
            'urutan' => 'nullable|integer|min:0',
            'aktif' => 'boolean',
        ]);

        $rekening->update($data);
        $this->catatAudit('ubah', 'rekening', $rekening->id, "Rekening {$rekening->kode} - {$rekening->nama}");

        return back()->with('sukses', 'Rekening diperbarui.');
    }

    public function destroy(Rekening $rekening)
    {
        $dipakaiAnggaran = Anggaran::where('rekening_id', $rekening->id)->count();
        $dipakaiRealisasi = Realisasi::where('rekening_id', $rekening->id)->count();
        $dipakaiTemplate = MappingTemplateKolom::where('rekening_id', $rekening->id)->count();

        if ($dipakaiAnggaran > 0 || $dipakaiRealisasi > 0 || $dipakaiTemplate > 0) {
            $rincian = [];
            if ($dipakaiAnggaran > 0) {
                $rincian[] = $dipakaiAnggaran.' baris anggaran';
            }
            if ($dipakaiRealisasi > 0) {
                $rincian[] = $dipakaiRealisasi.' baris realisasi';
            }
            if ($dipakaiTemplate > 0) {
                $rincian[] = $dipakaiTemplate.' template mapping';
            }

            return back()->with('galat', 'Tidak dapat menghapus rekening '.$rekening->kode.' - '.$rekening->nama.' karena masih dipakai di '.implode(', ', $rincian).'.');
        }

        $keterangan = 'Rekening '.$rekening->kode.' - '.$rekening->nama;
        $id = $rekening->id;
        $rekening->delete();
        $this->catatAudit('hapus', 'rekening', $id, $keterangan);

        return back()->with('sukses', 'Rekening dihapus.');
    }
}
