<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cbt_soals', function (Blueprint $table) {
            if (!Schema::hasColumn('cbt_soals', 'detail')) {
                $table->json('detail')->nullable()->after('pertanyaan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cbt_soals', function (Blueprint $table) {
            $table->dropColumn('detail');
        });
    }
};
