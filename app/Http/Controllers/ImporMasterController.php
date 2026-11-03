<?php

namespace App\Http\Controllers;

use App\Exports\TemplateMasterExport;
use App\Imports\LembarArray;
use App\Models\Rekening;
use App\Models\Skpd;
use App\Traits\MencatatAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImporMasterController extends Controller
{
    use MencatatAudit;

    /** Konfigurasi per jenis master yang bisa diimpor. */
    private function konfigurasi(string $jenis): array
    {
        $cfg = [
            'skpd' => [
                'judul' => 'SKPD',
                'model' => Skpd::class,
                'tabel' => 'skpd',
                'contoh' => [['1.01', 'Dinas Pendidikan'], ['1.02', 'Dinas Kesehatan']],
            ],
            'rekening' => [
                'judul' => 'Rekening',
                'model' => Rekening::class,
                'tabel' => 'rekening',
                'contoh' => [['1.1', 'Gaji Pokok'], ['1.2', 'Tunjangan Jabatan']],
            ],
        ];

        abort_unless(isset($cfg[$jenis]), 404);

        return $cfg[$jenis];
    }

    public function form(string $jenis): Response
    {
        $cfg = $this->konfigurasi($jenis);

        return Inertia::render('Master/Impor', [
            'jenis' => $jenis,
            'judul' => $cfg['judul'],
        ]);
    }

    public function pratampil(Request $request, string $jenis): Response|RedirectResponse
    {
        $cfg = $this->konfigurasi($jenis);

        $request->validate([
            'berkas' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $impor = new LembarArray;
        Excel::import($impor, $request->file('berkas'));
        $sheet = $impor->baris;

        if (count($sheet) < 2) {
            return back()->with('galat', 'Berkas Excel kosong atau hanya berisi judul kolom.');
        }

        $header = array_map(fn ($h) => trim((string) $h), $sheet[0]);

        $token = Str::random(40);
        Cache::put('impor-master:'.$token, [
            'jenis' => $jenis,
            'baris' => $sheet,
            'berkas' => $request->file('berkas')->getClientOriginalName(),
        ], 1800);

        return Inertia::render('Master/Pratampil', [
            'jenis' => $jenis,
            'judul' => $cfg['judul'],
            'token' => $token,
            'berkas' => $request->file('berkas')->getClientOriginalName(),
            'header' => $header,
            'contoh' => array_slice($sheet, 1, 5),
            'jml_baris' => count($sheet) - 1,
        ]);
    }

    public function simpan(Request $request, string $jenis)
    {
        $cfg = $this->konfigurasi($jenis);
        $model = $cfg['model'];

        $data = $request->validate([
            'token' => 'required|string',
            'kolom_kode' => 'required|integer|min:0',
            'kolom_nama' => 'required|integer|min:0|different:kolom_kode',
        ]);

        $paket = Cache::get('impor-master:'.$data['token']);
        if (!$paket || $paket['jenis'] !== $jenis) {
            return redirect('/'.$jenis.'/impor')->with('galat', 'Sesi pratampil kedaluwarsa. Ulangi impor dari awal.');
        }

        $header = array_map(fn ($h) => trim((string) $h), $paket['baris'][0]);
        $jmlKolom = count($header);
        if ($data['kolom_kode'] >= $jmlKolom || $data['kolom_nama'] >= $jmlKolom) {
            return redirect('/'.$jenis.'/impor')->with('galat', 'Kolom yang dipilih tidak valid.');
        }

        $ditambah = 0;
        $diperbarui = 0;
        $dilewati = 0;

        DB::transaction(function () use ($paket, $data, $model, $jenis, &$ditambah, &$diperbarui, &$dilewati) {
            foreach (array_slice($paket['baris'], 1) as $row) {
                $kode = trim((string) ($row[$data['kolom_kode']] ?? ''));
                $nama = trim((string) ($row[$data['kolom_nama']] ?? ''));

                // Kode kosong, terlalu panjang, atau nama kosong -> lewati
                if ($kode === '' || mb_strlen($kode) > 30 || $nama === '') {
                    $dilewati++;

                    continue;
                }

                $ada = $model::where('kode', $kode)->first();
                if ($ada) {
                    if ($ada->nama !== $nama) {
                        $ada->nama = $nama;
                        $ada->save();
                    }
                    $diperbarui++;
                } else {
                    $atribut = ['kode' => $kode, 'nama' => $nama];
                    if ($jenis === 'rekening') {
                        // Kelompok wajib diisi (NOT NULL): ambil dari bagian kode sebelum titik
                        $atribut['kelompok'] = Str::before($kode, '.') ?: $kode;
                        $atribut['urutan'] = 0;
                    }
                    $model::create($atribut);
                    $ditambah++;
                }
            }
        });

        Cache::forget('impor-master:'.$data['token']);
        $this->catatAudit(
            'impor', $cfg['tabel'], null,
            "Impor {$cfg['judul']}: {$ditambah} ditambah, {$diperbarui} diperbarui, {$dilewati} dilewati dari {$paket['berkas']}"
        );

        return redirect('/'.$jenis)->with(
            'sukses',
            "Impor {$cfg['judul']} selesai: {$ditambah} ditambah, {$diperbarui} diperbarui, {$dilewati} dilewati."
        );
    }

    public function template(string $jenis): BinaryFileResponse
    {
        $cfg = $this->konfigurasi($jenis);

        return Excel::download(
            new TemplateMasterExport($cfg['judul'], $cfg['contoh']),
            'template-'.$jenis.'.xlsx'
        );
    }
}
