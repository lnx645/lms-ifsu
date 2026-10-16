<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cbt_ujians', function (Blueprint $table): void {
            $table->boolean('kartu_diterbitkan')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('cbt_ujians', function (Blueprint $table): void {
            $table->dropColumn('kartu_diterbitkan');
        });
    }
};
