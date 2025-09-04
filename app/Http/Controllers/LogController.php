<?php
// app/Http/Controllers/LogController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogController extends Controller
{
    // method buat nerima log dari frontend biar bisa dicatat ke laravel.log
    public function frontendLog(Request $request)
    {
        // simpan pesan log yang dikirim frontend biar ada jejaknya di server
        Log::info('[FRONTEND] ' . $request->input('message'));

        // balikin respon sukses biar frontend tau log udah diterima
        return response()->json(['status' => 'logged']);
    }
}
