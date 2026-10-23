@extends('layouts.admin')

@section('title', 'Sửa phim')
@section('subtitle', $movie->title)

@section('content')
    <form method="POST" action="{{ route('admin.movies.update', $movie) }}" enctype="multipart/form-data" novalidate>
        @csrf
        @method('PUT')
        @include('admin.movies._form')
    </form>
@endsection
