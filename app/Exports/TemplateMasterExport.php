<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/** Template .xlsx untuk impor master (SKPD / Rekening): header Kode + Nama + 2 baris contoh. */
class TemplateMasterExport implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private string $judul,
        private array $contoh,
    ) {}

    public function collection(): Collection
    {
        return new Collection($this->contoh);
    }

    public function headings(): array
    {
        return ['Kode', 'Nama'];
    }

    public function title(): string
    {
        return $this->judul;
    }
}
