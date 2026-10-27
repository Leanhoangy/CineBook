@extends('layouts.app')

@section('title', 'Đăng nhập')

@section('content')
    <div class="container cb-auth-wrap py-5">
        <div class="cb-card cb-auth-card w-100">
            <div class="text-center mb-4">
                <div class="cb-brand fs-3"><i class="bi bi-film text-danger me-1"></i>Cine<span>Book</span></div>
                <p class="text-muted mb-0">Đăng nhập để đặt vé và quản lý vé của bạn</p>
            </div>

            <form method="POST" action="{{ route('login') }}" novalidate>
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" placeholder="ban@example.com" autofocus>
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Mật khẩu</label>
                    <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="••••••••">
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" @checked(old('remember'))>
                        <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
                    </div>
                    <a href="{{ route('password.request') }}" class="small">Quên mật khẩu?</a>
                </div>
                <button class="btn btn-primary w-100 py-2">Đăng nhập</button>
            </form>

            <p class="text-center text-muted small mt-4 mb-0">Chưa có tài khoản? <a href="{{ route('register') }}">Đăng ký ngay</a></p>

            @if (app()->isLocal())
                <div class="alert alert-secondary small mt-4 mb-0">
                    <strong>Tài khoản demo</strong> (mật khẩu: <code>password</code>)<br>
                    Admin: <code>admin@cinebook.test</code><br>
                    Khách: <code>user@cinebook.test</code>
                </div>
            @endif
        </div>
    </div>
@endsection
