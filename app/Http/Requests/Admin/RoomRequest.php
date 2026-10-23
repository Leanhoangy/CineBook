<?php

namespace App\Http\Requests\Admin;

use App\Models\Room;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('rooms')->ignore($this->route('room'))],
            'total_rows' => ['required', 'integer', 'min:3', 'max:'.Room::MAX_ROWS],
            'seats_per_row' => ['required', 'integer', 'min:4', 'max:'.Room::MAX_SEATS_PER_ROW],
            'vip_rows' => ['required', 'integer', 'min:0', 'lt:total_rows'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'tên phòng'];
    }

    public function messages(): array
    {
        return [
            'vip_rows.lt' => 'Số hàng VIP phải nhỏ hơn tổng số hàng ghế.',
        ];
    }
}
