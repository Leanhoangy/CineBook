@extends('layouts.admin')

@section('title', 'Sửa tài khoản')
@section('subtitle', $user->email)

@section('content')
    <form method="POST" action="{{ route('admin.users.update', $user) }}" novalidate>
        @csrf
        @method('PUT')
        @include('admin.users._form')
    </form>
@endsection
