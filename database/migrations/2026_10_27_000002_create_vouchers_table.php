<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('description')->nullable();
            $table->enum('type', ['percent', 'fixed']); // giảm theo % hoặc giảm số tiền cố định
            $table->unsignedInteger('value');
            $table->unsignedInteger('max_discount')->nullable(); // mức giảm tối đa cho mã giảm %
            $table->unsignedInteger('min_order')->default(0);    // giá trị đơn tối thiểu
            $table->unsignedInteger('usage_limit')->nullable();  // tổng số lượt dùng, null = không giới hạn
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('voucher_id')->nullable()->after('showtime_id')->constrained()->nullOnDelete();
            $table->unsignedInteger('discount')->default(0)->after('total_price'); // total_price là số tiền sau giảm
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('voucher_id');
            $table->dropColumn('discount');
        });

        Schema::dropIfExists('vouchers');
    }
};
