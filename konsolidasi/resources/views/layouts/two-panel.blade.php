@extends('layouts.app')

@yield('vite')

@section("content")
    <div x-data="webData">
        <div class="flex flex-col md:flex-row h-screen overflow-x-hidden" x-data="{ isSidebarVisible: true }">
            <!-- Sidebar -->
            <aside class="w-full md:w-1/3 bg-white p-6 shadow-lg md:overflow-y-auto md:h-full transition-transform duration-300 dark:bg-gray-800 dark:text-white"
                x-show="isSidebarVisible">
                {{ $sidebar }}
            </aside>

            <!-- Toggle Button -->
            <div class="hidden md:flex flex-col items-center p-2">
                <button data-tooltip-target="tooltip-default" data-tooltip-placement="bottom" @click="isSidebarVisible = !isSidebarVisible" class="p-2 text-gray-600 hover:text-gray-900 focus:outline-none dark:text-white">
                    <span class="material-symbols-outlined" x-text="isSidebarVisible ? 'arrow_menu_close' : 'arrow_menu_open'"></span>
                </button>

                <div id="tooltip-default" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-xs opacity-0 tooltip dark:bg-gray-700">
                    Kontrol
                    <div class="tooltip-arrow" data-popper-arrow></div>
                </div>

            </div>

            <!-- Main Content -->
            <main
                :class="{ 'md:w-2/3': isSidebarVisible, 'md:w-full': !isSidebarVisible }"
                class="{{ $mainClass ?? 'w-full md:overflow-hidden p-4 md:overflow-y-auto md:h-full transition-all duration-300 dark:bg-gray-900 md:w-2/3' }}"
            >
                {{ $slot }}
            </main>
        </div>
    </div>
@endsection
