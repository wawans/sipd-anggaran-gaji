<?php

namespace App\Http\Controllers;

use App\Imports\LembarArray;
use App\Models\MappingTemplate;
use App\Models\Realisasi;
use App\Models\Skpd;
use App\Models\Tahap;
use App\Traits\MencatatAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class RealisasiController extends Controller
{
    use MencatatAudit;

    public function index(Request $request): Response
    {
        $query = Realisasi::with(['skpd:id,kode,nama', 'rekening:id,kode,nama', 'pengimpor:id,nama'])
            ->orderByDesc('periode')->orderBy('skpd_id')->orderBy('rekening_id');

        if ($request->filled('periode')) {
            $query->where('periode', $request->periode);
        }
        if ($request->filled('skpd_id')) {
            $query->where('skpd_id', $request->skpd_id);
        }

        return Inertia::render('Realisasi/Index', [
            'data' => $query->paginate(25)->withQueryString(),
            'daftar_periode' => Realisasi::select('periode')->distinct()->orderByDesc('periode')->pluck('periode'),
            'daftar_skpd' => Skpd::orderBy('kode')->get(['id', 'kode', 'nama']),
            'filter' => $request->only(['periode', 'skpd_id']),
        ]);
    }

    public function impor(): Response
    {
        return Inertia::render('Realisasi/Impor', [
            'template' => MappingTemplate::with('kolom.rekening:id,kode,nama')
                ->orderBy('nama')->get(),
        ]);
    }

    public function pratampil(Request $request): Response|RedirectResponse
    {
        $data = $request->validate([
            'template_id' => 'required|exists:mapping_template,id',
            'periode' => ['required', 'regex:/^[0-9]{4}-(0[1-9]|1[0-2])$/'],
            'mode' => 'required|in:timpa,jumlahkan',
            'berkas' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ], [
            'periode.regex' => 'Periode harus format YYYY-MM (mis. 2026-01).',
        ]);

        if ($this->periodeTerkunci($data['periode'])) {
            return back()->with('galat', "Periode {$data['periode']} berada dalam tahap yang terkunci: impor dibatalkan.");
        }

        $template = MappingTemplate::with('kolom.rekening')->findOrFail($data['template_id']);
        $impor = new LembarArray;
        Excel::import($impor, $request->file('berkas'));
        $lembar = $impor->baris;
        $sheet = $lembar;

        if (count($sheet) < 2) {
            return back()->with('galat', 'Berkas Excel kosong atau hanya berisi judul kolom.');
        }

        $header = array_map(fn ($h) => trim((string) $h), $sheet[0]);

        $idxSkpd = $this->cariKolom($header, $template->kolom_skpd);
        if ($idxSkpd === null) {
            return back()->with('galat', "Kolom penanda SKPD '{$template->kolom_skpd}' tidak ditemukan di berkas.");
        }

        $pemetaan = [];
        $kolomTakDitemukan = [];
        foreach ($template->kolom as $kolom) {
            $idx = $this->cariKolom($header, $kolom->kolom_excel);
            if ($idx === null) {
                $kolomTakDitemukan[] = $kolom->kolom_excel;
            } else {
                $pemetaan[$idx] = ['rekening_id' => $kolom->rekening_id, 'rekening_nama' => $kolom->rekening->nama];
            }
        }

        $skpdByKode = Skpd::pluck('id', 'kode')->all();
        $skpdByNama = [];
        foreach (Skpd::get(['id', 'kode', 'nama']) as $s) {
            $skpdByNama[mb_strtolower($s->nama)] = ['id' => $s->id, 'kode' => $s->kode, 'nama' => $s->nama];
        }

        $cocok = [];
        $takCocok = [];

        foreach (array_slice($sheet, 1) as $n => $row) {
            $penanda = trim((string) ($row[$idxSkpd] ?? ''));
            if ($penanda === '') {
                continue;
            }

            $info = null;
            if (isset($skpdByKode[$penanda])) {
                $s = Skpd::find($skpdByKode[$penanda]);
                $info = ['id' => $s->id, 'kode' => $s->kode, 'nama' => $s->nama];
            } elseif (isset($skpdByNama[mb_strtolower($penanda)])) {
                $info = $skpdByNama[mb_strtolower($penanda)];
            }

            if (!$info) {
                $takCocok[] = ['baris' => $n + 2, 'penanda' => $penanda];

                continue;
            }

            $rincian = [];
            foreach ($pemetaan as $idx => $p) {
                $nilai = $this->parseAngka($row[$idx] ?? null);
                if ($nilai !== null && $nilai != 0) {
                    $rincian[] = ['rekening_id' => $p['rekening_id'], 'rekening_nama' => $p['rekening_nama'], 'jumlah' => $nilai];
                }
            }

            $cocok[] = [
                'skpd_id' => $info['id'],
                'skpd_kode' => $info['kode'],
                'skpd_nama' => $info['nama'],
                'jml_rekening' => count($rincian),
                'total' => array_sum(array_column($rincian, 'jumlah')),
                'rincian' => $rincian,
            ];
        }

        $token = Str::random(40);
        Cache::put('impor:'.$token, [
            'baris' => $cocok,
            'periode' => $data['periode'],
            'mode' => $data['mode'],
            'berkas' => $request->file('berkas')->getClientOriginalName(),
            'template' => $template->nama,
        ], 1800);

        return Inertia::render('Realisasi/Pratampil', [
            'token' => $token,
            'periode' => $data['periode'],
            'mode' => $data['mode'],
            'template' => $template->nama,
            'berkas' => $request->file('berkas')->getClientOriginalName(),
            'jml_cocok' => count($cocok),
            'jml_sel' => array_sum(array_column($cocok, 'jml_rekening')),
            'tak_cocok' => $takCocok,
            'kolom_tak_ditemukan' => $kolomTakDitemukan,
            'contoh' => array_slice($cocok, 0, 10),
        ]);
    }

    public function simpan(Request $request)
    {
        $data = $request->validate(['token' => 'required|string']);

        $paket = Cache::get('impor:'.$data['token']);
        if (!$paket) {
            return redirect('/realisasi/impor')->with('galat', 'Sesi pratampil kedaluwarsa. Ulangi impor dari awal.');
        }

        if ($this->periodeTerkunci($paket['periode'])) {
            return redirect('/realisasi/impor')->with('galat', "Periode {$paket['periode']} berada dalam tahap yang terkunci: impor dibatalkan.");
        }

        $tersimpan = 0;
        $penggunaId = auth()->id();

        DB::transaction(function () use ($paket, $penggunaId, &$tersimpan) {
            foreach ($paket['baris'] as $baris) {
                foreach ($baris['rincian'] as $rincian) {
                    if ($paket['mode'] === 'timpa') {
                        $sql = <<<'SQL'
                            INSERT INTO realisasi (periode, skpd_id, rekening_id, jumlah, file_sumber, diimpor_oleh, diimpor_pada)
                            VALUES (?, ?, ?, ?, ?, ?, now())
                            ON CONFLICT (periode, skpd_id, rekening_id)
                            DO UPDATE SET jumlah = EXCLUDED.jumlah, file_sumber = EXCLUDED.file_sumber,
                                          diimpor_oleh = EXCLUDED.diimpor_oleh, diimpor_pada = now()
                            SQL;
                    } else {
                        $sql = <<<'SQL'
                            INSERT INTO realisasi (periode, skpd_id, rekening_id, jumlah, file_sumber, diimpor_oleh, diimpor_pada)
                            VALUES (?, ?, ?, ?, ?, ?, now())
                            ON CONFLICT (periode, skpd_id, rekening_id)
                            DO UPDATE SET jumlah = realisasi.jumlah + EXCLUDED.jumlah, file_sumber = EXCLUDED.file_sumber,
                                          diimpor_oleh = EXCLUDED.diimpor_oleh, diimpor_pada = now()
                            SQL;
                    }

                    DB::insert($sql, [
                        $paket['periode'], $baris['skpd_id'], $rincian['rekening_id'],
                        $rincian['jumlah'], $paket['berkas'], $penggunaId,
                    ]);
                    $tersimpan++;
                }
            }
        });

        Cache::forget('impor:'.$data['token']);
        $modeLabel = $paket['mode'] === 'timpa' ? 'ditimpa' : 'dijumlahkan';
        $this->catatAudit('impor', 'realisasi', null, "Impor {$tersimpan} sel periode {$paket['periode']} ({$modeLabel}) dari {$paket['berkas']}");

        return redirect('/realisasi')->with('sukses', "Impor selesai: {$tersimpan} sel periode {$paket['periode']} {$modeLabel}.");
    }

    /** True jika periode (YYYY-MM) masuk rentang tanggal tahap yang terkunci. */
    protected function periodeTerkunci(string $periode): bool
    {
        return Tahap::where('status', 'terkunci')
            ->whereRaw("to_char(tgl_mulai, 'YYYY-MM') <= ?", [$periode])
            ->whereRaw("to_char(tgl_selesai, 'YYYY-MM') >= ?", [$periode])
            ->exists();
    }

    protected function cariKolom(array $header, string $nama): ?int
    {
        $cari = mb_strtolower(trim($nama));
        foreach ($header as $i => $h) {
            if (mb_strtolower($h) === $cari) {
                return $i;
            }
        }

        return null;
    }

    /** Parse angka format Indonesia (1.234.567,89) maupun polos. */
    protected function parseAngka($nilai): ?float
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }
        if (is_numeric($nilai)) {
            return (float) $nilai;
        }

        $s = trim((string) $nilai);
        if ($s === '' || $s === '-') {
            return null;
        }

        $negatif = str_starts_with($s, '-');
        $s = ltrim($s, '-');

        // Format Indonesia: titik pemisah ribuan (kelompok 3 digit), koma desimal
        if (preg_match('/^\d{1,3}(\.\d{3})+(,\d+)?$/', $s)) {
            $s = str_replace('.', '', $s);
        }
        $s = str_replace(',', '.', $s);
        $s = preg_replace('/[^0-9.]/', '', $s);

        if ($s === '' || !is_numeric($s)) {
            return null;
        }

        return ($negatif ? -1 : 1) * (float) $s;
    }
}
