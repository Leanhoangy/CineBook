<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('paid_at')->nullable()->after('status'); // null = chưa thanh toán
            $table->enum('payment_method', ['vnpay', 'counter'])->nullable()->after('paid_at');
            $table->string('payment_ref', 50)->nullable()->after('payment_method'); // mã giao dịch VNPay
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['paid_at', 'payment_method', 'payment_ref']);
        });
    }
};
