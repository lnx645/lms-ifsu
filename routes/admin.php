<?php

use App\Http\Controllers\Admin\AkunAdminController;
use App\Http\Controllers\Admin\AkunGuruController;
use App\Http\Controllers\Admin\AkunSiswaController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DetailPenilaianController;
use App\Http\Controllers\Admin\GuruKelasController;
use App\Http\Controllers\Admin\JurusanController;
use App\Http\Controllers\Admin\KelasController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\MatpelController;
use App\Http\Controllers\Admin\NaikKelasController;
use App\Http\Controllers\Admin\PengajarController;
use App\Http\Controllers\Admin\PenilaianController;
use App\Http\Controllers\Admin\ProfilController;
use App\Http\Controllers\Admin\SiswaController;
use App\Http\Controllers\Admin\SiswaKelasController;
use App\Http\Controllers\Admin\TahunAjaranController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
| Nama route TANPA prefix 'admin.' (group luar menambahkannya), contoh:
| Route::get('kelas')->name('kelas.index') => admin.kelas.index
*/

// -------------------------------------------------------------------
// Dashboard & Profil
// -------------------------------------------------------------------
Route::controller(DashboardController::class)->group(function (): void {
    Route::get('', '__invoke')->name('index');
});

Route::controller(ProfilController::class)->group(function (): void {
    Route::get('profil', 'index')->name('profil.index');
    Route::put('profil', 'update')->name('profil.update');
});

// -------------------------------------------------------------------
// Akun Admin
// -------------------------------------------------------------------
Route::controller(AkunAdminController::class)->group(function (): void {
    Route::get('akun-admin', 'index')->name('akun-admin.index');
    Route::post('akun-admin', 'store')->name('akun-admin.store');
    Route::put('akun-admin/{user}', 'update')->name('akun-admin.update');
    Route::delete('akun-admin/{user}', 'destroy')->name('akun-admin.destroy');
});

// -------------------------------------------------------------------
// Master Data: Kelas, Tahun Ajaran, Jurusan, Mata Pelajaran
// -------------------------------------------------------------------
Route::controller(KelasController::class)->group(function (): void {
    Route::get('kelas', 'index')->name('kelas.index');
    Route::post('kelas', 'store')->name('kelas.store');
    Route::put('kelas/{kelas}', 'update')->name('kelas.update');
    Route::delete('kelas/{kelas}', 'destroy')->name('kelas.destroy');
});

Route::controller(TahunAjaranController::class)->group(function (): void {
    Route::get('tahun-ajaran', 'index')->name('tahun-ajaran.index');
    Route::post('tahun-ajaran', 'store')->name('tahun-ajaran.store');
    Route::put('tahun-ajaran/{tahunAjaran}', 'update')->name('tahun-ajaran.update');
    Route::delete('tahun-ajaran/{tahunAjaran}', 'destroy')->name('tahun-ajaran.destroy');
});

Route::controller(JurusanController::class)->group(function (): void {
    Route::get('jurusan', 'index')->name('jurusan.index');
    Route::post('jurusan', 'store')->name('jurusan.store');
    Route::put('jurusan/{jurusan}', 'update')->name('jurusan.update');
    Route::delete('jurusan/{jurusan}', 'destroy')->name('jurusan.destroy');
});

Route::controller(MatpelController::class)->group(function (): void {
    Route::get('matpel', 'index')->name('matpel.index');
    Route::post('matpel', 'store')->name('matpel.store');
    Route::put('matpel/{matpel}', 'update')->name('matpel.update');
    Route::delete('matpel/{matpel}', 'destroy')->name('matpel.destroy');
});

// -------------------------------------------------------------------
// Pengajar & Penugasan (guru-kelas)
// -------------------------------------------------------------------
Route::resource('pengajar', PengajarController::class)->except(['create', 'edit', 'show']);

Route::controller(AkunGuruController::class)->group(function (): void {
    Route::get('pengajar/{guru}/akun', 'show')->name('pengajar.akun');
    Route::post('pengajar/{guru}/akun', 'store')->name('pengajar.akun.store');
    Route::put('pengajar/{guru}/akun', 'update')->name('pengajar.akun.update');
    Route::delete('pengajar/{guru}/akun', 'destroy')->name('pengajar.akun.destroy');
});

Route::controller(GuruKelasController::class)->group(function (): void {
    Route::get('pengajar/{guru}/penugasan', 'index')->name('pengajar.guru-kelas');
    Route::post('pengajar/{guru}/penugasan', 'store')->name('pengajar.guru-kelas.store');
    Route::put('pengajar/{guru}/penugasan/{guruKelas}', 'update')->name('pengajar.guru-kelas.update');
    Route::delete('pengajar/{guru}/penugasan/{guruKelas}', 'destroy')->name('pengajar.guru-kelas.destroy');
});

// -------------------------------------------------------------------
// Siswa, Akun & Penempatan Kelas
// -------------------------------------------------------------------
Route::resource('siswa', SiswaController::class)->except(['create', 'edit', 'show']);
Route::get('siswa/template', [SiswaController::class, 'template'])->name('siswa.template');
Route::post('siswa/impor', [SiswaController::class, 'impor'])->name('siswa.impor');

Route::controller(AkunSiswaController::class)->group(function (): void {
    Route::get('siswa/{siswa}/akun', 'show')->name('siswa.akun');
    Route::post('siswa/{siswa}/akun', 'store')->name('siswa.akun.store');
    Route::put('siswa/{siswa}/akun', 'update')->name('siswa.akun.update');
    Route::delete('siswa/{siswa}/akun', 'destroy')->name('siswa.akun.destroy');
});

Route::controller(SiswaKelasController::class)->group(function (): void {
    Route::get('siswa/{siswa}/kelas', 'index')->name('siswa.kelas');
    Route::post('siswa/{siswa}/kelas', 'store')->name('siswa.kelas.store');
    Route::put('siswa/{siswa}/kelas/{siswaKelas}', 'update')->name('siswa.kelas.update');
    Route::delete('siswa/{siswa}/kelas/{siswaKelas}', 'destroy')->name('siswa.kelas.destroy');
});

// -------------------------------------------------------------------
// Kenaikan Kelas
// -------------------------------------------------------------------
Route::controller(NaikKelasController::class)->group(function (): void {
    Route::get('naik-kelas', 'index')->name('naik-kelas.index');
    Route::post('naik-kelas/preview', 'preview')->name('naik-kelas.preview');
    Route::post('naik-kelas', 'execute')->name('naik-kelas.execute');
});

// -------------------------------------------------------------------
// Penilaian: Master Jenis Penilaian
// -------------------------------------------------------------------
Route::controller(PenilaianController::class)->group(function (): void {
    Route::get('penilaian', 'index')->name('penilaian.index');
    Route::post('penilaian', 'store')->name('penilaian.store');
    Route::get('penilaian/{penilaian}', 'show')->name('penilaian.show');
    Route::put('penilaian/{penilaian}', 'update')->name('penilaian.update');
    Route::delete('penilaian/{penilaian}', 'destroy')->name('penilaian.destroy');
});

// -------------------------------------------------------------------
// Penilaian: Detail Nilai per Siswa (berbasis penugasan)
// -------------------------------------------------------------------
Route::controller(DetailPenilaianController::class)->group(function (): void {
    Route::get('penilaian/{penilaian}/penugasan', 'filterSiswa')->name('penilaian.penugasan.filter');
    Route::get('penilaian/{penilaian}/penugasan/{guruKelas}/siswa/{siswa}', 'detail')->name('penilaian.penugasan.detail');
    Route::post('penilaian/{penilaian}/penugasan/{guruKelas}/siswa/{siswa}', 'storeNilai')->name('penilaian.penugasan.storeNilai');
});

// -------------------------------------------------------------------
// Laporan: Cetak Seluruh Data (XLSX & PDF)
// -------------------------------------------------------------------
Route::controller(LaporanController::class)->group(function (): void {
    Route::get('laporan', 'index')->name('laporan.index');
    Route::get('laporan/export-xlsx', 'exportXlsx')->name('laporan.export-xlsx');
    Route::get('laporan/export-pdf', 'exportPdf')->name('laporan.export-pdf');
});

