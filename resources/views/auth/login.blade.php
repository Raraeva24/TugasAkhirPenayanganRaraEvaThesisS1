<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"> <!-- ngatur encoding biar huruf & simbol kebaca -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- bikin tampilan responsif di HP -->
    <title>Login - Sistem Penayangan</title> <!-- judul tab browser -->
    
    <!-- Import CSS Bootstrap biar gampang styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* Styling dasar untuk body (background + layout tengah) */
        body {
            background: linear-gradient(to right, #000000, #265842); /* gradasi hitam ke hijau */
            height: 100vh; /* tinggi penuh layar */
            display: flex; /* flexbox */
            justify-content: center; /* konten di tengah horizontal */
            align-items: center; /* konten di tengah vertical */
            font-family: 'Segoe UI', sans-serif; /* font modern */
            overflow: hidden; /* biar ga ada scroll */
        }

        /* Card/form login */
        .login-card {
            background-color: white; /* card putih */
            padding: 2.5rem; /* jarak dalam */
            border-radius: 1rem; /* sudut rounded */
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15); /* bayangan */
            width: 100%;
            max-width: 400px; /* biar ga terlalu lebar */
            opacity: 0; /* awalnya transparan */
            transform: translateY(30px); /* geser dikit ke bawah */
            animation: fadeInUp 0.8s ease forwards; /* animasi masuk */
        }

        /* Animasi card naik + fade in */
        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Judul login */
        .login-title {
            font-size: 1.75rem; /* agak besar */
            font-weight: 600; /* tebal */
            text-align: center;
            margin-bottom: 1.5rem;
            color: #12823d; /* hijau */
        }

        /* Efek saat input fokus */
        .form-control:focus {
            box-shadow: none; /* hilang glow default */
            border-color: #021f5d; /* border biru tua */
        }

        /* Tombol utama */
        .btn-primary {
            background-color: #12823d; /* hijau */
            border: none;
        }

        .btn-primary:hover {
            background-color: #49b071; /* hijau lebih terang pas hover */
        }

        /* Ukuran kecil untuk pesan error */
        .text-danger.small {
            font-size: 0.8rem;
        }
    </style>
</head>
<body>

    <!-- Bungkus form login -->
    <div class="login-card">
        <div class="login-title">Masuk ke Sistem</div> <!-- Judul -->

        <!-- Kalau ada error dari session (misal gagal login), tampilkan alert -->
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <!-- Form login -->
        <form method="POST" action="{{ route('login') }}">
            @csrf <!-- token keamanan Laravel -->

            <!-- Input email -->
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input 
                    type="email" 
                    class="form-control" 
                    id="email" 
                    name="email" 
                    required 
                    autofocus 
                    value="{{ old('email') }}"> <!-- isi ulang email kalau form gagal -->
                @error('email')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <!-- Input password -->
            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <input 
                    type="password" 
                    class="form-control" 
                    id="password" 
                    name="password" 
                    required>
                @error('password')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <!-- Tombol login -->
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
    </div>

</body>
</html>
