<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDepartmentsTable extends Migration
{
    public function up()
    {
        Schema::create('Departments', function (Blueprint $table) {
            $table->string('id_departments')->primary();  
            // Primary key pakai string, bukan auto increment (contoh: "D01"")

            $table->string('name_departments');  
            // Nama department (contoh: prodi ti)

            $table->string('parent_id')->nullable();  
            // Untuk bikin struktur hirarki (departemen induk). Boleh kosong/null.

            $table->uuid('uuid')->nullable()->unique();  
            // UUID unik untuk identifikasi tambahan

            $table->timestamps();  
            // Bikin kolom created_at & updated_at otomatis
        });
    }

    public function down()
    {
        Schema::dropIfExists('Departments');  
        // Kalau rollback, tabel Departments dihapus
    }
}
