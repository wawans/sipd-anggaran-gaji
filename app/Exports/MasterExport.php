<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;

/** Export .xlsx berisi seluruh baris data satu master (SKPD / Rekening / Jenis Kebutuhan / Jenis Kebutuhan Tambahan). */
class MasterExport implements FromCollection, WithHeadings, WithStrictNullComparison, WithTitle
{
    public function __construct(
        private string $judul,
        private array $kepala,
        private array $baris,
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
