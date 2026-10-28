<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Ai/vì sao hủy vé: khách tự hủy, admin hủy, hệ thống tự hủy do quá hạn thanh toán
            $table->enum('cancel_reason', ['customer', 'admin', 'unpaid'])->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('cancel_reason');
        });
    }
};
