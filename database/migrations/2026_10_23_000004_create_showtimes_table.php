<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('showtimes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained()->restrictOnDelete();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->unsignedInteger('price'); // giá vé thường (VNĐ)
            $table->timestamps();

            $table->index(['room_id', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('showtimes');
    }
};
