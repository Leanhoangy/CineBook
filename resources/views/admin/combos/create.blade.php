@extends('layouts.admin')

@section('title', 'Thêm combo bắp nước')

@section('content')
    <form method="POST" action="{{ route('admin.combos.store') }}" enctype="multipart/form-data" novalidate>
        @csrf
        @include('admin.combos._form')
    </form>
@endsection
