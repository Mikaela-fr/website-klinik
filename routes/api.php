<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PasienController;
use App\Http\Controllers\Api\PenyakitController;
use App\Http\Controllers\Api\RegionController;
use App\Http\Controllers\Api\RekamMedisPasienController;
use App\Http\Controllers\Api\SatuanObatController;
use App\Http\Controllers\Api\SKDController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\TipeObatController;
use App\Http\Controllers\ObatController;
use App\Http\Controllers\ResepObatController;
use App\Http\Responses\CommonResponse;
use App\Models\Obat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('api.login');
Route::get('/regions/{parent}', [RegionController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        $user = User::where('kode', $request->user()->kode)->with('roles')->first();
        return CommonResponse::ok($user->toArray());
    });
    Route::post('/logout', [AuthController::class, 'logout']);

    // --- API Insights/Stats Routes (tidak perlu diubah) ---
    Route::prefix('stats')->group(function () {
        Route::get('/kunjungan-pasien', [StatsController::class, 'kunjunganPasien']);
        Route::get('/waktu-tunggu', [StatsController::class, 'waktuTunggu']);
        Route::get('/jenis-tren-penyakit', [StatsController::class, 'jenisTrenPenyakit']);
        Route::get('/pendapatan-pengeluaran', [StatsController::class, 'pendapatanPengeluaran']);
        Route::get('/margin-keuntungan', [StatsController::class, 'marginKeuntungan']);
        Route::get('/inventory-turnover-rate', [StatsController::class, 'inventoryTurnoverRate']);
    });

    Route::get('/medical-records', [RekamMedisPasienController::class, 'indexAll']);
    Route::get('/medical-records/{recordId}', [RekamMedisPasienController::class, 'showIndependent']);
    Route::delete('/medical-records/{recordId}', [RekamMedisPasienController::class, 'destroy']);
    Route::put('/medical-records/{recordId}/change-status', [RekamMedisPasienController::class, 'changeStatus']);
    Route::put('/medical-records/{recordId}/prescriptions/pay', [ResepObatController::class, 'pay']);

    Route::get('/skd/current-numbers', [SKDController::class, 'getCurrentNumbers']);
    Route::post('/skd', [SKDController::class, 'saveLetter']);
    Route::get('/skd/detail', [SKDController::class, 'show']);

    Route::apiResource('medical-records/{recordId}/prescriptions', ResepObatController::class);
    Route::apiResource('patients', PasienController::class);
    Route::apiResource('patients/{pasienId}/records', RekamMedisPasienController::class);
    Route::apiResource('medicines', ObatController::class);
    Route::get('/medicines-available', [ObatController::class, 'indexAvailable']);
    Route::get('/diagnoses', [PenyakitController::class, 'index']);

    Route::apiResource('medicine-types', TipeObatController::class);
    Route::apiResource('medicine-units', SatuanObatController::class);
});
