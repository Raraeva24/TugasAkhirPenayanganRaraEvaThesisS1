<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    // Fungsi untuk bikin tabel baru
    public function up()
    {
        Schema::create('Users', function (Blueprint $table) {
            $table->id();  
            // Primary key otomatis naik (1,2,3, dst)

            $table->string('name');  
            // Nama user

            $table->string('email')->unique();  
            // Email user, harus unik (nggak boleh dobel)

            $table->string('password');  
            // Password user (sudah di-hash)

            $table->string('role');  
            // Rol

            $table->string('id_departments');  
            // Relasi ke department (pakai id_departments dari tabel departments)

            $table->uuid('uuid')->nullable()->unique();  
            // UUID unik ( dipakai untuk identifikasi selain id, BUAT CHECKSUM NANTI)

            $table->rememberToken();  
            // Token untuk "ingat saya" saat login, tpi ngga ku pake

            $table->timestamps();  
            // Otomatis bikin kolom created_at dan updated_at
        });
    }

    // Fungsi untuk rollback (hapus tabel kalau migrate dibatalin)
    public function down()
    {
        Schema::dropIfExists('Users');  
        // Hapus tabel Users
    }
}
