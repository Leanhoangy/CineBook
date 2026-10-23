<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\Movie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MovieSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_filters_movies_and_keeps_keyword_when_paginating(): void
    {
        Movie::factory()->count(10)->sequence(fn ($s) => ['title' => 'Siêu nhân '.$s->index])->create();
        Movie::factory()->create(['title' => 'Phim khác hẳn']);

        $response = $this->get(route('movies.index', ['q' => 'Siêu nhân']));

        $response->assertOk()
            ->assertSeeInOrder(['Tìm thấy', '10', 'phim cho từ khóa'])
            ->assertDontSee('Phim khác hẳn')
            ->assertSee('q=Si%C3%AAu%20nh%C3%A2n&amp;page=2', false); // link trang 2 vẫn giữ từ khóa
    }

    public function test_filter_by_genre(): void
    {
        $action = Genre::create(['name' => 'Hành động']);
        $movie = Movie::factory()->create(['title' => 'Phim hành động']);
        $movie->genres()->attach($action);
        Movie::factory()->create(['title' => 'Phim tình cảm']);

        $this->get(route('movies.index', ['the-loai' => $action->slug]))
            ->assertSee('Phim hành động')
            ->assertDontSee('Phim tình cảm');
    }

    public function test_live_search_api_returns_json(): void
    {
        $movie = Movie::factory()->create(['title' => 'Interstellar']);

        $this->getJson(route('movies.suggest', ['q' => 'inter']))
            ->assertOk()
            ->assertJsonFragment(['title' => 'Interstellar', 'url' => route('movies.show', $movie)]);
    }
}
