@extends('layouts.guest')

@section('vite')
@vite(['resources/css/app.css', 'resources/js/auth/login.js'])
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" />

@endsection

@section('content')
<h1 class="text-center text-xl font-bold leading-tight tracking-tight text-gray-900 md:text-2xl">
    Login
</h1>

<form @submit.prevent="submitLogin" class="space-y-4 md:space-y-6" x-data="webData">
    @csrf

    <!-- Username -->
    <div>
        <label for="username" class="block mb-2 text-sm font-medium text-gray-900">Username</label>
        <input
            type="text"
            id="username"
            name="username"
            x-model="username"
            @input="username = $event.target.value.toLowerCase()"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg  block w-full p-2.5"
            placeholder="hatta45"
            required />
    </div>

    <!-- Password -->
    <div x-data="{ show: false }">
        <label for="password" class="block mb-2 text-sm font-medium text-gray-900">Password</label>
        <input :type="show ? 'text' : 'password'"
            name="password"
            id="password"
            x-model="password"
            placeholder="••••••••"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg  block w-full p-2.5"
            required>
        <label class="inline-flex items-center mt-2 space-x-2 text-sm text-gray-700">
            <input type="checkbox" x-model="show" class="rounded border-gray-300 text-primary-600 ">
            <span>Lihat password</span>
        </label>
    </div>

    <!-- Display Errors -->
    <template x-if="error">
        <div class="relative flex items-center gap-2 p-4 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50" role="alert">
            <span class="material-symbols-rounded text-red-800 text-base">warning</span>
            <p class="text-sm" x-text="error"></p>
        </div>
    </template>

    <x-primary-button class="w-full justify-center">
        <span>Masuk</span>
    </x-primary-button>

    <p class="text-sm font-light text-gray-500">
        Belum punya akun? <a href="{{ route('register') }}" class="font-medium text-primary-600 hover:underline">Daftar di sini</a>
    </p>
</form>
@endsection