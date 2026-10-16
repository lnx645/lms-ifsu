<?php

use App\Models\BankSoal;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jejak asal soal ujian dari bank (opsional). Soal tetap snapshot independen;
     * kolom ini hanya penanda sumber, di-null-kan bila bank dihapus.
     */
    public function up(): void
    {
        Schema::table('soals', function (Blueprint $table) {
            $table->foreignIdFor(BankSoal::class)->nullable()->after('ujian_id')
                ->constrained('bank_soals')->cascadeOnUpdate()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('soals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bank_soal_id');
        });
    }
};
