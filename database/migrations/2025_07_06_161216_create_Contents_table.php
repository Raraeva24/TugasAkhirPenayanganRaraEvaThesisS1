<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContentsTable extends Migration
{
    public function up()
    {
        Schema::create('contents', function (Blueprint $table) {
            $table->id();  
            // Primary key otomatis (auto increment)

            $table->string('title');  
            // Judul konten (contoh: "Promo September", "Event Kampus")

            $table->text('description')->nullable();  
            // Deskripsi konten, boleh kosong/null

            $table->string('file_server')->nullable();  
            // Lokasi file (misal path gambar/video di server), boleh kosong

            $table->integer('duration');  
            // Durasi tampil (misalnya dalam detik atau menit)

            $table->date('start_date');  
            // Tanggal mulai tayang

            $table->date('end_date');  
            // Tanggal selesai tayang

            $table->time('start_time');  
            // Jam mulai tayang (contoh: 08:00)

            $table->time('end_time');  
            // Jam selesai tayang (contoh: 17:00)

            $table->string('uuid');  
            // UUID unik buat identifikasi tambahan

            $table->string('repeat_days')->nullable();  
            // Hari berulang (misalnya: "Mon,Tue,Wed"), boleh kosong

            $table->unsignedBigInteger('created_by')->nullable();  
            // ID user yang buat konten, relasi ke tabel users

            $table->unsignedBigInteger('modified_by')->nullable();  
            // ID user yang terakhir ubah konten, relasi ke tabel users

            $table->timestamps();  
            // Otomatis bikin kolom created_at & updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('contents');  
        // Kalau rollback, tabel contents dihapus
    }
}
