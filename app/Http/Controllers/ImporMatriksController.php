<?php

namespace App\Http\Controllers;

use App\Exports\TemplateMatriksExport;
use App\Models\Anggaran;
use App\Models\AnggaranKebutuhan;
use App\Models\AnggaranKebutuhanTambahan;
use App\Models\JenisKebutuhan;
use App\Models\JenisKebutuhanTambahan;
use App\Models\Rekening;
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
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Impor matriks anggaran dari Excel (level tahap).
 * Alur 2 langkah: unggah -> pratampil + pemetaan kolom (token sesi di Cache)
 * -> simpan. Template dinamis: kolom jenis kebutuhan/tambahan sesuai pilihan.
 */
class ImporMatriksController extends Controller
{
    use MencatatAudit;

    /** Kolom tetap template (kunci logis => label header). */
    private function labelTetap(): array
    {
        return [
            'kode_skpd' => 'Kode SKPD',
            'nama_skpd' => 'Nama SKPD',
            'kode_rekening' => 'Kode Rekening',
            'nama_rekening' => 'Nama Rekening',
            'nilai' => 'Nilai/Bulan',
            'volume' => 'Volume',
            'tipe_acress' => 'Tipe Acress',
            'nilai_acress' => 'Nilai Acress',
        ];
    }

    /** Jenis kebutuhan & tambahan terpilih dari request (koleksi id+nama). */
    private function jenisTerpilih(Request $request): array
    {
        $kb = JenisKebutuhan::whereIn('id', (array) $request->input('kebutuhan', []))
            ->orderBy('urutan')->get(['id', 'nama']);
        $kt = JenisKebutuhanTambahan::whereIn('id', (array) $request->input('tambahan', []))
            ->orderBy('urutan')->get(['id', 'nama']);

        return [$kb, $kt];
    }

    private function bangunHeader($kb, $kt): array
    {
        $kepala = array_values($this->labelTetap());
        foreach ($kb as $j) {
            $kepala[] = $j->nama;
        }
        foreach ($kt as $j) {
            $kepala[] = $j->nama;
        }

        return $kepala;
    }

    /**
     * Unduh template: mode=kosong (hanya header) atau mode=data
     * (berisi seluruh baris SKPD x rekening tahap ini untuk revisi).
     */
    public function template(Request $request, Tahap $tahap): BinaryFileResponse|RedirectResponse
    {
        if ($tahap->terkunci()) {
            return back()->with('galat', 'Tahap terkunci: tidak bisa mengunduh template impor.');
        }

        $data = $request->validate([
            'mode' => 'required|in:kosong,data',
            'kebutuhan' => 'nullable|array',
            'kebutuhan.*' => 'integer|exists:jenis_kebutuhan,id',
            'tambahan' => 'nullable|array',
            'tambahan.*' => 'integer|exists:jenis_kebutuhan_tambahan,id',
        ]);

        [$kb, $kt] = $this->jenisTerpilih($request);
        $kepala = $this->bangunHeader($kb, $kt);

        $baris = [];
        if ($data['mode'] === 'data') {
            $kbIds = $kb->pluck('id')->all();
            $ktIds = $kt->pluck('id')->all();

            $daftar = Anggaran::with(['skpd:id,kode,nama', 'rekening:id,kode,nama', 'kebutuhan', 'kebutuhanTambahan'])
                ->where('tahap_id', $tahap->id)
                ->join('skpd as s', 's.id', '=', 'anggaran.skpd_id')
                ->join('rekening as r', 'r.id', '=', 'anggaran.rekening_id')
                ->orderBy('s.kode')->orderBy('r.urutan')->orderBy('r.kode')
                ->select('anggaran.*')
                ->get();

            foreach ($daftar as $a) {
                $r = [
                    $a->skpd->kode,
                    $a->skpd->nama,
                    $a->rekening->kode,
                    $a->rekening->nama,
                    (float) $a->nilai,
                    (float) $a->volume,
                    $a->acress_type === 'persen' ? '%' : 'Rp',
                    (float) $a->acress,
                ];
                $petaKb = $a->kebutuhan->keyBy('jenis_kebutuhan_id');
                $petaKt = $a->kebutuhanTambahan->keyBy('jenis_kebutuhan_tambahan_id');
                foreach ($kbIds as $id) {
                    $r[] = isset($petaKb[$id]) ? (float) $petaKb[$id]->nominal : '';
                }
                foreach ($ktIds as $id) {
                    $r[] = isset($petaKt[$id]) ? (float) $petaKt[$id]->nominal : '';
                }
                $baris[] = $r;
            }
        }

        return Excel::download(
            new TemplateMatriksExport("Tahap {$tahap->nama}", $kepala, $baris),
            'template-matriks-'.Str::slug($tahap->nama).'-'.$data['mode'].'.xlsx'
        );
    }

    /**
     * Pratampil: (a) unggah berkas baru -> parse + petakan otomatis + validasi,
     * (b) token + pemetaan manual -> validasi ulang.
     */
    public function pratampil(Request $request, Tahap $tahap): Response|RedirectResponse
    {
        if ($tahap->terkunci()) {
            return back()->with('galat', 'Tahap terkunci: tidak bisa impor matriks.');
        }

        if ($request->filled('token')) {
            $data = $request->validate([
                'token' => 'required|string',
                'pemetaan' => 'required|array',
            ]);
            $paket = Cache::get('impor-matriks:'.$data['token']);
            if (!$paket || (int) $paket['tahap_id'] !== (int) $tahap->id) {
                return redirect("/tahap/{$tahap->id}")->with('galat', 'Sesi pratampil kedaluwarsa. Ulangi impor dari awal.');
            }
            $peta = $this->normalisasiPemetaan($data['pemetaan'], $paket);
            $hasil = $this->validasiSemua($paket, $peta, $tahap);

            return $this->tampilPratampil($tahap, $data['token'], $paket, $peta, $hasil);
        }

        $request->validate([
            'berkas' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'kebutuhan' => 'nullable|array',
            'kebutuhan.*' => 'integer|exists:jenis_kebutuhan,id',
            'tambahan' => 'nullable|array',
            'tambahan.*' => 'integer|exists:jenis_kebutuhan_tambahan,id',
        ]);

        [$baris, $persen] = $this->bacaLembar($request->file('berkas'));

        if (count($baris) < 2) {
            return back()->with('galat', 'Berkas Excel kosong atau hanya berisi judul kolom.');
        }

        [$kb, $kt] = $this->jenisTerpilih($request);

        $token = Str::random(40);
        $paket = [
            'tahap_id' => $tahap->id,
            'baris' => $baris,
            'persen' => $persen,
            'berkas' => $request->file('berkas')->getClientOriginalName(),
            'kebutuhan' => $kb->map(fn ($j) => ['id' => $j->id, 'nama' => $j->nama])->all(),
            'tambahan' => $kt->map(fn ($j) => ['id' => $j->id, 'nama' => $j->nama])->all(),
        ];
        Cache::put('impor-matriks:'.$token, $paket, 1800);

        $peta = $this->petakanOtomatis($baris[0], $paket);
        $hasil = $this->validasiSemua($paket, $peta, $tahap);

        return $this->tampilPratampil($tahap, $token, $paket, $peta, $hasil);
    }

    public function simpan(Request $request, Tahap $tahap)
    {
        if ($tahap->terkunci()) {
            return back()->with('galat', 'Tahap terkunci: tidak bisa impor matriks.');
        }

        $data = $request->validate([
            'token' => 'required|string',
            'pemetaan' => 'required|array',
        ]);

        $paket = Cache::get('impor-matriks:'.$data['token']);
        if (!$paket || (int) $paket['tahap_id'] !== (int) $tahap->id) {
            return redirect("/tahap/{$tahap->id}")->with('galat', 'Sesi pratampil kedaluwarsa. Ulangi impor dari awal.');
        }

        $peta = $this->normalisasiPemetaan($data['pemetaan'], $paket);
        if (!isset($peta['kode_skpd']) || !isset($peta['kode_rekening'])) {
            return back()->with('galat', 'Pilih kolom Kode SKPD dan Kode Rekening terlebih dahulu.');
        }

        $hasil = $this->validasiSemua($paket, $peta, $tahap);

        $ditambah = 0;
        $diubah = 0;
        $dilewati = 0;

        DB::transaction(function () use ($hasil, $tahap, &$ditambah, &$diubah, &$dilewati) {
            foreach ($hasil as $h) {
                if ($h['status'] === 'lewati') {
                    $dilewati++;

                    continue;
                }

                // Cek ulang di dalam transaksi (aman dari duplikat dalam berkas)
                $anggaran = Anggaran::where('tahap_id', $tahap->id)
                    ->where('skpd_id', $h['skpd_id'])
                    ->where('rekening_id', $h['rekening_id'])
                    ->first();

                if ($anggaran) {
                    $ubah = [];
                    if ($h['nilai'] !== null) {
                        $ubah['nilai'] = $h['nilai'];
                    }
                    if ($h['volume'] !== null) {
                        $ubah['volume'] = $h['volume'];
                    }
                    if ($h['tipe_acress'] !== null || $h['nilai_acress'] !== null) {
                        $ubah['acress_type'] = $h['tipe_acress'] ?? 'nominal';
                        $ubah['acress'] = $h['nilai_acress'] ?? 0;
                    }
                    if ($ubah) {
                        $anggaran->update($ubah);
                    }
                    $diubah++;
                } else {
                    $anggaran = Anggaran::create([
                        'tahap_id' => $tahap->id,
                        'skpd_id' => $h['skpd_id'],
                        'rekening_id' => $h['rekening_id'],
                        'nilai' => $h['nilai'] ?? 0,
                        'volume' => $h['volume'] ?? 0,
                        'acress_type' => $h['tipe_acress'] ?? 'nominal',
                        'acress' => $h['nilai_acress'] ?? 0,
                    ]);
                    $ditambah++;
                }

                // Rincian: hanya jenis terpilih yang diproses; sel kosong = tidak diubah
                foreach ($h['rincian'] as $kunci => $r) {
                    if ($r['nilai'] === null) {
                        continue;
                    }
                    if (str_starts_with($kunci, 'kb_')) {
                        $k = ['anggaran_id' => $anggaran->id, 'jenis_kebutuhan_id' => $r['jenis_id']];
                        if ($r['nilai'] > 0) {
                            AnggaranKebutuhan::updateOrCreate($k, ['nominal' => $r['nilai']]);
                        } else {
                            AnggaranKebutuhan::where($k)->delete();
                        }
                    } else {
                        $k = ['anggaran_id' => $anggaran->id, 'jenis_kebutuhan_tambahan_id' => $r['jenis_id']];
                        if ($r['nilai'] > 0) {
                            AnggaranKebutuhanTambahan::updateOrCreate($k, ['nominal' => $r['nilai']]);
                        } else {
                            AnggaranKebutuhanTambahan::where($k)->delete();
                        }
                    }
                }
            }
        });

        Cache::forget('impor-matriks:'.$data['token']);
        $this->catatAudit(
            'impor', 'anggaran', null,
            "Impor matriks tahap {$tahap->nama}: {$ditambah} ditambah, {$diubah} diperbarui, {$dilewati} dilewati dari {$paket['berkas']}"
        );

        return redirect("/tahap/{$tahap->id}/anggaran")->with(
            'sukses',
            "Impor matriks selesai: {$ditambah} baris ditambah, {$diubah} diperbarui, {$dilewati} dilewati."
        );
    }

    /**
     * Baca lembar aktif: nilai mentah + matriks penanda sel berformat persen.
     * Kembalikan [baris, persen] — persen[$i] sejajar baris data ke-$i.
     */
    private function bacaLembar($berkas): array
    {
        $lembar = IOFactory::load($berkas->getRealPath())->getActiveSheet();
        $barisTerakhir = $lembar->getHighestRow();
        $kolomTerakhir = $lembar->getHighestColumn();

        $baris = $lembar->rangeToArray("A1:{$kolomTerakhir}{$barisTerakhir}", null, true, false, false);

        $persen = [];
        for ($r = 2; $r <= $barisTerakhir; $r++) {
            $tandai = [];
            $jmlKolom = count($baris[$r - 1] ?? []);
            for ($c = 1; $c <= $jmlKolom; $c++) {
                $koordinat = Coordinate::stringFromColumnIndex($c).$r;
                $format = (string) $lembar->getStyle($koordinat)->getNumberFormat()->getFormatCode();
                $tandai[] = str_contains($format, '%');
            }
            $persen[] = $tandai;
        }

        return [$baris, $persen];
    }

    /** Pemetaan header -> kunci logis: case-insensitive, toleran spasi. */
    private function petakanOtomatis(array $header, array $paket): array
    {
        $normal = fn ($s) => mb_strtolower(trim((string) preg_replace('/\s+/', ' ', (string) $s)));

        $alias = [];
        foreach ($this->labelTetap() as $kunci => $label) {
            $alias[$normal($label)] = $kunci;
        }
        $alias['nilai per bulan'] = 'nilai';
        $alias['nilai perbulan'] = 'nilai';
        $alias['nilai accress'] = 'nilai_acress';
        $alias['nilai accres'] = 'nilai_acress';
        $alias['accress'] = 'nilai_acress';
        $alias['accres'] = 'nilai_acress';
        $alias['tipe accress'] = 'tipe_acress';
        $alias['tipe accres'] = 'tipe_acress';

        foreach ($paket['kebutuhan'] as $j) {
            $n = $normal($j['nama']);
            if (!isset($alias[$n])) {
                $alias[$n] = 'kb_'.$j['id'];
            }
        }
        foreach ($paket['tambahan'] as $j) {
            $n = $normal($j['nama']);
            if (!isset($alias[$n])) {
                $alias[$n] = 'kt_'.$j['id'];
            }
        }

        $peta = [];
        $terpakai = [];
        foreach ($header as $i => $h) {
            $kunci = $alias[$normal($h)] ?? null;
            if ($kunci && !in_array($i, $terpakai, true)) {
                $peta[$kunci] = $i;
                $terpakai[] = $i;
            }
        }

        return $peta;
    }

    /** Daftar medan logis untuk dropdown pemetaan di frontend. */
    private function daftarMedan(array $paket): array
    {
        $medan = [];
        foreach ($this->labelTetap() as $kunci => $label) {
            $medan[] = [
                'kunci' => $kunci,
                'label' => $label,
                'wajib' => in_array($kunci, ['kode_skpd', 'kode_rekening'], true),
            ];
        }
        foreach ($paket['kebutuhan'] as $j) {
            $medan[] = ['kunci' => 'kb_'.$j['id'], 'label' => 'Kebutuhan: '.$j['nama'], 'wajib' => false];
        }
        foreach ($paket['tambahan'] as $j) {
            $medan[] = ['kunci' => 'kt_'.$j['id'], 'label' => 'Tambahan: '.$j['nama'], 'wajib' => false];
        }

        return $medan;
    }

    /** Normalisasi pemetaan kiriman pengguna: buang kunci tak dikenal & kolom ganda. */
    private function normalisasiPemetaan(array $input, array $paket): array
    {
        $jmlKolom = count($paket['baris'][0] ?? []);
        $peta = [];
        $terpakai = [];
        foreach ($input as $kunci => $idx) {
            if (!$this->kunciValid((string) $kunci, $paket)) {
                continue;
            }
            if ($idx === null || $idx === '') {
                continue;
            }
            $idx = (int) $idx;
            if ($idx < 0 || $idx >= $jmlKolom) {
                continue;
            }
            if (in_array($idx, $terpakai, true)) {
                continue;
            }
            $terpakai[] = $idx;
            $peta[(string) $kunci] = $idx;
        }

        return $peta;
    }

    private function kunciValid(string $kunci, array $paket): bool
    {
        if (isset($this->labelTetap()[$kunci])) {
            return true;
        }
        if (str_starts_with($kunci, 'kb_')) {
            return collect($paket['kebutuhan'])->contains('id', (int) substr($kunci, 3));
        }
        if (str_starts_with($kunci, 'kt_')) {
            return collect($paket['tambahan'])->contains('id', (int) substr($kunci, 3));
        }

        return false;
    }

    /**
     * Parse angka: format Indonesia (1.234.567,89) maupun polos.
     * Kembalikan [nilai|null, galat|null]; null = sel kosong.
     * Sel berformat persen (nilai mentah 0.025) otomatis dikali 100.
     */
    private function parseAngka($mentah, bool $formatPersen): array
    {
        if ($mentah === null) {
            return [null, null];
        }

        if (is_string($mentah)) {
            $s = trim($mentah);
            if ($s === '') {
                return [null, null];
            }
            $asli = $s;
            $persenTeks = false;
            if (str_ends_with($s, '%')) {
                $persenTeks = true;
                $s = trim(substr($s, 0, -1));
            }
            // Format Indonesia: titik pemisah ribuan (kelompok 3 digit), koma desimal
            if (preg_match('/^\d{1,3}(\.\d{3})+(,\d+)?$/', $s)) {
                $s = str_replace('.', '', $s);
            }
            $s = str_replace(',', '.', $s);
            $s = preg_replace('/[^0-9.\-]/', '', $s);
            if ($s === '' || !is_numeric($s)) {
                return [null, "bukan angka ('{$asli}')"];
            }
            $nilai = (float) $s;
            if ($nilai < 0) {
                return [null, 'tidak boleh negatif'];
            }

            return [$nilai, null]; // '2.5%' -> 2.5 (tanda % sudah menyatakan persen)
        }

        if (is_bool($mentah)) {
            return [null, 'bukan angka'];
        }

        $nilai = (float) $mentah;
        if ($formatPersen) {
            $nilai *= 100; // 0.025 -> 2.5
        }
        if ($nilai < 0) {
            return [null, 'tidak boleh negatif'];
        }

        return [$nilai, null];
    }

    /**
     * Parse tipe acress: Rp/%/nominal/persen (case-insensitive), kosong = null.
     * Kembalikan [tipe|null, galat|null].
     */
    private function parseTipeAcress($mentah): array
    {
        if ($mentah === null) {
            return [null, null];
        }
        $s = mb_strtolower(trim((string) $mentah));
        if ($s === '') {
            return [null, null];
        }
        if (in_array($s, ['rp', 'nominal'], true)) {
            return ['nominal', null];
        }
        if (in_array($s, ['%', 'persen', 'percent'], true)) {
            return ['persen', null];
        }

        return [null, "tipe acress '{$mentah}' tidak dikenal (isi Rp/%/nominal/persen)"];
    }

    private function validasiSemua(array $paket, array $peta, Tahap $tahap): array
    {
        $petaSkpd = Skpd::pluck('id', 'kode');
        $petaRek = Rekening::pluck('id', 'kode');
        $adaBaris = Anggaran::where('tahap_id', $tahap->id)->get()
            ->keyBy(fn ($a) => $a->skpd_id.':'.$a->rekening_id);

        $hasil = [];
        $terlihat = [];
        foreach (array_slice($paket['baris'], 1) as $i => $row) {
            $h = $this->validasiBaris($row, $i, $paket, $peta, $petaSkpd, $petaRek, $adaBaris);
            $kunci = ($h['skpd_id'] ?? '-').':'.($h['rekening_id'] ?? '-');
            if ($h['status'] !== 'lewati' && isset($terlihat[$kunci])) {
                $h['status'] = 'ubah';
                $h['anggaran_id'] = null;
                $h['pesan'] = ['Duplikat dalam berkas: baris ini akan menimpa baris sebelumnya.'];
            }
            $terlihat[$kunci] = true;
            $hasil[] = $h;
        }

        return $hasil;
    }

    private function validasiBaris(array $row, int $i, array $paket, array $peta, $petaSkpd, $petaRek, $adaBaris): array
    {
        $sel = fn ($kunci) => isset($peta[$kunci]) ? ($row[$peta[$kunci]] ?? null) : null;
        $persen = fn ($kunci) => isset($peta[$kunci]) ? (bool) ($paket['persen'][$i][$peta[$kunci]] ?? false) : false;

        $kodeSkpd = trim((string) ($sel('kode_skpd') ?? ''));
        $kodeRek = trim((string) ($sel('kode_rekening') ?? ''));

        $out = [
            'no' => $i + 2,
            'kode_skpd' => $kodeSkpd,
            'nama_skpd' => trim((string) ($sel('nama_skpd') ?? '')),
            'kode_rekening' => $kodeRek,
            'nama_rekening' => trim((string) ($sel('nama_rekening') ?? '')),
            'status' => 'lewati',
            'pesan' => [],
            'nilai' => null,
            'volume' => null,
            'tipe_acress' => null,
            'nilai_acress' => null,
            'rincian' => [],
        ];

        if ($kodeSkpd === '') {
            $out['pesan'][] = 'Kode SKPD kosong.';

            return $out;
        }
        if ($kodeRek === '') {
            $out['pesan'][] = 'Kode rekening kosong.';

            return $out;
        }
        $skpdId = $petaSkpd[$kodeSkpd] ?? null;
        if (!$skpdId) {
            $out['pesan'][] = "Kode SKPD '{$kodeSkpd}' tidak dikenal.";

            return $out;
        }
        $rekId = $petaRek[$kodeRek] ?? null;
        if (!$rekId) {
            $out['pesan'][] = "Kode rekening '{$kodeRek}' tidak dikenal.";

            return $out;
        }

        $bermasalah = false;
        foreach (['nilai' => 'Nilai/Bulan', 'volume' => 'Volume', 'nilai_acress' => 'Nilai Acress'] as $kunci => $label) {
            [$nilai, $galat] = $this->parseAngka($sel($kunci), $persen($kunci));
            if ($galat) {
                $out['pesan'][] = "{$label}: {$galat}.";
                $bermasalah = true;
            }
            $out[$kunci] = $nilai;
        }

        [$tipe, $galatTipe] = $this->parseTipeAcress($sel('tipe_acress'));
        if ($galatTipe) {
            $out['pesan'][] = $galatTipe.'.';
            $bermasalah = true;
        }
        $out['tipe_acress'] = $tipe;

        foreach (['kebutuhan' => 'kb_', 'tambahan' => 'kt_'] as $grup => $awalan) {
            foreach ($paket[$grup] as $j) {
                $kunci = $awalan.$j['id'];
                [$nilai, $galat] = $this->parseAngka($sel($kunci), $persen($kunci));
                if ($galat) {
                    $out['pesan'][] = "{$j['nama']}: {$galat}.";
                    $bermasalah = true;
                }
                $out['rincian'][$kunci] = ['jenis_id' => $j['id'], 'nama' => $j['nama'], 'nilai' => $nilai];
            }
        }

        if ($bermasalah) {
            return $out;
        }

        $out['skpd_id'] = $skpdId;
        $out['rekening_id'] = $rekId;
        $kunciBaris = $skpdId.':'.$rekId;
        if (isset($adaBaris[$kunciBaris])) {
            $out['status'] = 'ubah';
            $out['anggaran_id'] = $adaBaris[$kunciBaris]->id;
            $out['pesan'][] = 'Baris sudah ada: akan diperbarui (sel kosong tidak diubah).';
        } else {
            $out['status'] = 'baru';
            $out['pesan'][] = 'Baris baru: akan ditambahkan.';
        }

        return $out;
    }

    private function tampilPratampil(Tahap $tahap, string $token, array $paket, array $peta, array $hasil): Response
    {
        $ringkas = ['baru' => 0, 'ubah' => 0, 'lewati' => 0];
        foreach ($hasil as $h) {
            $ringkas[$h['status']]++;
        }

        return Inertia::render('Tahap/ImporPratampil', [
            'tahap' => $tahap,
            'token' => $token,
            'berkas' => $paket['berkas'],
            'header' => array_map(fn ($h) => trim((string) $h), $paket['baris'][0]),
            'jml_baris' => count($paket['baris']) - 1,
            'medan' => $this->daftarMedan($paket),
            'pemetaan' => $peta,
            'hasil' => $hasil,
            'ringkas' => $ringkas,
        ]);
    }
}
