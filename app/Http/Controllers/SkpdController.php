<?php

namespace App\Http\Controllers;

use App\Models\Skpd;
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
}
