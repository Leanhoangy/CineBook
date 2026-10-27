@extends('layouts.admin')

@section('title', 'Sửa mã giảm giá')
@section('subtitle', $voucher->code)

@section('content')
    <form method="POST" action="{{ route('admin.vouchers.update', $voucher) }}" novalidate>
        @csrf
        @method('PUT')
        @include('admin.vouchers._form')
    </form>
@endsection
