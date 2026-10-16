<?php

use App\Models\Matpel;
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
        Schema::table('guru_kelas', function (Blueprint $table) {
            $table->foreignIdFor(Matpel::class)->nullable()->constrained('matpels')->cascadeOnUpdate()->nullOnDelete()->after('kelas_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guru_kelas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('matpel_id');
        });
    }
};
