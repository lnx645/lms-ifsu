<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->foreignId('matpel_id')->nullable()->after('nama')->constrained('matpels')->nullOnDelete();
            $table->foreignId('guru_id')->nullable()->after('matpel_id')->constrained('gurus')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropConstrainedForeignId('matpel_id');
            $table->dropConstrainedForeignId('guru_id');
        });
    }
};
