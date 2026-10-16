<?php

use App\Models\Kelas;
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
        Schema::table('siswa_kelas', function (Blueprint $table) {
            $table->string('siswa_nisn', 10)->after('id');
            $table->foreignIdFor(Kelas::class)->after('siswa_nisn')->constrained('kelas')->cascadeOnUpdate()->cascadeOnDelete();
            $table->boolean('active')->default(false)->after('kelas_id');

            $table->unique(['siswa_nisn', 'kelas_id']);
            $table->foreign('siswa_nisn')->references('nisn')->on('siswa')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('siswa_kelas', function (Blueprint $table) {
            $table->dropForeign(['siswa_nisn']);
            $table->dropConstrainedForeignId('kelas_id');
            $table->dropUnique(['siswa_nisn', 'kelas_id']);
            $table->dropColumn(['siswa_nisn', 'active']);
        });
    }
};
