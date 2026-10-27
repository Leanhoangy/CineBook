<?php

namespace App\Http\Requests\Admin;

use App\Models\Movie;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MovieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:20'],
            'director' => ['nullable', 'string', 'max:150'],
            'cast' => ['nullable', 'string', 'max:255'],
            'duration' => ['required', 'integer', 'min:30', 'max:300'],
            'release_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:release_date'],
            'age_rating' => ['required', Rule::in(array_keys(Movie::AGE_RATINGS))],
            'status' => ['required', Rule::in(array_keys(Movie::STATUSES))],
            'trailer_url' => ['nullable', 'url', 'max:255'],
            'poster' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'remove_poster' => ['nullable', 'boolean'],
            'genres' => ['required', 'array', 'min:1'],
            'genres.*' => ['integer', 'exists:genres,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'duration.min' => 'Thời lượng phim phải từ 30 phút trở lên.',
            'poster.max' => 'Poster không được lớn hơn 3MB.',
        ];
    }
}
