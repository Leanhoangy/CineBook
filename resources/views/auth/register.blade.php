@extends('layouts.app')

@section('title', 'Đăng ký')

@section('content')
    <div class="container cb-auth-wrap py-5">
        <div class="cb-card cb-auth-card w-100">
            <div class="text-center mb-4">
                <h1 class="h4 fw-bold mb-1">Tạo tài khoản</h1>
                <p class="text-muted mb-0">Đăng ký miễn phí để bắt đầu đặt vé</p>
            </div>

            <form method="POST" action="{{ route('register') }}" novalidate>
                @csrf
                <div class="mb-3">
                    <label for="name" class="form-label">Họ tên</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" autofocus>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="phone" class="form-label">Số điện thoại <span class="text-muted fw-normal">(không bắt buộc)</span></label>
                    <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror" placeholder="0901234567">
                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Mật khẩu</label>
                    <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror">
                    <div class="form-text">Tối thiểu 8 ký tự, gồm cả chữ và số.</div>
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-4">
                    <label for="password_confirmation" class="form-label">Nhập lại mật khẩu</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control">
                </div>
                <button class="btn btn-primary w-100 py-2">Đăng ký</button>
            </form>

            <p class="text-center text-muted small mt-4 mb-0">Đã có tài khoản? <a href="{{ route('login') }}">Đăng nhập</a></p>
        </div>
    </div>
@endsection
