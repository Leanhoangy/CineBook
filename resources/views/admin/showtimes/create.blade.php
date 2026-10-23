@extends('layouts.admin')

@section('title', 'Thêm suất chiếu')

@section('content')
    <form method="POST" action="{{ route('admin.showtimes.store') }}" novalidate>
        @csrf
        @include('admin.showtimes._form')
    </form>
@endsection
