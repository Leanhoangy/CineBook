<?php

namespace App\Http\Requests;

use App\Models\Combo;
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
            'voucher_code' => ['nullable', 'string', 'max:30'],
            'combos' => ['nullable', 'array'],                                   // [combo_id => số lượng]
            'combos.*' => ['integer', 'min:0', 'max:'.Combo::MAX_QUANTITY],
        ];
    }

    public function messages(): array
    {
        return [
            'seats.max' => 'Mỗi lần chỉ được đặt tối đa :max ghế.',
            'combos.*.max' => 'Mỗi loại bắp nước chỉ đặt tối đa :max phần.',
            'combos.*.integer' => 'Số lượng bắp nước không hợp lệ.',
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

                // Chỉ bán combo đang mở bán
                $ids = array_keys(array_filter((array) $this->input('combos', [])));
                if ($ids && Combo::active()->whereIn('id', $ids)->count() !== count($ids)) {
                    $validator->errors()->add('combos', 'Có món bắp nước đã ngừng bán. Vui lòng chọn lại.');
                }
            },
        ];
    }
}
