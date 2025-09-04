<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;


class AuthController extends Controller
{
    public function showLoginForm()
{
    return view('auth.login');
}

  public function login(Request $request)
{
    // ✅ Validasi lokal
    $request->validate([
        'email' => [
            'required',
            'email',
            'regex:/^[a-zA-Z0-9._%+-]+@gmail\.com$/i'
        ],
        'password' => ['required', 'min:8'],
    ], [
        'email.required' => 'Email wajib diisi.',
        'email.email' => 'Format email tidak valid.',
        'email.regex' => 'Email harus menggunakan ji mail yang valid. Contoh: example@gmail.com',
        'password.required' => 'Password wajib diisi.',
        'password.min' => 'Password minimal 8 karakter.',
    ]);

    // ✅ Coba login ke Web 1
    $response = Http::post(config('services.web1.url') . '/api/login', [
        'email' => $request->email,
        'password' => $request->password,
    ]);

    // ✅ Jika berhasil
    if ($response->successful()) {
        $data = $response->json();

        session([
            'token' => $data['token'],
            'user' => $data['user'],
       
        ]);

          

        return redirect()->route('content.index');
    }

    // ❌ Jika gagal: email/password salah (tidak spesifik)
    return back()->with('error', 'Email atau password salah.');
}

    public function logout(Request $request)
{
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
}
}
