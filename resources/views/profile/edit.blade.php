@extends('layouts.app')

@section('title', 'Tài khoản')

@section('content')
    <div class="container py-4 py-lg-5" style="max-width: 960px">
        <h1 class="cb-section-title mb-4">Tài khoản của tôi</h1>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="cb-card p-4 text-center">
                    @if ($user->avatar_url)
                        <img src="{{ $user->avatar_url }}" alt="" class="cb-avatar cb-avatar-lg mb-3">
                    @else
                        <span class="cb-avatar cb-avatar-lg mb-3">{{ $user->initials }}</span>
                    @endif
                    <h2 class="h5 fw-bold mb-1">{{ $user->name }}</h2>
                    <p class="text-muted small mb-3">{{ $user->email }}</p>
                    <x-status-badge :status="$user->role" />
                    @if ($user->isCustomer())
                        <hr>
                        <div class="row text-center">
                            <div class="col"><div class="fs-4 fw-bold">{{ $user->confirmed_count }}</div><small class="text-muted">Lượt đặt vé</small></div>
                            <div class="col"><div class="fs-6 fw-bold mt-2">@money($totalSpent)</div><small class="text-muted">Đã chi tiêu</small></div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-md-8">
                <div class="cb-card p-4 mb-4">
                    <h2 class="h6 fw-bold mb-3"><i class="bi bi-person me-2"></i>Thông tin cá nhân</h2>
                    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" novalidate>
                        @csrf
                        @method('PUT')
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label for="name" class="form-label">Họ tên</label>
                                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-sm-6">
                                <label for="phone" class="form-label">Số điện thoại</label>
                                <input type="tel" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" class="form-control @error('phone') is-invalid @enderror">
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror">
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label for="avatar" class="form-label">Ảnh đại diện</label>
                                <input type="file" id="avatar" name="avatar" accept="image/*" class="form-control @error('avatar') is-invalid @enderror">
                                <div class="form-text">JPG, PNG hoặc WEBP, tối đa 2MB.</div>
                                @error('avatar') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                @if ($user->avatar)
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" name="remove_avatar" value="1" id="remove_avatar">
                                        <label class="form-check-label small" for="remove_avatar">Xóa ảnh đại diện hiện tại</label>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <button class="btn btn-primary mt-4 px-4">Lưu thay đổi</button>
                    </form>
                </div>

                <div class="cb-card p-4">
                    <h2 class="h6 fw-bold mb-3"><i class="bi bi-shield-lock me-2"></i>Đổi mật khẩu</h2>
                    <form method="POST" action="{{ route('profile.password') }}" novalidate>
                        @csrf
                        @method('PUT')
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="current_password" class="form-label">Mật khẩu hiện tại</label>
                                <input type="password" id="current_password" name="current_password" class="form-control @error('current_password', 'password') is-invalid @enderror">
                                @error('current_password', 'password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-sm-6">
                                <label for="new_password" class="form-label">Mật khẩu mới</label>
                                <input type="password" id="new_password" name="password" class="form-control @error('password', 'password') is-invalid @enderror">
                                @error('password', 'password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-sm-6">
                                <label for="password_confirmation" class="form-label">Nhập lại mật khẩu mới</label>
                                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control">
                            </div>
                        </div>
                        <button class="btn btn-outline-primary mt-4 px-4">Đổi mật khẩu</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
