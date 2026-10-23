@extends('layouts.admin')

@section('title', 'Thêm phim mới')

@section('content')
    <form method="POST" action="{{ route('admin.movies.store') }}" enctype="multipart/form-data" novalidate>
        @csrf
        @include('admin.movies._form')
    </form>
@endsection
