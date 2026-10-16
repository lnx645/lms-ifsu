<?php

use App\Models\BankSoal;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Opsi jawaban untuk soal bank (pg/multi/benar_salah).
     */
    public function up(): void
    {
        Schema::create('bank_opsi_soals', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(BankSoal::class)->constrained('bank_soals')->cascadeOnUpdate()->cascadeOnDelete();
            $table->text('teks');
            $table->boolean('benar')->default(false);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->index(['bank_soal_id', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_opsi_soals');
    }
};
