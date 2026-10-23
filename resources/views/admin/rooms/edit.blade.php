@extends('layouts.admin')

@section('title', 'Sửa phòng chiếu')
@section('subtitle', $room->name)

@section('content')
    <form method="POST" action="{{ route('admin.rooms.update', $room) }}" novalidate>
        @csrf
        @method('PUT')
        @include('admin.rooms._form')
    </form>
@endsection
