<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\TeknisiController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\PettyCashController;
use App\Http\Controllers\SaldoTeknisiController;
use App\Http\Controllers\SlipGajiController;
use App\Http\Controllers\LaporanKeuanganController;
use App\Http\Controllers\SaldoKantorController;

// --- Route publik, TIDAK butuh login ---
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// --- Semua route di bawah ini WAJIB pakai token (Bearer) ---
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::apiResource('barang', BarangController::class);
    Route::apiResource('teknisi', TeknisiController::class);
    Route::apiResource('transaksi', TransaksiController::class);
    Route::post('/transaksi/{transaksi}/dokumentasi', [TransaksiController::class, 'uploadDokumentasi']);
    Route::delete('/transaksi/{transaksi}/dokumentasi/{dokumentasi}', [TransaksiController::class, 'deleteDokumentasi']);
    Route::put('/barang/{barang}/stok', [BarangController::class, 'adjustStok']);

    Route::get('/kas-kecil', [PettyCashController::class, 'kasKecilIndex']);
    Route::post('/kas-kecil', [PettyCashController::class, 'kasKecilStore']);
    Route::delete('/kas-kecil/{pettyCash}', [PettyCashController::class, 'destroy']);

    Route::get('/teknisi-saldo', [SaldoTeknisiController::class, 'index']);
    Route::post('/teknisi-saldo', [SaldoTeknisiController::class, 'store']);
    Route::delete('/teknisi-saldo/{saldoTeknisi}', [SaldoTeknisiController::class, 'destroy']);

    Route::post('/slip-gaji/{teknisi}/generate', [SlipGajiController::class, 'generate']);
    Route::post('/slip-gaji/{teknisi}/bayar', [SlipGajiController::class, 'bayar']);

    Route::get('/laporan-keuangan/rincian', [LaporanKeuanganController::class, 'rincian']);
    Route::get('/laporan-keuangan', [LaporanKeuanganController::class, 'index']);

    Route::get('/saldo-kantor', [SaldoKantorController::class, 'index']);
    Route::post('/saldo-kantor', [SaldoKantorController::class, 'store']);
    Route::delete('/saldo-kantor/{saldoKantor}', [SaldoKantorController::class, 'destroy']);

});