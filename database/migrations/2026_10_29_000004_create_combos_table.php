<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bắp nước bán kèm vé
        Schema::create('combos', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('description')->nullable();
            $table->unsignedInteger('price');
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Combo khách mua trong từng đơn: lưu tên + giá tại thời điểm đặt để lịch sử không đổi khi admin sửa giá
        Schema::create('booking_combos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('combo_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 100);
            $table->unsignedInteger('price');
            $table->unsignedTinyInteger('quantity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_combos');
        Schema::dropIfExists('combos');
    }
};
