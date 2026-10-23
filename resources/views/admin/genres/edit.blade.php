@extends('layouts.admin')

@section('title', 'Sửa thể loại')
@section('subtitle', $genre->name)

@section('content')
    <div class="ad-card p-4" style="max-width: 640px">
        <form method="POST" action="{{ route('admin.genres.update', $genre) }}" novalidate>
            @csrf
            @method('PUT')
            @include('admin.genres._form')
        </form>
    </div>
@endsection
