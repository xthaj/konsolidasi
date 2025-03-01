@extends('layouts.guest')

@section('vite')
    @vite(['resources/css/app.css'])
@endsection

@section('content')
    <h1 class="text-center text-xl font-bold leading-tight tracking-tight text-gray-900 md:text-2xl">
        Login
    </h1>

    <form method="POST" action="{{ route('login') }}" class="space-y-4 md:space-y-6">
        @csrf

        <!-- Username -->
        <div>
            <label for="username" class="block mb-2 text-sm font-medium text-gray-900">Username</label>
            <input type="text" id="username" name="username" value="{{ old('username') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5" placeholder="hatta45" required />
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block mb-2 text-sm font-medium text-gray-900">Password</label>
            <input type="password" name="password" id="password" placeholder="••••••••" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required="">
        </div>

        <!-- Remember Me Checkbox -->
        <!-- TODO: cache login -->
        <div class="flex items-center">
            <input id="remember" name="remember" type="checkbox" class="w-4 h-4 border border-gray-300 rounded bg-gray-50 focus:ring-3 focus:ring-primary-300" {{ old('remember') ? 'checked' : '' }}>
            <label for="remember" class="ml-2 text-sm font-medium text-gray-900">Ingat saya</label>
        </div>

        <!-- Display Errors (e.g., throttle message) -->
        @if ($errors->any())
            @foreach ($errors->all() as $error)
                <p class="mt-2 text-sm text-red-600">{{ $error }}</p>
            @endforeach
        @endif

        <button type="submit" class="w-full text-white bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Masuk</button>

        <p class="text-sm font-light text-gray-500">
            Belum punya akun? <a href="{{ route('register') }}" class="font-medium text-primary-600 hover:underline">Daftar di sini</a>
        </p>
    </form>

@endsection
