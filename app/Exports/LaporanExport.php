<?php

namespace App\Exports;

use App\Models\Tahap;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;

class LaporanExport implements FromCollection, WithHeadings, WithStrictNullComparison, WithTitle
{
    public function __construct(
        private Tahap $tahap,
        private array $laporan,
    ) {}

    public function collection(): Collection
    {
        $baris = [];

        $baris[] = ['RINGKASAN TAHAP: '.$this->tahap->tahun.' - '.$this->tahap->nama];
        $baris[] = ['Total anggaran', $this->laporan['ringkas']['total_anggaran']];
        $baris[] = ['Total realisasi', $this->laporan['ringkas']['total_realisasi']];
        $baris[] = ['Selisih (anggaran - realisasi)', $this->laporan['ringkas']['selisih']];
        $baris[] = [];

        $baris[] = ['PER SKPD'];
        foreach ($this->laporan['per_skpd'] as $r) {
            $baris[] = [$r['kode'], $r['nama'], $r['total_anggaran'], $r['total_realisasi'], $r['selisih']];
        }
        $baris[] = [];

        $baris[] = ['PER REKENING'];
        foreach ($this->laporan['per_rekening'] as $r) {
            $baris[] = [$r['kode'], $r['nama'], $r['total_anggaran'], $r['total_realisasi'], $r['selisih']];
        }

        return new Collection($baris);
    }

    public function headings(): array
    {
        return ['Keterangan / Kode', 'Nama', 'Total Anggaran', 'Total Realisasi', 'Selisih'];
    }

    public function title(): string
    {
        return 'Laporan '.$this->tahap->tahun;
    }
}
