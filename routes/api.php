<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MasterDataController;
use App\Http\Middleware\ApiKeyMiddleware;

Route::middleware(ApiKeyMiddleware::class)->group(function () {
    Route::get("tahun-ajaran", [MasterDataController::class, "tahunAjaran"]);
    Route::get("jurusan", [MasterDataController::class, "jurusan"]);
    Route::get("kelas", [MasterDataController::class, "kelas"]);
    Route::get("mata-pelajaran", [MasterDataController::class, "mataPelajaran"]);
    Route::get("guru", [MasterDataController::class, "guru"]);
    Route::get("siswa", [MasterDataController::class, "siswa"]);
    Route::get("kelas/{id}/siswa", [MasterDataController::class, "siswaByKelas"]);
});
