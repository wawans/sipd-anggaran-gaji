<?php

namespace App\Http\Controllers;

use App\Models\JenisKebutuhan;
use App\Traits\MencatatAudit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JenisKebutuhanController extends Controller
{
    use MencatatAudit;

    private function konfigurasi(): array
    {
        return [
            'judul' => 'Jenis Kebutuhan',
            'rute' => 'jenis-kebutuhan',
            'kolom' => [
                ['kunci' => 'nama', 'label' => 'Nama'],
                ['kunci' => 'urutan', 'label' => 'Urutan'],
                ['kunci' => 'aktif', 'label' => 'Status', 'tipe' => 'aktif'],
            ],
            'field' => [
                ['nama' => 'nama', 'label' => 'Nama (mis. Jabatan Kosong)', 'tipe' => 'teks', 'wajib' => true],
                ['nama' => 'urutan', 'label' => 'Urutan tampil', 'tipe' => 'angka'],
                ['nama' => 'aktif', 'label' => 'Aktif', 'tipe' => 'cek'],
            ],
        ];
    }

    public function index(): Response
    {
        return Inertia::render('Master/Index', array_merge($this->konfigurasi(), [
            'data' => JenisKebutuhan::orderBy('urutan')->orderBy('nama')->paginate(20),
        ]));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:100|unique:jenis_kebutuhan,nama',
            'urutan' => 'nullable|integer|min:0',
            'aktif' => 'boolean',
        ]);

        $jenis = JenisKebutuhan::create($data);
        $this->catatAudit('buat', 'jenis_kebutuhan', $jenis->id, "Jenis kebutuhan {$jenis->nama}");

        return back()->with('sukses', 'Jenis kebutuhan tersimpan.');
    }

    public function update(Request $request, JenisKebutuhan $jenisKebutuhan)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:100|unique:jenis_kebutuhan,nama,'.$jenisKebutuhan->id,
            'urutan' => 'nullable|integer|min:0',
            'aktif' => 'boolean',
        ]);

        $jenisKebutuhan->update($data);
        $this->catatAudit('ubah', 'jenis_kebutuhan', $jenisKebutuhan->id, "Jenis kebutuhan {$jenisKebutuhan->nama}");

        return back()->with('sukses', 'Jenis kebutuhan diperbarui.');
    }
}
