@extends('layouts.admin')

@section('title', 'Thêm phòng chiếu')

@section('content')
    <form method="POST" action="{{ route('admin.rooms.store') }}" novalidate>
        @csrf
        @include('admin.rooms._form')
    </form>
@endsection
