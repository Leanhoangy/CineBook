@extends('layouts.app')

@section('title', 'Không tìm thấy trang')

@section('content')
    <div class="container cb-auth-wrap py-5">
        <div class="text-center mx-auto" style="max-width: 480px">
            <i class="bi bi-camera-reels text-danger" style="font-size: 4rem"></i>
            <div class="display-3 fw-bold mt-2">404</div>
            <h1 class="h4 fw-bold">Không tìm thấy trang</h1>
            <p class="text-muted">Trang bạn tìm không tồn tại hoặc đã bị xóa.</p>
            <a href="{{ url('/') }}" class="btn btn-primary rounded-pill px-4 mt-2">Về trang chủ</a>
        </div>
    </div>
@endsection
