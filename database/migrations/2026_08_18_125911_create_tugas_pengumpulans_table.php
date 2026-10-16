<?php

use App\Models\Tugas;
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
        Schema::create('tugas_pengumpulans', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Tugas::class)->constrained('tugases')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('siswa_nisn', 10);
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('mime_type')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->unique(['tugas_id', 'siswa_nisn']);

            $table->foreign('siswa_nisn')->references('nisn')->on('siswa')->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tugas_pengumpulans');
    }
};
