<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('movie')->canBeReviewedBy($this->user());
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'content' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'Vui lòng chọn số sao.',
            'rating.between' => 'Số sao phải từ 1 đến 5.',
        ];
    }

    public function attributes(): array
    {
        return ['content' => 'nội dung đánh giá'];
    }
}
