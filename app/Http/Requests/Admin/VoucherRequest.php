<?php

namespace App\Http\Requests\Admin;

use App\Models\Voucher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
    }

    public function rules(): array
    {
        $percent = $this->input('type') === Voucher::TYPE_PERCENT;

        return [
            'code' => ['required', 'string', 'min:4', 'max:30', 'regex:/^[A-Z0-9]+$/', Rule::unique('vouchers')->ignore($this->route('voucher'))],
            'description' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(Voucher::TYPES))],
            'value' => $percent
                ? ['required', 'integer', 'between:1,100']
                : ['required', 'integer', 'min:1000', 'max:1000000'],
            'max_discount' => ['nullable', 'integer', 'min:1000', Rule::prohibitedIf(! $percent)],
            'min_order' => ['nullable', 'integer', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => array_filter([
                'nullable', 'date',
                $this->filled('starts_at') ? 'after:starts_at' : null,
                $this->isMethod('post') ? 'after:now' : null, // sửa mã cũ (VD: chỉ để khóa) không bắt buộc gia hạn
            ]),
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'Mã chỉ gồm chữ in hoa không dấu và số, không có khoảng trắng.',
            'value.between' => 'Phần trăm giảm phải từ 1 đến 100.',
            'max_discount.prohibited' => 'Mức giảm tối đa chỉ dùng cho mã giảm theo %.',
            'expires_at.after' => 'Ngày hết hạn phải sau ngày bắt đầu và sau thời điểm hiện tại.',
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'mã giảm giá',
            'type' => 'loại giảm giá',
            'value' => 'giá trị giảm',
            'max_discount' => 'mức giảm tối đa',
            'min_order' => 'đơn tối thiểu',
            'usage_limit' => 'số lượt dùng',
            'starts_at' => 'ngày bắt đầu',
            'expires_at' => 'ngày hết hạn',
        ];
    }

    /** Ô để trống thì lưu null/0 thay vì chuỗi rỗng */
    public function voucherData(): array
    {
        $data = $this->validated();
        $data['min_order'] ??= 0;
        $data['is_active'] = $this->boolean('is_active');

        return $data;
    }
}
