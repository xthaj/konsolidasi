<?php

use App\Http\Controllers\DataController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VisualisasiController;
use App\Http\Controllers\RekonsiliasiController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

//visualisasi
Route::get('/viz', [VisualisasiController::class, 'create'])->name('visualisasi.create');

//data
Route::get('/data/edit', [DataController::class, 'edit'])->name('data.edit');
Route::get('/data/upload', [DataController::class, 'create'])->name('data.create');

// Rekonsiliasi
Route::get('/rekonsiliasi/pemilihan', [RekonsiliasiController::class, 'pemilihan'])->name('rekon.pemilihan');
Route::get('/rekonsiliasi/progres', [RekonsiliasiController::class, 'progres'])->name('rekon.progres');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
