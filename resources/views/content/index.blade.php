@extends('layouts.app')
<!-- Extend layout utama Laravel biar template ini ikut pakai struktur layout yang sama -->

@section('content')
    <!-- Bagian konten utama -->

    <div class="carousel-3d-wrapper">
        <div class="carousel-3d" id="carousel3d">
            @php
                // Urutin data konten berdasarkan:
                // 1. Tanggal mulai
                // 2. Jam mulai
                // 3. Durasi
                $sortedContents = $contents->sortBy([
                    fn($a, $b) => strtotime($a->start_date) <=> strtotime($b->start_date),
                    fn($a, $b) => strtotime($a->start_time) <=> strtotime($b->start_time),
                    fn($a, $b) => $a->duration <=> $b->duration,
                ])->values();
            @endphp

            @foreach ($sortedContents as $index => $content)
                @php
                    // Ambil ekstensi file (jpg/png/mp4/dll)
                    $ext = pathinfo($content->file_server, PATHINFO_EXTENSION);
                    // Tambahkan query versi biar cache browser kebaruhi
                    $source = $content->file_url . '?v=' . $initialLastUpdate;

                    // Kalau durasi kosong, default 10 detik
                    $duration = $content->duration ?? 10;
                @endphp

                <!-- Item carousel 3D -->
                <div class="carousel-3d-item {{ $index === 0 ? 'active' : '' }}" 
                     data-index="{{ $index }}"
                     data-duration="{{ $duration }}"
                     data-type="{{ in_array(strtolower($ext), ['mp4', 'webm']) ? 'video' : 'image' }}">
                     
                    @if (in_array(strtolower($ext), ['mp4', 'webm']))
                        <!-- Kalau file video -->
                        <video src="{{ $source }}" playsinline preload="auto" muted></video>
                    @else
                        <!-- Kalau file gambar -->
                        <img src="{{ $source }}" alt="konten">
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Ambil semua item slide
        const items = document.querySelectorAll('.carousel-3d-item');
        // Ambil index terakhir dari localStorage (biar kalau reload mulai dari slide terakhir)
        let current = parseInt(localStorage.getItem('lastSlideIndex') || "0");
        let timeout;

        // Kalau index kebesaran, reset ke 0
        if (current >= items.length) current = 0;

        // Fungsi buat update kelas (active, prev, next)
        function updateClasses() {
            items.forEach((item, index) => {
                item.classList.remove('active', 'prev', 'next');
                if (index === current) item.classList.add('active');
                else if (index === (current - 1 + items.length) % items.length) item.classList.add('prev');
                else if (index === (current + 1) % items.length) item.classList.add('next');
            });
        }

        let userInteracted = false;

        // Fungsi: baru aktifkan audio kalau user sudah klik/touch
        function enableAudioOnInteraction() {
            if (!userInteracted) {
                userInteracted = true;
                const videos = document.querySelectorAll('.carousel-3d-item video');
                videos.forEach(video => {
                    video.muted = false;  // buka mute
                    video.volume = 1.0;   // set volume normal
                });
                document.removeEventListener('click', enableAudioOnInteraction);
                document.removeEventListener('touchstart', enableAudioOnInteraction);
            }
        }

        document.addEventListener('click', enableAudioOnInteraction);
        document.addEventListener('touchstart', enableAudioOnInteraction);

        // Fungsi untuk tampilkan slide
        function showSlide(index) {
            if (timeout) clearTimeout(timeout);
            current = index;
            updateClasses();
            localStorage.setItem('lastSlideIndex', current);

            const item = items[current];
            const isVideo = item.getAttribute('data-type') === 'video';
            const videoEl = item.querySelector('video');

            // Pause semua video lain
            items.forEach(i => {
                const v = i.querySelector('video');
                if (v) {
                    v.pause();
                    v.currentTime = 0;
                }
            });

            if (isVideo && videoEl) {
                // Kalau kontennya video
                videoEl.loop = false;
                videoEl.muted = !userInteracted;
                videoEl.volume = userInteracted ? 1.0 : 0;

                videoEl.play().catch(() => {
                    videoEl.muted = true;
                    videoEl.play();
                });

                // Kalau video selesai → pindah slide
                videoEl.onended = () => nextSlide();

                // Hitung durasi video buat set timeout
                videoEl.addEventListener('loadedmetadata', () => {
                    let durasi = isNaN(videoEl.duration) || !isFinite(videoEl.duration) ? 10 : videoEl.duration;
                    timeout = setTimeout(() => nextSlide(), (durasi + 1) * 1000);
                }, { once: true });
            } else {
                // Kalau kontennya gambar → pakai durasi default
                const duration = parseInt(item.getAttribute('data-duration') || "10") * 1000;
                timeout = setTimeout(nextSlide, duration);
            }
        }

        // Fungsi next slide
        function nextSlide() {
            if (timeout) clearTimeout(timeout);
            current = (current + 1) % items.length;
            showSlide(current);
        }

        // Start carousel kalau ada item
        if (items.length > 0) {
            showSlide(current);
        }

        // ==============================
        // BAGIAN SYNC KONTEN
        // ==============================

        let lastChecksum = localStorage.getItem('lastChecksum') || "{{ $initialChecksum }}";
        let lastUpdate = Number(localStorage.getItem('lastUpdate') || "{{ $initialLastUpdate }}");
        let lastReloadTime = 0;

        // Kirim log ke Laravel
        function logToLaravel(message) {
            fetch("{{ route('frontend.log') }}", {
                method: "POST",
                headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": "{{ csrf_token() }}" },
                body: JSON.stringify({ message: message })
            }).catch(() => { });
        }

        // Reload halaman dengan aman (ada cooldown 5 detik biar gak loop reload)
        function safeReload(reason) {
            const now = Date.now();
            if (now - lastReloadTime > 5000) {
                lastReloadTime = now;
                logToLaravel(`🔁 Reload halaman. Alasan: ${reason}`);
                location.reload();
            } else {
                logToLaravel(`⏸ Reload dibatalkan (cooldown aktif). Alasan: ${reason}`);
            }
        }

        // Fungsi sync konten dengan server
        function syncContents() {
            logToLaravel("🔄 Sinkronisasi konten dimulai...");
            fetch("{{ route('content.syncAll') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
                .then(response => response.json())
                .then(data => {
                    let changed = false;

                    // Cek struktur konten (nambah/hapus)
                    if (data.checksum !== lastChecksum) {
                        logToLaravel(`📦 Struktur konten berubah. Checksum lama: ${lastChecksum}, baru: ${data.checksum}`);
                        lastChecksum = data.checksum;
                        localStorage.setItem('lastChecksum', lastChecksum);
                        changed = true;
                    }

                    // Cek konten update (edit)
                    if (data.last_update !== lastUpdate) {
                        logToLaravel(`✏️ Konten diupdate. LastUpdate lama: ${lastUpdate}, baru: ${data.last_update}`);
                        lastUpdate = data.last_update;
                        localStorage.setItem('lastUpdate', lastUpdate);
                        changed = true;
                    }

                    if (changed) {
                        safeReload("Perubahan konten terdeteksi (checksum/timestamp)");
                    }
                })
                .catch(error => logToLaravel("❌ Sync gagal: " + error.message));
        }

        // Pantau status jaringan
        function updateNetworkStatus() {
            if (navigator.onLine) {
                logToLaravel("🌐 Status jaringan: Online. 📡 Konten dari Web 1.");
                syncContents();
            } else {
                logToLaravel("⚠️ Status jaringan: Offline. 📦 Konten dari database lokal.");
            }
        }
        window.addEventListener('online', updateNetworkStatus);
        window.addEventListener('offline', updateNetworkStatus);
        updateNetworkStatus();

        // Jalanin sync tiap 10 detik
        setInterval(syncContents, 10000);
    });
</script>
@endsection
