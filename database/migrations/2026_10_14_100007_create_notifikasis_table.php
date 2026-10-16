<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Notifikasi dalam aplikasi (mis. jadwal ujian baru, penugasan sesi).
     */
    public function up(): void
    {
        Schema::create('notifikasis', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipe', 30)->default('info');
            $table->string('judul', 255);
            $table->text('pesan')->nullable();
            $table->string('tautan', 255)->nullable();
            $table->timestamp('dibaca_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'dibaca_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasis');
    }
};
