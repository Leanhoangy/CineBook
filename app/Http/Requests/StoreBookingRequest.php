<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'seats' => ['required', 'array', 'min:1', 'max:'.config('cinema.max_seats_per_booking')],
            'seats.*' => ['required', 'string', 'distinct', 'regex:/^[A-Z]\d{1,2}$/'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'seats.max' => 'Mỗi lần chỉ được đặt tối đa :max ghế.',
        ];
    }

    /** Kiểm tra ghế có thuộc sơ đồ của phòng chiếu hay không */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $room = $this->route('showtime')->room;

                foreach ((array) $this->input('seats', []) as $code) {
                    if (is_string($code) && ! $room->hasSeat($code)) {
                        $validator->errors()->add('seats', "Ghế {$code} không tồn tại trong {$room->name}.");
                    }
                }
            },
        ];
    }
}
