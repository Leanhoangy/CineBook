<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user()->loadCount([
            'bookings as confirmed_count' => fn ($q) => $q->where('status', 'confirmed'),
        ]);

        $totalSpent = $user->bookings()->where('status', 'confirmed')->sum('total_price');

        return view('profile.edit', compact('user', 'totalSpent'));
    }

    public function update(ProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->safe()->only(['name', 'email', 'phone']);

        // Upload ảnh đại diện qua Laravel Storage (disk public)
        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        } elseif ($request->boolean('remove_avatar') && $user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $data['avatar'] = null;
        }

        $user->update($data);

        return back()->with('success', 'Cập nhật thông tin cá nhân thành công.');
    }

    /** Đổi mật khẩu: minh họa cách validate() trực tiếp trong Controller */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers(), 'different:current_password'],
        ], [
            'password.different' => 'Mật khẩu mới phải khác mật khẩu hiện tại.',
        ], [
            'password' => 'mật khẩu mới',
        ]);

        $request->user()->update(['password' => $validated['password']]);

        return back()->with('success', 'Đổi mật khẩu thành công.');
    }
}
