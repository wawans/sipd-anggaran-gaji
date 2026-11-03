<?php

namespace App\Http\Controllers;

use App\Models\JenisKebutuhanTambahan;
use App\Traits\MencatatAudit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JenisKebutuhanTambahanController extends Controller
{
    use MencatatAudit;

    private function konfigurasi(): array
    {
        return [
            'judul' => 'Jenis Kebutuhan Tambahan',
            'rute' => 'jenis-kebutuhan-tambahan',
            'kolom' => [
                ['kunci' => 'nama', 'label' => 'Nama'],
                ['kunci' => 'urutan', 'label' => 'Urutan'],
                ['kunci' => 'aktif', 'label' => 'Status', 'tipe' => 'aktif'],
            ],
            'field' => [
                ['nama' => 'nama', 'label' => 'Nama (mis. Accress)', 'tipe' => 'teks', 'wajib' => true],
                ['nama' => 'urutan', 'label' => 'Urutan tampil', 'tipe' => 'angka'],
                ['nama' => 'aktif', 'label' => 'Aktif', 'tipe' => 'cek'],
            ],
        ];
    }

    public function index(): Response
    {
        return Inertia::render('Master/Index', array_merge($this->konfigurasi(), [
            'data' => JenisKebutuhanTambahan::orderBy('urutan')->orderBy('nama')->paginate(20),
        ]));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:100|unique:jenis_kebutuhan_tambahan,nama',
            'urutan' => 'nullable|integer|min:0',
            'aktif' => 'boolean',
        ]);

        $jenis = JenisKebutuhanTambahan::create($data);
        $this->catatAudit('buat', 'jenis_kebutuhan_tambahan', $jenis->id, "Jenis kebutuhan tambahan {$jenis->nama}");

        return back()->with('sukses', 'Jenis kebutuhan tambahan tersimpan.');
    }

    public function update(Request $request, JenisKebutuhanTambahan $jenisKebutuhanTambahan)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:100|unique:jenis_kebutuhan_tambahan,nama,'.$jenisKebutuhanTambahan->id,
            'urutan' => 'nullable|integer|min:0',
            'aktif' => 'boolean',
        ]);

        $jenisKebutuhanTambahan->update($data);
        $this->catatAudit('ubah', 'jenis_kebutuhan_tambahan', $jenisKebutuhanTambahan->id, "Jenis kebutuhan tambahan {$jenisKebutuhanTambahan->nama}");

        return back()->with('sukses', 'Jenis kebutuhan tambahan diperbarui.');
    }
}
