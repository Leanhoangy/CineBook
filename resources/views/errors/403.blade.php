@extends('layouts.app')

@section('title', 'Không có quyền truy cập')

@section('content')
    <div class="container cb-auth-wrap py-5">
        <div class="text-center mx-auto" style="max-width: 480px">
            <i class="bi bi-shield-lock text-danger" style="font-size: 4rem"></i>
            <div class="display-3 fw-bold mt-2">403</div>
            <h1 class="h4 fw-bold">Không có quyền truy cập</h1>
            <p class="text-muted">{{ $exception?->getMessage() ?: 'Bạn không có quyền truy cập trang này.' }}</p>
            <a href="{{ url('/') }}" class="btn btn-primary rounded-pill px-4 mt-2">Về trang chủ</a>
        </div>
    </div>
@endsection
