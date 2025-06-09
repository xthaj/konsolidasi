@php
$statusCode = method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500;
@endphp

@extends('layouts.guest')

@section('vite')
@vite(['resources/css/app.css'])
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" />
@endsection

@section('content')
<h1 class="text-center text-xl font-bold leading-tight tracking-tight text-gray-900 md:text-8xl">
    {{ $statusCode }}
</h1>

<h2 class="text-center">
    Terjadi kesalahan.
</h2>
@endsection