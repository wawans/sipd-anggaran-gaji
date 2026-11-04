<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/** Template .xlsx dinamis untuk impor matriks anggaran per tahap. */
class TemplateMatriksExport implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private string $judul,
        private array $kepala,
        private array $baris = [],
    ) {}

    public function collection(): Collection
    {
        return new Collection($this->baris);
    }

    public function headings(): array
    {
        return $this->kepala;
    }

    public function title(): string
    {
        return $this->judul;
    }
}
