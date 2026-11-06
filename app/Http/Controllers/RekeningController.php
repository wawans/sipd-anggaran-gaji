<?php

namespace App\Http\Controllers;

use App\Exports\MasterExport;
use App\Models\Rekening;
use App\Models\Anggaran;
use App\Models\Realisasi;
use App\Models\MappingTemplateKolom;
use App\Traits\MencatatAudit;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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
                ['kunci' => 'induk_tampil', 'label' => 'Induk'],
                ['kunci' => 'tingkat', 'label' => 'Tingkat'],
                ['kunci' => 'urutan', 'label' => 'Urutan'],
                ['kunci' => 'aktif', 'label' => 'Status', 'tipe' => 'aktif'],
            ],
            'field' => [
                ['nama' => 'kode', 'label' => 'Kode (mis. 1.1)', 'tipe' => 'teks', 'wajib' => true],
                ['nama' => 'nama', 'label' => 'Nama', 'tipe' => 'teks', 'wajib' => true],
                ['nama' => 'parent_rekening_id', 'label' => 'Rekening induk (kosongkan bila top-level)', 'tipe' => 'induk'],
                ['nama' => 'urutan', 'label' => 'Urutan tampil', 'tipe' => 'angka'],
                ['nama' => 'aktif', 'label' => 'Aktif', 'tipe' => 'cek'],
            ],
        ];
    }

    public function index(): Response
    {
        return Inertia::render('Master/Index', array_merge($this->konfigurasi(), [
            'data' => Rekening::with('induk:id,kode,nama')->orderBy('urutan')->orderBy('kode')->paginate(20),
            'semua_rekening' => Rekening::orderBy('kode')->get(['id', 'kode', 'nama', 'parent_rekening_id']),
        ]));
    }

    public function ekspor(): BinaryFileResponse
    {
        $baris = Rekening::with('induk:id,kode,nama')->orderBy('urutan')->orderBy('kode')->get()->map(fn ($r) => [
            $r->kode,
            $r->nama,
            $r->induk ? $r->induk->kode : '-',
            $r->tingkat,
            $r->urutan ?? 0,
            $r->aktif ? 'Aktif' : 'Nonaktif',
        ])->all();

        return Excel::download(
            new MasterExport('Rekening', ['Kode', 'Nama', 'Kode Induk', 'Tingkat', 'Urutan', 'Status'], $baris),
            'master-rekening-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kode' => 'required|string|max:30|unique:rekening,kode',
            'nama' => 'required|string|max:200',
            'parent_rekening_id' => 'nullable|exists:rekening,id',
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
            'parent_rekening_id' => 'nullable|exists:rekening,id',
            'urutan' => 'nullable|integer|min:0',
            'aktif' => 'boolean',
        ]);

        if ($pesan = $this->cekIndukValid($rekening, $data['parent_rekening_id'] ?? null)) {
            return back()->withErrors(['parent_rekening_id' => $pesan]);
        }

        $rekening->update($data);
        $this->catatAudit('ubah', 'rekening', $rekening->id, "Rekening {$rekening->kode} - {$rekening->nama}");

        return back()->with('sukses', 'Rekening diperbarui.');
    }

    /**
     * Pastikan calon induk bukan diri sendiri dan bukan keturunannya
     * (mencegah siklus A -> B -> A). Mengembalikan pesan galat atau null.
     */
    private function cekIndukValid(Rekening $rekening, $parentId): ?string
    {
        if (!$parentId) {
            return null;
        }
        if ((int) $parentId === (int) $rekening->id) {
            return 'Rekening induk tidak boleh diri sendiri.';
        }
        // Telusuri rantai ancestor calon induk; bila bertemu id sendiri -> siklus.
        $cursor = (int) $parentId;
        while ($cursor) {
            if ($cursor === (int) $rekening->id) {
                return 'Rekening induk tidak boleh sub-rekening (keturunan) dari rekening ini karena akan membentuk siklus.';
            }
            $cursor = Rekening::where('id', $cursor)->value('parent_rekening_id');
            $cursor = $cursor ? (int) $cursor : 0;
        }

        return null;
    }

    public function destroy(Rekening $rekening)
    {
        $jmlAnak = Rekening::where('parent_rekening_id', $rekening->id)->count();
        if ($jmlAnak > 0) {
            return back()->with('galat', 'Tidak dapat menghapus rekening '.$rekening->kode.' - '.$rekening->nama.' karena memiliki '.$jmlAnak.' sub-rekening. Hapus atau pindahkan sub-rekening terlebih dahulu.');
        }

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
