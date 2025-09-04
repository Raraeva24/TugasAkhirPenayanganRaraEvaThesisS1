<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Content;
use Illuminate\Support\Carbon;

class SyncController extends Controller
{
    // Endpoint buat sinkronisasi metadata konten biar client bisa cek update terbaru
    public function syncContents()
    {
        try {
            // Ambil konten yang statusnya aktif biar cuma data valid aja yang diambil
            $contents = Content::where('status', 'active')
                ->select('uuid', 'file_path', 'updated_at') // pilih kolom penting biar lebih ringan
                ->get();

            // Ambil semua uuid terus diurutkan biar konsisten
            // lalu digabung jadi string dan di-md5 biar gampang cek perubahan
            $currentUUIDs = $contents->pluck('uuid')->sort()->values()->toArray();
            $checksum = md5(implode('|', $currentUUIDs));

            // Ambil waktu terakhir data diupdate biar tau kapan terakhir ada perubahan
            $lastUpdated = Content::max('updated_at');

            // Format data konten jadi array simpel biar gampang dipakai service worker
            $files = $contents->map(function ($item) {
                return [
                    'uuid' => $item->uuid, // buat identitas unik konten
                    'url' => url('storage/' . $item->file_path), // buat akses file dari storage
                    'updated_at' => Carbon::parse($item->updated_at)->timestamp // buat info update terakhir
                ];
            });

            // Balikin hasil response JSON biar bisa dipakai client
            return response()->json([
                'success' => true, // buat nandain proses sukses
                'message' => '✅ Metadata sinkronisasi berhasil diambil.', // buat info status
                'checksum' => $checksum, // buat deteksi ada perubahan atau tidak
                'last_updated' => $lastUpdated instanceof Carbon
                    ? $lastUpdated->timestamp // buat ambil timestamp kalau objek Carbon
                    : (is_string($lastUpdated) ? strtotime($lastUpdated) : 0), // buat fallback kalau string/null
                'files' => $files // buat daftar konten yang bisa diakses
            ]);

        } catch (\Exception $e) {
            // Balikin error kalau ada masalah biar client tau
            return response()->json([
                'success' => false, // buat nandain gagal
                'error' => $e->getMessage() // buat kasih tau pesan error
            ], 500);
        }
    }
}
