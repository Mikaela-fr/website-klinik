<?php

use App\Events\AntrianUpdate;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PengaturanController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home.index');
Route::get('/antrian', [HomeController::class, 'antrian'])->name('home.antrian');

Route::get('/update-antrian', function() {
    broadcast(new AntrianUpdate(json_encode([
        'current' => 90,
        'next' => 91
    ])));
    return response()->json(['status' => 'Success!']);
});

Route::get('/pengaturan', [PengaturanController::class, 'index'])->name('pengaturan.index');
Route::get('/pengaturan/multimedia/tambah', [PengaturanController::class, 'tambah'])->name('pengaturan.tambah');
Route::post('/pengaturan/multimedia/tambah', [PengaturanController::class, 'simpan'])->name('pengaturan.simpan');
Route::get('/pengaturan/multimedia/edit/{id}', [PengaturanController::class, 'edit'])->name('pengaturan.edit');
Route::put('/pengaturan/multimedia/edit/{id}', [PengaturanController::class, 'update'])->name('pengaturan.update');
Route::delete('/pengaturan/multimedia/hapus/{id}', [PengaturanController::class, 'hapus'])->name('pengaturan.hapus');
Route::get('/pengaturan/multimedia/ubah-urutan/{id}', [PengaturanController::class, 'ubahUrutan'])->name('pengaturan.ubah-urutan');

Route::post('/pengaturan/teks/tambah', [PengaturanController::class, 'tambahTeks'])->name('pengaturan.tambah-teks');
Route::put('/pengaturan/teks/ubah/{id}', [PengaturanController::class, 'ubahTeks'])->name('pengaturan.ubah-teks');
Route::get('/pengaturan/teks/hapus/{id}', [PengaturanController::class, 'hapusTeks'])->name('pengaturan.hapus-teks');