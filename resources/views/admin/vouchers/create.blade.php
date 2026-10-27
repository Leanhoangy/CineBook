@extends('layouts.admin')

@section('title', 'Thêm mã giảm giá')

@section('content')
    <form method="POST" action="{{ route('admin.vouchers.store') }}" novalidate>
        @csrf
        @include('admin.vouchers._form')
    </form>
@endsection
