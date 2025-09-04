<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\Department;
use Carbon\Carbon;
use App\Services\SyncService;

class ContentController extends Controller
{
    /**
     * Tampilkan konten yang sedang tayang
     * - konten buatan sendiri
     * - konten dari anak departemen yang visible + tayang_request
     */
    public function index()
    {
        $user = session('user');
        if (!$user) {
            // redirect biar user yang belum login dipaksa login dulu
            return redirect()->route('login')->withErrors(['auth' => 'Anda belum login']);
        }

        // ambil waktu sekarang biar bisa cek jadwal konten
        $today = Carbon::now();
        $nowDate = $today->toDateString();
        $nowDay = $today->dayOfWeekIso;

        // ambil id departemen user biar tau anak-anaknya
        $deptId = $user['id_departments'];
        $childIds = Department::where('parent_id', $deptId)->pluck('id_departments')->toArray();

        // ambil semua konten, baik buatan sendiri atau dari anak departemen
        $contents = Content::with('departments')
            ->where(function ($query) use ($user, $childIds) {
                // buat filter konten milik user
                $query->where('created_by', $user['id'])
                    // atau konten dari anak departemen yang visible + tayang_request
                    ->orWhereHas('departments', function ($q) use ($childIds) {
                        $q->whereIn('departments.id_departments', $childIds)
                            ->where('monitor_contents.is_visible_to_parent', true)
                            ->where('monitor_contents.is_tayang_request', true);
                    });
            })
            ->orderBy('id') // urutin biar stabil tampilnya
            ->orderBy('start_date')
            ->orderBy('end_date')
            ->orderBy('start_time')
            ->orderBy('end_time')
            ->orderBy('duration')
            ->get();

        // looping tiap konten buat tentuin status tayangnya
        $contents->each(function ($content) use ($today) {
            $nowDate = $today->toDateString();
            $nowDay = $today->dayOfWeekIso;

            // ambil jadwal konten
            $startDate = $content->start_date;
            $endDate = $content->end_date ?? $startDate;
            $startTime = $content->start_time ?? '00:00';
            $endTime = $content->end_time ?? '23:59';

            // gabungkan tanggal + jam biar gampang dibandingin
            $startDateTime = Carbon::parse($startDate . ' ' . $startTime);
            $endDateTime = Carbon::parse($endDate . ' ' . $endTime);

            // cek apakah hari ini masuk ke repeat_days konten
            $repeatDays = explode(',', $content->repeat_days ?? '');
            $isRepeatToday = in_array($nowDay, $repeatDays);

            // kasih status konten sesuai waktunya
            if ($today->lt($startDateTime)) {
                $content->status = 'Akan Tayang'; // belum mulai
            } elseif ($today->gt($endDateTime)) {
                $content->status = 'Sudah Selesai'; // sudah lewat
            } elseif ($isRepeatToday || $startDate == $nowDate || $endDate == $nowDate) {
                if ($today->between($startDateTime, $endDateTime)) {
                    $content->status = 'Sedang Tayang'; // pas waktunya
                } else {
                    $content->status = 'Akan Tayang'; // belum jamnya
                }
            } else {
                $content->status = 'Akan Tayang';
            }
        });

        // ambil hanya konten yang statusnya sedang tayang
        $contents = $contents->filter(fn($c) => $c->status === 'Sedang Tayang')->values();

        // buat checksum biar bisa tau kalau daftar konten berubah
        $initialUUIDs = $contents->pluck('uuid')->sort()->values()->toArray();
        $initialChecksum = md5(implode('|', $initialUUIDs));

        // ambil timestamp terakhir update konten
        $lastUpdated = $contents->max('updated_at');
        $initialLastUpdate = $lastUpdated instanceof \Carbon\Carbon
            ? $lastUpdated->timestamp
            : (is_string($lastUpdated) ? strtotime($lastUpdated) : 0);

        // kirim ke view biar bisa dipakai di frontend
        return view('content.index', [
            'contents' => $contents,
            'initialChecksum' => $initialChecksum,
            'initialLastUpdate' => $initialLastUpdate,
        ]);
    }

    // method buat sinkronisasi data ke lokal biar update sama web pusat
    public function syncAll(SyncService $sync)
    {
        \Log::info('➡️ MASUK KE syncAll');

        $token = session('token');
        $user = session('user');
        if (!$token || !$user) {
            // balikin error kalau belum login / token kosong
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        try {
            // jalankan service sinkronisasi
            $sync->syncAll();

            $today = Carbon::now();
            $nowDate = $today->toDateString();
            $nowDay = $today->dayOfWeekIso;

            // ambil semua anak departemen biar bisa cek konten turunan
            $childIds = Department::where('parent_id', $user['id_departments'])->pluck('id_departments')->toArray();

            // ambil semua kandidat konten (punya sendiri + dari anak departemen)
            $candidates = Content::with(['departments' => fn($q) => $q->withPivot(['is_visible_to_parent', 'is_tayang_request', 'updated_at'])])
                ->where(function ($q) use ($user, $childIds) {
                    $q->where('created_by', $user['id'])
                        ->orWhereHas('departments', function ($q2) use ($childIds) {
                            $q2->whereIn('departments.id_departments', $childIds)
                                ->where('monitor_contents.is_visible_to_parent', true)
                                ->where('monitor_contents.is_tayang_request', true);
                        });
                })->get();

            // filter kandidat biar hanya konten yang sedang tayang
            $visible = $candidates->filter(function ($content) use ($today, $nowDate, $nowDay) {
                $startDate = $content->start_date;
                $endDate = $content->end_date ?? $startDate;
                $repeatDays = $content->repeat_days ? explode(',', $content->repeat_days) : [];
                $isRepeatToday = in_array($nowDay, $repeatDays);
                $startDateTime = Carbon::parse($startDate . ' ' . $content->start_time);
                $endDateTime = Carbon::parse($endDate . ' ' . ($content->end_time ?? '23:59:59'));

                // konten dianggap tayang kalau waktunya pas dan repeat cocok
                return $today->between($startDateTime, $endDateTime) &&
                    ($isRepeatToday || empty($repeatDays));
            });

            if ($visible->isEmpty()) {
                // kalau gak ada konten tayang, kasih kosong
                $checksum = '';
                $timestamp = 0;
            } else {
                // bikin checksum biar tau kalau ada perubahan konten
                $currentUUIDs = $visible->pluck('uuid')->sort()->values()->toArray();
                $checksum = md5(implode('|', $currentUUIDs));

                // ambil update terakhir dari konten
                $lastUpdated = $visible->max('updated_at');
                $contentTs = $lastUpdated instanceof \Carbon\Carbon
                    ? $lastUpdated->timestamp
                    : (is_string($lastUpdated) ? strtotime($lastUpdated) : 0);

                // ambil update terakhir dari pivot monitor_contents
                $pivotMax = \DB::table('monitor_contents')
                    ->whereIn('id_departments', $childIds)
                    ->max('updated_at');
                $pivotTs = $pivotMax ? strtotime($pivotMax) : 0;

                // pilih yang paling baru
                $timestamp = max($contentTs, $pivotTs);
            }

            \Log::info('✅ Berhasil sinkronisasi');

            // balikin respon JSON biar frontend bisa tau perubahan
            return response()->json([
                'success' => true,
                'message' => 'Sinkronisasi berhasil',
                'last_update' => $timestamp,
                'checksum' => $checksum,
            ]);

        } catch (\Exception $e) {
            // tangkap error biar gak bikin crash
            \Log::error("❌ Error syncAll: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
