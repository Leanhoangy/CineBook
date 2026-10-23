<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::withCount('bookings')
            ->withSum(['bookings as total_spent' => fn ($q) => $q->where('status', 'confirmed')], 'total_price')
            ->search($request->query('q'))
            ->when($request->query('role'), fn ($q, $r) => $q->where('role', $r))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('admin.users.create', ['user' => new User(['role' => User::ROLE_USER])]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $user = new User($request->safe()->except('role'));
        $user->role = $request->validated('role');
        $user->save();

        return redirect()->route('admin.users.index')->with('success', "Đã tạo tài khoản {$user->email}.");
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        if ($user->is($request->user()) && $request->validated('role') !== User::ROLE_ADMIN) {
            return back()->withInput()->with('error', 'Bạn không thể tự gỡ quyền quản trị của chính mình.');
        }

        $data = $request->safe()->except(['role', 'password']);
        if ($request->filled('password')) {
            $data['password'] = $request->validated('password');
        }

        $user->fill($data);
        $user->role = $request->validated('role');
        $user->save();

        return redirect()->route('admin.users.index')->with('success', "Đã cập nhật tài khoản {$user->email}.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Bạn không thể xóa tài khoản đang đăng nhập.');
        }

        if ($user->bookings()->exists()) {
            return back()->with('error', "Tài khoản {$user->email} đã có lịch sử đặt vé nên không thể xóa.");
        }

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', "Đã xóa tài khoản {$user->email}.");
    }
}
