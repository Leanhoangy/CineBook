<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('code', 12)->unique(); // mã vé hiển thị cho khách
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('showtime_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('total_price');
            $table->enum('status', ['confirmed', 'cancelled'])->default('confirmed');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('booking_seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('seat_code', 5); // ví dụ: A1, C12
            $table->enum('seat_type', ['standard', 'vip'])->default('standard');
            $table->unsignedInteger('price');

            $table->unique(['booking_id', 'seat_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_seats');
        Schema::dropIfExists('bookings');
    }
};
