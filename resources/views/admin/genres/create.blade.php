@extends('layouts.admin')

@section('title', 'Thêm thể loại')

@section('content')
    <div class="ad-card p-4" style="max-width: 640px">
        <form method="POST" action="{{ route('admin.genres.store') }}" novalidate>
            @csrf
            @include('admin.genres._form')
        </form>
    </div>
@endsection
