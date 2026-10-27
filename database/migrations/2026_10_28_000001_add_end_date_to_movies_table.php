<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Ngày kết thúc chiếu: qua ngày này phim tự chuyển sang "Ngừng chiếu" (để trống = admin tự quản lý) */
    public function up(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->date('end_date')->nullable()->after('release_date');
        });
    }

    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropColumn('end_date');
        });
    }
};
