@extends('layouts.app')

@section('title', 'Quên mật khẩu')

@section('content')
    <div class="container cb-auth-wrap py-5">
        <div class="cb-card cb-auth-card w-100">
            <div class="text-center mb-4">
                <h1 class="h4 fw-bold mb-1">Quên mật khẩu?</h1>
                <p class="text-muted mb-0">Nhập email đã đăng ký, chúng tôi sẽ gửi liên kết để bạn đặt lại mật khẩu</p>
            </div>

            <form method="POST" action="{{ route('password.email') }}" novalidate>
                @csrf
                <div class="mb-4">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" placeholder="ban@example.com" autofocus>
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <button class="btn btn-primary w-100 py-2"><i class="bi bi-envelope me-1"></i>Gửi liên kết đặt lại</button>
            </form>

            <p class="text-center text-muted small mt-4 mb-0"><a href="{{ route('login') }}"><i class="bi bi-arrow-left me-1"></i>Quay lại đăng nhập</a></p>

            @if (app()->isLocal() && config('mail.default') === 'log')
                <div class="alert alert-secondary small mt-4 mb-0">
                    Đang dùng <code>MAIL_MAILER=log</code>: email không được gửi thật mà được ghi vào <code>storage/logs/laravel.log</code>.
                </div>
            @endif
        </div>
    </div>
@endsection
