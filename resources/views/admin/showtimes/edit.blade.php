@extends('layouts.admin')

@section('title', 'Sửa suất chiếu')

@section('content')
    <form method="POST" action="{{ route('admin.showtimes.update', $showtime) }}" novalidate>
        @csrf
        @method('PUT')
        @include('admin.showtimes._form')
    </form>
@endsection
