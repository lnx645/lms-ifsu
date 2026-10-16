<?php

use App\Models\Guru;
use App\Models\GuruKelas;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tugases', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Guru::class)->constrained('gurus')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignIdFor(GuruKelas::class)->constrained('guru_kelas')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->timestamp('tanggal_terbit')->nullable();
            $table->timestamp('deadline')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('mime_type')->nullable();
            $table->timestamps();

            $table->index(['guru_kelas_id', 'tanggal_terbit', 'deadline']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tugases');
    }
};
