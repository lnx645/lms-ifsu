<?php

use App\Models\Guru;
use App\Models\Matpel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bank soal: kumpulan soal template per mata pelajaran, dipakai bersama
     * guru pengampu matpel yang sama. Soal disalin (snapshot) saat dipakai di ujian.
     */
    public function up(): void
    {
        Schema::create('bank_soals', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Matpel::class)->constrained('matpels')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignIdFor(Guru::class)->nullable()->constrained('gurus')->cascadeOnUpdate()->nullOnDelete();
            $table->string('tipe', 15); // pg, multi, benar_salah, isian, esai
            $table->text('pertanyaan');
            $table->unsignedSmallInteger('poin')->default(1);
            $table->string('topik')->nullable();
            $table->string('kesulitan', 10)->default('sedang'); // mudah, sedang, sulit
            $table->json('kunci_isian')->nullable();
            $table->boolean('isian_case_sensitive')->default(false);
            $table->timestamps();

            $table->index(['matpel_id', 'tipe']);
            $table->index('topik');
            $table->index('kesulitan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_soals');
    }
};
