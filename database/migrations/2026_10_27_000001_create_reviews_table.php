<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('movie_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating'); // 1 – 5 sao
            $table->text('content')->nullable();
            $table->boolean('is_hidden')->default(false); // admin ẩn bình luận vi phạm
            $table->timestamps();

            // Mỗi khách chỉ đánh giá một phim một lần (có thể sửa lại)
            $table->unique(['user_id', 'movie_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
