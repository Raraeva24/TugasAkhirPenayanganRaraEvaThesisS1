<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContentController;
use App\Models\Content;


// Kalau buka URL utama "/", tampilkan halaman welcome
Route::get('/', function () {
    return view('welcome');
});

// Halaman dashboard, cuma bisa dibuka kalau user udah login
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');


// ===== AUTH (Login / Logout) =====

// Tampilkan form login
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');

// Proses data login (cek email & password)
Route::post('/login', [AuthController::class, 'login']);

// Logout versi Closure (langsung ditulis di sini, nggak lewat controller)
Route::post('/logout', function () {
    // Hapus session login user
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    // Setelah logout, balik lagi ke halaman login
    return redirect('/login');
})->name('logout');


// ===== KONTEN (Slideshow/Carousel) =====

// Tampilkan halaman konten (nanti isinya slideshow)
Route::get('/contents', [ContentController::class, 'index'])->name('content.index');

// API khusus untuk ambil daftar konten (dipakai service worker / frontend)
Route::get('/api/contents', function (Request $request) {
    // Ambil token dari header Authorization
    $token = $request->header('Authorization');

    // Buang teks "Bearer " biar tinggal token aja
    $token = str_replace('Bearer ', '', $token);

    // Cek token ke tabel sw_tokens
    $row = \DB::table('sw_tokens')->where('token', $token)->first();

    // Kalau token tidak valid -> balikin error 401 (Unauthorized)
    if (!$row) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    // Ambil semua konten dengan status "tayang" (ditampilkan di layar)
    $contents = \App\Models\Content::where('status', 'tayang')
        ->orderBy('updated_at', 'desc')
        ->get();

    // Kirim data konten ke frontend dalam bentuk JSON
    return response()->json([
        'contents' => $contents
    ]);
});


// ===== SYNC DATA =====

// Endpoint manual buat test sinkronisasi (dipanggil manual, buat debugging kalo ngga bisa sync otomatis huaaa 😭😭☝🏻)
Route::get('/sync-test', function () {
    app(\App\Services\SyncService::class)->syncAll();
    return '✅ Sync all done.';
})->middleware('auth');

// Sinkronisasi penuh (konten + departemen + relasi monitor_content)
Route::post('/sync-all', [ContentController::class, 'syncAll'])->name('content.syncAll');


// ===== LOG FRONTEND =====

// Simpan log dari frontend (misal error JS, info konten tayang, dll) ke laravel.log
Route::post('/log-frontend', [\App\Http\Controllers\LogController::class, 'frontendLog'])->name('frontend.log');


// ===== DATA JSON (Versi Ringkas) =====

// Ambil data konten sederhana (uuid, file_url, file_server, updated_at) dalam format JSON
Route::get('/contents.json', function () {
    $contents = Content::select('uuid', 'file_url', 'file_server', 'updated_at')->get();
    return response()->json($contents);
});
