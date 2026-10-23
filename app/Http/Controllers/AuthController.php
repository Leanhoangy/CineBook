<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();
        $user = $request->user();

        $default = $user->isAdmin() ? route('admin.dashboard') : route('home');

        return redirect()->intended($default)
            ->with('success', "Xin chào {$user->name}, chúc bạn một ngày tốt lành!");
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        // role luôn là user, không cho phép tự đăng ký làm admin
        $user = User::create($request->validated());

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('home'))
            ->with('success', 'Đăng ký tài khoản thành công. Chào mừng bạn đến với CineBook!');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Bạn đã đăng xuất.');
    }
}
