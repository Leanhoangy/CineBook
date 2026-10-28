<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Mã giao dịch phía rạp gửi sang VNPay (vnp_TxnRef), cần khi gọi API hoàn tiền
            $table->string('payment_txn_ref', 40)->nullable()->after('payment_ref');
            $table->timestamp('refunded_at')->nullable()->after('payment_txn_ref'); // null = chưa hoàn tiền
            $table->enum('refund_method', ['vnpay', 'manual'])->nullable()->after('refunded_at');
            $table->string('refund_ref', 100)->nullable()->after('refund_method'); // mã GD hoàn VNPay hoặc ghi chú hoàn thủ công
            // Thêm lý do hủy: rạp hủy cả suất chiếu
            $table->enum('cancel_reason', ['customer', 'admin', 'unpaid', 'showtime'])->nullable()->change();
        });

        // Suất chiếu bị rạp hủy được xóa mềm: biến mất khỏi trang khách nhưng vé cũ vẫn xem được
        Schema::table('showtimes', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('showtimes', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['payment_txn_ref', 'refunded_at', 'refund_method', 'refund_ref']);
            $table->enum('cancel_reason', ['customer', 'admin', 'unpaid'])->nullable()->change();
        });
    }
};
