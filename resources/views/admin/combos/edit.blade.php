@extends('layouts.admin')

@section('title', 'Sửa combo bắp nước')
@section('subtitle', $combo->name)

@section('content')
    <form method="POST" action="{{ route('admin.combos.update', $combo) }}" enctype="multipart/form-data" novalidate>
        @csrf
        @method('PUT')
        @include('admin.combos._form')
    </form>
@endsection
