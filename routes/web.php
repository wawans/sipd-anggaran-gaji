<?php

use App\Http\Controllers\AnggaranController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DasborController;
use App\Http\Controllers\ImporMasterController;
use App\Http\Controllers\ImporMatriksController;
use App\Http\Controllers\JenisKebutuhanController;
use App\Http\Controllers\JenisKebutuhanTambahanController;
use App\Http\Controllers\KataSandiController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\LogAuditController;
use App\Http\Controllers\MappingTemplateController;
use App\Http\Controllers\PenggunaController;
use App\Http\Controllers\PeranController;
use App\Http\Controllers\PerbandinganController;
use App\Http\Controllers\RealisasiController;
use App\Http\Controllers\RekeningController;
use App\Http\Controllers\SkpdController;
use App\Http\Controllers\TahapController;
use Illuminate\Support\Facades\Route;

// Autentikasi (nama route 'login' dipakai middleware auth untuk redirect tamu)
Route::get('/masuk', [AuthController::class, 'tampilMasuk'])->name('login')->middleware('guest');
Route::post('/masuk', [AuthController::class, 'masuk'])->middleware('guest');

Route::middleware('auth')->group(function () {
    Route::post('/keluar', [AuthController::class, 'keluar'])->name('keluar');

    // Ubah kata sandi sendiri — semua role, tanpa hak akses khusus.
    // Tanpa parameter id: controller memakai user dari sesi auth.
    Route::get('/kata-sandi', [KataSandiController::class, 'edit'])->name('kata-sandi.edit');
    Route::put('/kata-sandi', [KataSandiController::class, 'update'])->name('kata-sandi.update');
    Route::get('/', fn () => redirect('/dasbor'));

    Route::get('/dasbor', [DasborController::class, 'index'])
        ->middleware('hak_akses:laporan.view')->name('dasbor');

    // Master SKPD
    Route::get('/skpd', [SkpdController::class, 'index'])->middleware('hak_akses:skpd.view')->name('skpd.index');
    Route::post('/skpd', [SkpdController::class, 'store'])->middleware('hak_akses:skpd.manage');
    Route::put('/skpd/{skpd}', [SkpdController::class, 'update'])->middleware('hak_akses:skpd.manage');
    Route::delete('/skpd/{skpd}', [SkpdController::class, 'destroy'])->middleware('hak_akses:skpd.manage');

    // Master rekening
    Route::get('/rekening', [RekeningController::class, 'index'])->middleware('hak_akses:rekening.view')->name('rekening.index');
    Route::post('/rekening', [RekeningController::class, 'store'])->middleware('hak_akses:rekening.manage');
    Route::put('/rekening/{rekening}', [RekeningController::class, 'update'])->middleware('hak_akses:rekening.manage');
    Route::delete('/rekening/{rekening}', [RekeningController::class, 'destroy'])->middleware('hak_akses:rekening.manage');

    // Impor master SKPD & rekening dari Excel
    Route::get('/skpd/impor', [ImporMasterController::class, 'form'])->defaults('jenis', 'skpd')->middleware('hak_akses:skpd.manage')->name('skpd.impor');
    Route::post('/skpd/impor/pratampil', [ImporMasterController::class, 'pratampil'])->defaults('jenis', 'skpd')->middleware('hak_akses:skpd.manage');
    Route::post('/skpd/impor/simpan', [ImporMasterController::class, 'simpan'])->defaults('jenis', 'skpd')->middleware('hak_akses:skpd.manage');
    Route::get('/skpd/impor/template', [ImporMasterController::class, 'template'])->defaults('jenis', 'skpd')->middleware('hak_akses:skpd.manage');

    Route::get('/rekening/impor', [ImporMasterController::class, 'form'])->defaults('jenis', 'rekening')->middleware('hak_akses:rekening.manage')->name('rekening.impor');
    Route::post('/rekening/impor/pratampil', [ImporMasterController::class, 'pratampil'])->defaults('jenis', 'rekening')->middleware('hak_akses:rekening.manage');
    Route::post('/rekening/impor/simpan', [ImporMasterController::class, 'simpan'])->defaults('jenis', 'rekening')->middleware('hak_akses:rekening.manage');
    Route::get('/rekening/impor/template', [ImporMasterController::class, 'template'])->defaults('jenis', 'rekening')->middleware('hak_akses:rekening.manage');

    // Master jenis kebutuhan
    Route::get('/jenis-kebutuhan', [JenisKebutuhanController::class, 'index'])->middleware('hak_akses:jenis_kebutuhan.view')->name('jenis-kebutuhan.index');
    Route::post('/jenis-kebutuhan', [JenisKebutuhanController::class, 'store'])->middleware('hak_akses:jenis_kebutuhan.manage');
    Route::put('/jenis-kebutuhan/{jenisKebutuhan}', [JenisKebutuhanController::class, 'update'])->middleware('hak_akses:jenis_kebutuhan.manage');
    Route::delete('/jenis-kebutuhan/{jenisKebutuhan}', [JenisKebutuhanController::class, 'destroy'])->middleware('hak_akses:jenis_kebutuhan.manage');

    // Master jenis kebutuhan tambahan
    Route::get('/jenis-kebutuhan-tambahan', [JenisKebutuhanTambahanController::class, 'index'])->middleware('hak_akses:kebutuhan_tambahan.view')->name('jenis-kebutuhan-tambahan.index');
    Route::post('/jenis-kebutuhan-tambahan', [JenisKebutuhanTambahanController::class, 'store'])->middleware('hak_akses:kebutuhan_tambahan.manage');
    Route::put('/jenis-kebutuhan-tambahan/{jenisKebutuhanTambahan}', [JenisKebutuhanTambahanController::class, 'update'])->middleware('hak_akses:kebutuhan_tambahan.manage');
    Route::delete('/jenis-kebutuhan-tambahan/{jenisKebutuhanTambahan}', [JenisKebutuhanTambahanController::class, 'destroy'])->middleware('hak_akses:kebutuhan_tambahan.manage');

    // Tahap
    Route::get('/tahap', [TahapController::class, 'index'])->middleware('hak_akses:tahap.view')->name('tahap.index');
    Route::post('/tahap', [TahapController::class, 'store'])->middleware('hak_akses:tahap.create');
    Route::get('/tahap/{tahap}', [TahapController::class, 'show'])->middleware('hak_akses:tahap.view')->name('tahap.show');
    Route::put('/tahap/{tahap}', [TahapController::class, 'update'])->middleware('hak_akses:tahap.edit');
    Route::post('/tahap/{tahap}/finalkan', [TahapController::class, 'finalkan'])->middleware('hak_akses:tahap.edit');
    Route::post('/tahap/{tahap}/kunci', [TahapController::class, 'kunci'])->middleware('hak_akses:tahap.lock');

    // Anggaran (matriks per tahap)
    Route::get('/tahap/{tahap}/anggaran', [AnggaranController::class, 'index'])->middleware('hak_akses:anggaran.view')->name('anggaran.index');
    Route::get('/tahap/{tahap}/skpd/{skpd}/matriks', [AnggaranController::class, 'matriksSkpd'])->middleware('hak_akses:anggaran.view')->name('anggaran.matriksskpd');
    Route::post('/tahap/{tahap}/anggaran', [AnggaranController::class, 'store'])->middleware('hak_akses:anggaran.edit');
    Route::put('/anggaran/{anggaran}', [AnggaranController::class, 'update'])->middleware('hak_akses:anggaran.edit');
    Route::delete('/anggaran/{anggaran}', [AnggaranController::class, 'destroy'])->middleware('hak_akses:anggaran.edit');
    Route::post('/tahap/{tahap}/salin', [AnggaranController::class, 'salin'])->middleware('hak_akses:anggaran.salin');

    // Impor matriks anggaran dari Excel (level tahap)
    Route::get('/tahap/{tahap}/impor/template', [ImporMatriksController::class, 'template'])->middleware('hak_akses:anggaran.edit');
    Route::post('/tahap/{tahap}/impor/pratampil', [ImporMatriksController::class, 'pratampil'])->middleware('hak_akses:anggaran.edit');
    Route::post('/tahap/{tahap}/impor/simpan', [ImporMatriksController::class, 'simpan'])->middleware('hak_akses:anggaran.edit');

    // Realisasi & impor
    Route::get('/realisasi', [RealisasiController::class, 'index'])->middleware('hak_akses:realisasi.view')->name('realisasi.index');
    Route::get('/realisasi/impor', [RealisasiController::class, 'impor'])->middleware('hak_akses:realisasi.import')->name('realisasi.impor');
    Route::post('/realisasi/impor/pratampil', [RealisasiController::class, 'pratampil'])->middleware('hak_akses:realisasi.import');
    Route::post('/realisasi/impor/simpan', [RealisasiController::class, 'simpan'])->middleware('hak_akses:realisasi.import');

    // Template mapping
    Route::get('/mapping-template', [MappingTemplateController::class, 'index'])->middleware('hak_akses:mapping.view')->name('mapping.index');
    Route::post('/mapping-template', [MappingTemplateController::class, 'store'])->middleware('hak_akses:mapping.manage');
    Route::get('/mapping-template/{mappingTemplate}', [MappingTemplateController::class, 'edit'])->middleware('hak_akses:mapping.view');
    Route::put('/mapping-template/{mappingTemplate}', [MappingTemplateController::class, 'update'])->middleware('hak_akses:mapping.manage');
    Route::post('/mapping-template/{mappingTemplate}/kolom', [MappingTemplateController::class, 'tambahKolom'])->middleware('hak_akses:mapping.manage');
    Route::delete('/mapping-template/kolom/{kolom}', [MappingTemplateController::class, 'hapusKolom'])->middleware('hak_akses:mapping.manage');

    // Laporan & perbandingan
    Route::get('/laporan', [LaporanController::class, 'index'])->middleware('hak_akses:laporan.view')->name('laporan.index');
    Route::get('/laporan/ekspor', [LaporanController::class, 'ekspor'])->middleware('hak_akses:laporan.export');
    Route::get('/perbandingan', [PerbandinganController::class, 'index'])->middleware('hak_akses:laporan.view')->name('perbandingan.index');

    // Pengguna & peran
    Route::get('/pengguna', [PenggunaController::class, 'index'])->middleware('hak_akses:pengguna.view')->name('pengguna.index');
    Route::post('/pengguna', [PenggunaController::class, 'store'])->middleware('hak_akses:pengguna.manage');
    Route::put('/pengguna/{pengguna}', [PenggunaController::class, 'update'])->middleware('hak_akses:pengguna.manage');
    Route::get('/peran', [PeranController::class, 'index'])->middleware('hak_akses:peran.manage')->name('peran.index');
    Route::get('/peran/{peran}/ubah', [PeranController::class, 'edit'])->middleware('hak_akses:peran.manage');
    Route::put('/peran/{peran}', [PeranController::class, 'update'])->middleware('hak_akses:peran.manage');

    // Log audit
    Route::get('/audit', [LogAuditController::class, 'index'])->middleware('hak_akses:audit.view')->name('audit.index');
});
