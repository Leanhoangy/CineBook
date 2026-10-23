@extends('layouts.app')

@section('title', 'Phiên làm việc hết hạn')

@section('content')
    <div class="container cb-auth-wrap py-5">
        <div class="text-center mx-auto" style="max-width: 480px">
            <i class="bi bi-hourglass-bottom text-danger" style="font-size: 4rem"></i>
            <div class="display-3 fw-bold mt-2">419</div>
            <h1 class="h4 fw-bold">Phiên làm việc hết hạn</h1>
            <p class="text-muted">Trang đã hết hạn, vui lòng tải lại và thử lại.</p>
            <a href="{{ url('/') }}" class="btn btn-primary rounded-pill px-4 mt-2">Về trang chủ</a>
        </div>
    </div>
@endsection
