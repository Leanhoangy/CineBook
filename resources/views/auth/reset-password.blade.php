@extends('layouts.app')

@section('title', 'Đặt lại mật khẩu')

@section('content')
    <div class="container cb-auth-wrap py-5">
        <div class="cb-card cb-auth-card w-100">
            <div class="text-center mb-4">
                <h1 class="h4 fw-bold mb-1">Đặt lại mật khẩu</h1>
                <p class="text-muted mb-0">Mật khẩu mới tối thiểu 8 ký tự, gồm cả chữ và số</p>
            </div>

            <form method="POST" action="{{ route('password.update') }}" novalidate>
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $email) }}" class="form-control @error('email') is-invalid @enderror">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Mật khẩu mới</label>
                    <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" autofocus>
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-4">
                    <label for="password_confirmation" class="form-label">Nhập lại mật khẩu mới</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control">
                </div>
                <button class="btn btn-primary w-100 py-2">Đặt lại mật khẩu</button>
            </form>
        </div>
    </div>
@endsection
