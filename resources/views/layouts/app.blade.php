<!DOCTYPE html>
<html lang="id">

<head>
    <!-- Atur jenis huruf biar bisa baca karakter Indonesia -->
    <meta charset="UTF-8">

    <!-- Token keamanan bawaan Laravel, biar form aman -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Biar tampilan pas di HP/tablet (responsif) -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Judul halaman, kalau @yield kosong, default "Sistem Digital Signage" -->
    <title>@yield('title', 'Sistem Digital Signage')</title>

    <!-- Panggil CSS buatan sendiri -->
    <link href="{{ asset('css/index.css') }}" rel="stylesheet">

    <!-- Panggil CSS Bootstrap (tampilan siap pakai) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Panggil icon Font Awesome (ikon logout, dll) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>

<body class="bg-black text-white" style="height: 100vh; overflow: hidden;">
    <!-- bg-black = latar belakang hitam, text-white = tulisan putih
         height:100vh = penuh 1 layar, overflow hidden = jangan bisa discroll -->

    <!-- Navbar atas -->
    <nav class="navbar-custom">

        <!-- Bagian cuaca -->
        <div id="weather" class="weather-info">
            <span class="weather-icon">⛅</span>
            <span class="weather-text">Memuat...</span>
        </div>

        <!-- Bagian tanggal & jam -->
        <div class="clock-info">
            <div id="date-today" class="date">Senin, 23 Juli 2025</div>
            <div id="clock" class="time">12:45:23</div>
        </div>

        <!-- Tombol Logout -->
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-inline">
            @csrf
            <!-- Tombol logout, pakai ikon -->
            <button type="button" class="logout-btn" onclick="confirmLogout()">
                <i class="fas fa-sign-out-alt"></i>
            </button>
        </form>
    </nav>

    {{-- Konten utama, nanti diganti sama @yield --}}
    <main>
        @yield('content')
    </main>

    {{-- Script tambahan (kalau tiap halaman ada JS sendiri) --}}
    @yield('scripts')

    <!-- Script jam & tanggal -->
    <script>
        function updateClock() {
            const now = new Date();

            // Update jam, format 24 jam
            document.getElementById('clock').textContent =
                now.toLocaleTimeString('id-ID', { hour12: false });

            // Update tanggal, contoh: Senin, 23 Juli 2025
            const tanggal = now.toLocaleDateString('id-ID', {
                weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
            });
            document.getElementById('date-today').textContent = tanggal;
        }
        // Jalanin tiap 1 detik
        setInterval(updateClock, 1000);
        updateClock();

        // Ambil data cuaca
        function fetchWeather() {
            fetch("https://wttr.in/?format=%C+%t")
                .then(r => r.text())
                .then(text => {
                    let condition = text.split(' ')[0]; // ambil kondisi cuaca
                    let temp = text.split(' ')[1] || ''; // ambil suhu

                    // Pilih ikon sesuai cuaca
                    let icon = '⛅';
                    if (condition.includes('rain') || condition.includes('hujan')) icon = '🌧️';
                    else if (condition.includes('clear') || condition.includes('cerah')) icon = '☀️';
                    else if (condition.includes('cloud')) icon = '☁️';

                    document.querySelector('.weather-icon').textContent = icon;
                    document.querySelector('.weather-text').textContent = temp;
                })
                .catch(() => {
                    // Kalau gagal ambil data cuaca
                    document.querySelector('.weather-icon').textContent = '⚠️';
                    document.querySelector('.weather-text').textContent = 'N/A';
                });
        }
        fetchWeather();

        // Konfirmasi sebelum logout
        function confirmLogout() {
            if (confirm('Apakah kamu yakin ingin logout?')) {
                document.getElementById('logout-form').submit();
            }
        }
    </script>

    <!-- Area sensor transparan (misalnya buat efek hover/gesture) -->
    <div class="reveal-zone"></div>

    <!-- Tulisan kecil di bawah (watermark) -->
    <footer class="footer-custom">
        © {{ date('Y') }} Sistem Digital Signage
    </footer>
</body>

</html>
