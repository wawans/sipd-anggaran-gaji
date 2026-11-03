<?php

namespace App\Http\Controllers;

use App\Models\MappingTemplate;
use App\Models\MappingTemplateKolom;
use App\Traits\MencatatAudit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MappingTemplateController extends Controller
{
    use MencatatAudit;

    public function index(): Response
    {
        return Inertia::render('MappingTemplate/Index', [
            'data' => MappingTemplate::withCount('kolom')->orderBy('nama')->paginate(15),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:100|unique:mapping_template,nama',
            'kolom_skpd' => 'required|string|max:100',
        ]);

        $template = MappingTemplate::create($data);
        $this->catatAudit('buat', 'mapping_template', $template->id, "Template mapping {$template->nama}");

        return redirect("/mapping-template/{$template->id}")->with('sukses', 'Template dibuat. Tambahkan pemetaan kolom di bawah.');
    }

    public function edit(MappingTemplate $mappingTemplate): Response
    {
        $mappingTemplate->load('kolom.rekening:id,kode,nama');

        return Inertia::render('MappingTemplate/Ubah', [
            'template' => $mappingTemplate,
            'rekening' => \App\Models\Rekening::where('aktif', true)->orderBy('urutan')->orderBy('kode')->get(['id', 'kode', 'nama']),
        ]);
    }

    public function update(Request $request, MappingTemplate $mappingTemplate)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:100|unique:mapping_template,nama,'.$mappingTemplate->id,
            'kolom_skpd' => 'required|string|max:100',
        ]);

        $mappingTemplate->update($data);
        $this->catatAudit('ubah', 'mapping_template', $mappingTemplate->id, "Template mapping {$mappingTemplate->nama}");

        return back()->with('sukses', 'Template diperbarui.');
    }

    public function tambahKolom(Request $request, MappingTemplate $mappingTemplate)
    {
        $data = $request->validate([
            'kolom_excel' => 'required|string|max:100',
            'rekening_id' => 'required|exists:rekening,id',
        ]);

        $ada = MappingTemplateKolom::where('template_id', $mappingTemplate->id)
            ->where(function ($q) use ($data) {
                $q->where('kolom_excel', $data['kolom_excel'])
                    ->orWhere('rekening_id', $data['rekening_id']);
            })->exists();

        if ($ada) {
            return back()->with('galat', 'Kolom Excel atau rekening ini sudah dipetakan di template ini.');
        }

        $kolom = MappingTemplateKolom::create($data + ['template_id' => $mappingTemplate->id]);
        $this->catatAudit('buat', 'mapping_template_kolom', $kolom->id, "Pemetaan '{$data['kolom_excel']}' di template {$mappingTemplate->nama}");

        return back()->with('sukses', 'Pemetaan kolom ditambahkan.');
    }

    public function hapusKolom(MappingTemplateKolom $kolom)
    {
        $nama = $kolom->kolom_excel;
        $kolom->delete();
        $this->catatAudit('hapus', 'mapping_template_kolom', $kolom->id, "Pemetaan '{$nama}' dihapus");

        return back()->with('sukses', 'Pemetaan kolom dihapus.');
    }
}
