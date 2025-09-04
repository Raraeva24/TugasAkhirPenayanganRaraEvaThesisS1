<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SyncService;

class SyncWeb1Data extends Command
{
    protected $signature = 'sync:web1';
    protected $description = 'Sinkronisasi data dari Web 1 ke Web 2';

    public function handle(SyncService $syncService)
    {
        try {
            $syncService->syncAll();
            $this->info('✅ Sinkronisasi semua data Web 1 selesai.');
        } catch (\Exception $e) {
            $this->error('❌ Gagal sinkronisasi: ' . $e->getMessage());
        }
    }
}

