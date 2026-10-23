<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users')->ignore($user)],
            'phone' => ['nullable', 'regex:/^0\d{9}$/'],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            // Khi sửa: để trống mật khẩu nghĩa là giữ nguyên
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::min(8)],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng số 0.',
        ];
    }
}
