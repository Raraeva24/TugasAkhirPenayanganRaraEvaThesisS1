<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
// use Illuminate\Support\Facades\Storage;
use App\Models\Department;
use App\Models\Content;
use App\Models\MonitorContent;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncService
{
    /**
     * Fungsi utama untuk menjalankan semua sinkronisasi:
     * - Konten (contents)
     * - Departemen (departments)
     * - MonitorContent (hubungan konten ↔ departemen)
     */
    public function syncAll()
    {
        Log::debug("🟡 Memulai syncAll");

        // Sinkronisasi konten terlebih dahulu (karena monitor_contents butuh content)
        $this->syncContents();
        sleep(1); // ➕ beri jeda 1 detik sebelum lanjut ke monitor_contents (untuk menghindari race condition)

        // Sinkronisasi departemen
        $this->syncDepartments();
        sleep(1); // ➕ jeda antar request biar server API tidak overload

        // Sinkronisasi relasi monitor_contents
        $this->syncMonitorContents();
    }

    /**
     * Sinkronisasi tabel departments dari API Web1 → database lokal
     */
    public function syncDepartments()
    {
        Log::debug("🟣 Mulai sinkronisasi departments");

        // Ambil token dari session (untuk autentikasi API)
        $token = session('token');
        if (!$token) {
            Log::error("❌ Token tidak ditemukan. Silakan login ulang.");
            return;
        }

        // Request ke API Web1
        $response = Http::withToken($token)
            ->get(config('services.web1.url') . '/api/departments');

        Log::info("📦 Respons API departments: " . $response->status());

        // Jika gagal (status bukan 200-an)
        if (!$response->successful()) {
            Log::error("❌ Gagal mengambil data departments. Status: " . $response->status());
            return;
        }

        // Ambil data array dari JSON response
        $data = $response->json('data');

        if (!is_array($data)) {
            Log::error("❌ Format data tidak valid untuk departments.");
            return;
        }

        // Loop setiap department dari API
        foreach ($data as $dept) {
            $id = $dept['id_departments'] ?? null;
            $name = $dept['name_departments'] ?? null;

            // Validasi minimal ada ID & nama
            if (!$id || !$name) {
                Log::warning("⚠️ Data department tidak lengkap: " . json_encode($dept));
                continue;
            }

            // Simpan ke DB (update jika sudah ada, insert jika belum)
            Department::updateOrCreate(
                ['id_departments' => $id],
                [
                    'name_departments' => $name,
                    'parent_id' => $dept['parent_id'] ?? null,
                    'uuid' => $dept['uuid'] ?? null,
                ]
            );

            Log::info("✅ Department disimpan/diupdate: {$name} (ID: {$id})");
        }

        Log::info("✅ Sinkronisasi departments selesai.");
    }

    /**
     * Sinkronisasi tabel monitor_contents dari API Web1 → database lokal
     * MonitorContent adalah relasi antara konten (contents) dengan departemen
     */
    public function syncMonitorContents()
    {
        Log::debug("🔵 Mulai sinkronisasi monitor_contents");

        $token = session('token');
        if (!$token) {
            Log::error("❌ Token tidak ditemukan. Silakan login ulang.");
            return;
        }

        $response = Http::withToken($token)
            ->get(config('services.web1.url') . '/api/monitor-contents');

        Log::info("📦 Respons API monitor_contents: " . $response->status());

        if (!$response->successful()) {
            Log::error("❌ Gagal mengambil data monitor_contents. Status: " . $response->status());
            return;
        }

        $body = $response->json();

        $data = $body['body']['data'] ?? $body['data'] ?? null;

        if (!is_array($data)) {
            Log::error("❌ Format data monitor_contents tidak valid.");
            return;
        }

        foreach ($data as $item) {
            $uuid = $item['content_uuid'] ?? null;
            $deptId = $item['id_departments'] ?? null;

            // Cek konten lokal berdasarkan UUID
            $content = Content::where('uuid', $uuid)->first();

            // Jika konten belum sinkron di lokal, skip, di phpmyadmin mysql
            if (!$content) {
                Log::warning("⚠️ Konten {$uuid} belum ada di lokal, monitor_contents dilewati");
                continue;
            }

            // Simpan ke tabel monitor_contents
            MonitorContent::updateOrCreate(
                [
                    'content_id' => $content->id, // pakai ID lokal (bukan UUID API)
                    'id_departments' => $deptId
                ],
                [
                    'is_visible_to_parent' => $item['is_visible_to_parent'] ?? false,
                    'is_tayang_request' => $item['is_tayang_request'] ?? false,
                ]
            );

            Log::info("✅ MonitorContent disimpan: Konten {$content->id} (uuid: {$uuid}) → Departemen {$deptId}");
        }

        Log::info("✅ Sinkronisasi monitor_contents selesai.");
    }

    /**
     * Sinkronisasi tabel contents dari API Web1 → database lokal
     */
    public function syncContents()
    {
        Log::debug("🔵 Masuk syncContents");
        $token = session('token');

        if (!$token) {
            Log::error("❌ Token tidak ditemukan. Silakan login ulang.");
            return;
        }

        // Ambil data user dari session, untuk tahu department_id
        $user = session('user');
        $departmentId = $user['id_departments'] ?? null;

        // Request ke API Web1 dengan filter department_id
        $response = Http::withToken($token)
            ->get(config('services.web1.url') . '/api/contents', [
                'department_id' => $departmentId
            ]);

        Log::info("📦 Respon konten dari Web 1:", [
            'status' => $response->status()
        ]);

        if (!$response->successful()) {
            Log::error("❌ Gagal ambil konten: " . $response->status());
            return;
        }

        $data = $response->json('data');

        if (!is_array($data)) {
            Log::error("❌ Gagal sinkronisasi: data kosong atau format tidak sesuai.");
            return;
        }

        $remoteUUIDs = []; // daftar UUID dari API, dipakai untuk deteksi konten yang sudah dihapus

        foreach ($data as $remote) {
            $uuid = $remote['uuid'];
            $title = $remote['title'];
            $remoteUUIDs[] = $uuid;

            Log::info("📊 Cek konten: {$title}");

            // Cari di lokal apakah konten sudah ada
            $local = Content::where('uuid', $uuid)->first();

            // Tentukan apakah perlu update (kalau belum ada, atau jika versi API lebih baru)
            $shouldUpdate = !$local || $remote['updated_at'] > optional($local)->updated_at;

            if ($shouldUpdate) {
                // Simpan / update metadata konten
                $local = Content::updateOrCreate(
                    ['uuid' => $remote['uuid']], // identifikasi berdasarkan UUID
                    [
                        'title' => $remote['title'],
                        'description' => $remote['description'],
                        'file_server' => $remote['file_server'],
                        'file_url' => $remote['file_url'],
                        'duration' => $remote['duration'],
                        'start_date' => $remote['start_date'],
                        'end_date' => $remote['end_date'],
                        'start_time' => $remote['start_time'],
                        'end_time' => $remote['end_time'],
                        'repeat_days' => $remote['repeat_days'],
                        'modified_by' => $remote['modified_by'],
                        'created_by' => $remote['created_by'],
                        'updated_at' => Carbon::parse($remote['updated_at']),
                        'created_at' => $remote['created_at'],
                    ]
                );

                Log::info("✅ Metadata konten disimpan/diupdate: {$title} ({$uuid})");
            }
        }

        // Hapus konten lokal yang sudah tidak ada di server Web1
        Content::whereNotIn('uuid', $remoteUUIDs)->each(function ($content) {
            Log::info("🗑️ Konten dihapus dari lokal: {$content->title} ({$content->uuid})");
            $content->delete();
        });

        Log::info("✅ Sinkronisasi konten Web 1 selesai.");
    }
}
