@extends('layouts.app')

@section('title', 'Lỗi hệ thống')

@section('content')
    <div class="container cb-auth-wrap py-5">
        <div class="text-center mx-auto" style="max-width: 480px">
            <i class="bi bi-bug text-danger" style="font-size: 4rem"></i>
            <div class="display-3 fw-bold mt-2">500</div>
            <h1 class="h4 fw-bold">Lỗi hệ thống</h1>
            <p class="text-muted">Đã có lỗi xảy ra, vui lòng thử lại sau.</p>
            <a href="{{ url('/') }}" class="btn btn-primary rounded-pill px-4 mt-2">Về trang chủ</a>
        </div>
    </div>
@endsection
