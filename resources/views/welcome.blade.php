<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <!-- Atur karakter teks jadi UTF-8 supaya huruf/angka/simbol aman -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Biar tampilan menyesuaikan layar HP/Laptop -->
    <title>Selamat Datang</title>
    <!-- Import Bootstrap untuk styling bawaan -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* Bagian style untuk desain tampilan */

        body {
            background: linear-gradient(to right, #000000, #265842);
            /* Latar belakang gradasi hitam ke hijau */
            height: 100vh;
            /* Tinggi penuh layar */
            display: flex;
            /* Pakai flexbox biar gampang atur posisi */
            justify-content: center;
            /* Posisi tengah horizontal */
            align-items: center;
            /* Posisi tengah vertikal */
            font-family: 'Segoe UI', sans-serif;
            /* Ganti font biar lebih modern */
            overflow: hidden;
            /* Hilangkan scroll */
            color: #ffffff;
            /* Warna teks default putih */
        }

        .splash-container {
            display: flex;
            flex-direction: column;
            /* Susunan isi dari atas ke bawah */
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 1rem;
        }

        h1,
        p {
            color: #ffffff;
            /* judul & teks berwarna putih */
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
            /* Ukuran loading spinner */
        }

        @media (max-width: 576px) {
            /* Aturan kalau layar kecil (HP) */
            h1 {
                font-size: 1.5rem;
                /* ukuran judul */
            }

            .spinner-border {
                width: 2rem;
                height: 2rem;
                /* Spinner diperkecil */
            }
        }

        .spinner-border.custom-green {
            /* Modifikasi spinner warna */
            border-top-color: #22c55e;
            /* Hijau di atas */
            border-right-color: rgba(255, 255, 255, 0.2);
            border-bottom-color: rgba(255, 255, 255, 0.2);
            border-left-color: rgba(255, 255, 255, 0.2);
            /* Sisanya transparan putih samar */
        }
    </style>
</head>

<body>
    <!-- Halaman splash (loading sebelum ke login) -->
    <div class="splash-container">
        <h1>Sistem Penayangan Konten</h1>
        <p>Mengalihkan ke halaman login...</p>
        <!-- Spinner Bootstrap -->
        <div class="spinner-border text-primary custom-green" role="status">
            <span class="visually-hidden">Loading...</span>
            <!-- Text tersembunyi untuk aksesibilitas -->
        </div>
    </div>

    <script>
        // Script redirect otomatis setelah 2 detik
        setTimeout(function () {
            window.location.href = "{{ route('login') }}";
            // Pindah ke halaman login Laravel
        }, 2000); // 2000 ms = 2 detik
    </script>
</body>

</html>
