@extends('layouts.guest')

@section('vite')
@vite(['resources/css/app.css', 'resources/js/auth/login.js'])
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" />

@endsection

@section('content')
<h1 class="text-center text-xl font-bold leading-tight tracking-tight text-gray-900 md:text-2xl">
    Login
</h1>

<form @submit.prevent="submitLogin" class="space-y-4 md:space-y-5" x-data="webData">
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

</form>

<form action="{{ route('sso.login') }}" method="GET" class="w-full">
    <button type="submit" class="mt-4 w-full justify-center border border-primary-600 text-primary-700 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-primary-700/50 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center me-2 mb-2 transition-colors duration-200 ease-in-out">
        <img src="{{ asset('images/logo_bps.svg') }}" class="w-4 h-4 me-2" alt="BPS logo">
        Login dengan SSO BPS
    </button>
</form>


<p class="text-sm font-light text-gray-500">
    Belum punya akun? <a href="{{ route('register') }}" class="font-medium text-primary-600 hover:underline">Daftar di sini</a>
</p>
@endsection