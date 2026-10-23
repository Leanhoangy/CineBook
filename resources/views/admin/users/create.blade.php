@extends('layouts.admin')

@section('title', 'Thêm tài khoản')

@section('content')
    <form method="POST" action="{{ route('admin.users.store') }}" novalidate>
        @csrf
        @include('admin.users._form')
    </form>
@endsection
