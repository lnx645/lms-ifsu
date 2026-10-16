<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->unsignedBigInteger('guru_id')->nullable()->change();
            $table->unsignedBigInteger('jurusan_id')->nullable()->change();
            $table->unsignedBigInteger('parent_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->unsignedBigInteger('guru_id')->nullable(false)->change();
            $table->unsignedBigInteger('jurusan_id')->nullable(false)->change();
            $table->unsignedBigInteger('parent_id')->nullable(false)->change();
        });
    }
};
