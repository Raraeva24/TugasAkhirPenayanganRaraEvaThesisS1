<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMonitorContentsTable extends Migration
{
    /**
     * Jalankan migration -> bikin tabel baru di database
     */
    public function up()
    {
        Schema::create('monitor_contents', function (Blueprint $table) {
            $table->id(); 
            // Kolom id (primary key), otomatis naik 1 tiap tambah data

            $table->unsignedBigInteger('content_id'); 
            // Nyimpen id dari konten yang dipantau

            $table->string('id_departments'); 
            // Nyimpen id department (relasi ke tabel departments)

            $table->boolean('is_visible_to_parent')->default(false); 
            // Penanda: konten ini kelihatan nggak buat parent (true/false)

            $table->boolean('is_tayang_request')->default(false); 
            // Penanda: konten ini lagi minta ditayangkan atau enggak

            $table->timestamps(); 
            // Bikin otomatis kolom created_at & updated_at

            // Relasi ke tabel contents, kalau data dihapus -> ikut kehapus
            $table->foreign('content_id')
                  ->references('id')
                  ->on('contents')
                  ->onDelete('cascade');

            // Relasi ke tabel departments, kalau department dihapus -> ikut kehapus
            $table->foreign('id_departments')
                  ->references('id_departments')
                  ->on('departments')
                  ->onDelete('cascade');
        });
    }

    /**
     * Rollback -> kalau migrate dibatalin, tabelnya dihapus
     */
    public function down()
    {
        Schema::dropIfExists('Monitor_contents'); 
        // Hapus tabel monitor_contents
    }
};
