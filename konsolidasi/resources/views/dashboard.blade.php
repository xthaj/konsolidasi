<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen bg-white">
        @include('layouts.navigation')
        <!-- Page Content -->
        <main>
            <div class="bg-white py-8 px-4 mx-auto max-w-screen-xl text-center lg:py-16 lg:px-12">
                @if ($percentage >= 0)
                <a href="{{ route('rekon.progres') }}" class="inline-flex justify-between items-center py-1 px-1 pr-4 mb-7 text-sm text-gray-700 bg-gray-100 rounded-full dark:bg-gray-800 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-700" role="alert">
                    <span class="text-xs bg-primary-600 rounded-full text-white px-4 py-1.5 mr-3">{{ $activeMonthYear }}</span>
                    <span class="text-sm font-medium">Cek Rekonsiliasi</span>
                    <svg class="ml-2 w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                    </svg>
                </a>
                @elseif(auth()->user()->isPusat())
                <a href="{{ route('visualisasi.create') }}" class="inline-flex justify-between items-center py-1 px-1 pr-4 mb-7 text-sm text-gray-700 bg-gray-100 rounded-full dark:bg-gray-800 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-700" role="alert">
                    <span class="text-xs bg-primary-600 rounded-full text-white px-4 py-1.5 mr-3">{{ $activeMonthYear }}</span>
                    <span class="text-sm font-medium">Cek Visualisasi</span>
                    <svg class="ml-2 w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                    </svg>
                </a>
                @endif

                <div class="flex flex-col gap-8 md:flex-row items-center justify-center max-w-6xl mx-auto px-6">
                    <!-- Left Content -->
                    <div class="md:w-1/2 space-y-4">
                        <h1 class="mb-4 text-left text-4xl font-extrabold tracking-tight leading-none text-gray-900 md:text-5xl lg:text-6xl dark:text-white">Harmonisasi dan Rekonsiliasi Harga</h1>
                        <h2 class="mb-4 text-left text-2xl font-extrabold tracking-tight leading-none text-primary-900 md:text-5xl lg:text-6xl dark:text-white">{{ $activeMonthYear }}</h2>

                        @if (auth()->user()->isPusat())
                        @if ($percentage > 0)
                        <div class="w-full h-6 bg-gray-200 rounded-full dark:bg-gray-700">
                            <div class="h-6 bg-primary-600 rounded-full dark:bg-primary-500 text-xs font-medium text-blue-100 text-center p-0.5" style="width: {{ $percentage }}%">
                                {{ $percentage }}%
                            </div>
                        </div>
                        @endif
                        <div class="flex flex-col my-8 lg:mb-16 space-y-4 sm:flex-row sm:space-y-0 sm:space-x-4">
                            @if ($percentage >= 0)
                            <a href="{{ route('rekon.progres') }}" class="inline-flex justify-center items-center py-3 px-5 text-base font-medium text-center text-white rounded-lg bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-900">
                                Lihat Rekonsiliasi
                            </a>
                            @else
                            <a href="{{ route('visualisasi.create') }}" class="inline-flex justify-center items-center py-3 px-5 text-base font-medium text-center text-white rounded-lg bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-900">
                                Lihat Visualisasi
                            </a>
                            @endif
                        </div>
                        @else
                        <div class="flex flex-col my-8 lg:mb-16 space-y-4 sm:flex-row sm:space-y-0 sm:space-x-4">
                            <a href="{{ route('rekon.progres') }}" class="inline-flex justify-center items-center py-3 px-5 text-base font-medium text-center text-white rounded-lg bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-900">
                                Lihat Rekonsiliasi
                            </a>
                        </div>
                        @endif
                    </div>
                    <!-- Right Image -->
                    <div class="md:w-1/3 mt-8 md:mt-0 flex justify-center">
                        <img src="{{ asset('images/hero.webp') }}" alt="Placeholder Image">
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>