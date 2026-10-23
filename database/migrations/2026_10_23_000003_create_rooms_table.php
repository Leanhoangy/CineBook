<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->unsignedTinyInteger('total_rows');     // số hàng ghế (A, B, C...)
            $table->unsignedTinyInteger('seats_per_row');  // số ghế mỗi hàng
            $table->unsignedTinyInteger('vip_rows')->default(0); // số hàng VIP tính từ cuối phòng
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
