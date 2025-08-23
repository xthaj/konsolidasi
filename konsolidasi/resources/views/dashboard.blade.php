<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" />
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen bg-white">
        @include('layouts.navigation')
        <!-- Page Content -->
        <main>
            <div class="bg-white p-4 mx-auto max-w-screen-xl text-center lg:pt-4 lg:pb-8 lg:px-12">
                @if ($percentage >= 0)
                <a href="{{ route('rekon.pengisian') }}" class="inline-flex justify-between items-center py-1 px-1 pr-4 mb-7 text-sm text-gray-700 bg-gray-100 rounded-full  hover:bg-gray-200 " role="alert">
                    <span class="text-xs bg-primary-600 rounded-full text-white px-4 py-1.5 mr-3">{{ $activeMonthYear }}</span>
                    <span class="text-sm font-medium">Cek Rekonsiliasi</span>
                    <span class="material-symbols-rounded">
                        keyboard_arrow_right
                    </span>
                </a>

                @elseif(auth()->user()->isPusat())
                <a href="{{ route('visualisasi.create') }}" class="inline-flex justify-between items-center py-1 px-1 pr-4 mb-7 text-sm text-gray-700 bg-gray-100 rounded-full  hover:bg-gray-200 " role="alert">
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
                        <h1 class="mb-4 text-left text-4xl font-extrabold tracking-tight leading-none text-gray-900 md:text-5xl lg:text-6xl">
                            Harmonisasi dan Rekonsiliasi Harga
                        </h1>
                        <h2 class="mb-4 text-left text-3xl font-extrabold tracking-tight leading-none text-primary-900 md:text-5xl lg:text-6xl">
                            {{ $activeMonthYear }}
                        </h2>

                        {{-- Percentage progress bar --}}
                        @if ($percentage > 0)
                        <div class="w-full h-6 bg-gray-200 rounded-full">
                            <div
                                class="h-6 bg-primary-600 rounded-full text-xs font-medium text-white text-center p-0.5"
                                style="width: {{ $progressWidth }}%">
                                {{ $percentage }}%
                            </div>
                        </div>
                        @endif

                        {{-- Buttons --}}
                        <div class="flex flex-col my-8 lg:mb-16 space-y-4 sm:flex-row sm:space-y-0 sm:space-x-4">
                            @if(auth()->user()->isPusat())
                            @if ($percentage < 0)
                                <x-primary-button
                                type="button"
                                class="inline-flex justify-center items-center px-5 py-3 text-base"
                                onclick="window.location.href='{{ route('visualisasi.create') }}'">
                                Lihat Harmonisasi
                                </x-primary-button>
                                @else
                                <x-primary-button
                                    type="button"
                                    class="inline-flex justify-center items-center px-5 py-3 text-base"
                                    onclick="window.location.href='{{ route('rekon.pengisian') }}'">
                                    Lihat Rekonsiliasi
                                </x-primary-button>
                                @endif
                                @else
                                <x-primary-button
                                    type="button"
                                    class="inline-flex justify-center items-center px-5 py-3 text-base"
                                    onclick="window.location.href='{{ route('rekon.pengisian-skl') }}'">
                                    Lihat Rekonsiliasi
                                </x-primary-button>
                                @endif
                        </div>
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