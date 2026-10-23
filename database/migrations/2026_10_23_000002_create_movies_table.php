<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('director', 150)->nullable();
            $table->string('cast')->nullable();
            $table->unsignedSmallInteger('duration'); // phút
            $table->date('release_date');
            $table->enum('age_rating', ['P', 'T13', 'T16', 'T18'])->default('P');
            $table->string('poster')->nullable();
            $table->string('trailer_url')->nullable();
            $table->enum('status', ['now_showing', 'coming_soon', 'ended'])->default('coming_soon');
            $table->timestamps();
            $table->softDeletes();
        });

        // Bảng trung gian quan hệ nhiều-nhiều giữa phim và thể loại
        Schema::create('genre_movie', function (Blueprint $table) {
            $table->foreignId('genre_id')->constrained()->cascadeOnDelete();
            $table->foreignId('movie_id')->constrained()->cascadeOnDelete();
            $table->primary(['genre_id', 'movie_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('genre_movie');
        Schema::dropIfExists('movies');
    }
};
