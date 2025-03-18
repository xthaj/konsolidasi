@extends('layouts.app')

@yield('vite')

@section("content")
    <div x-data="webData">

        <!-- Loading Overlay -->
        <!-- <div x-show="loading" class="absolute inset-0 bg-gray-100 bg-opacity-100 flex items-center justify-center z-10">
            <svg aria-hidden="true" class="inline w-8 h-8 text-gray-200 animate-spin dark:text-gray-600 fill-primary-500" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/>
            </svg>
            <span class="sr-only">Loading...</span>
        </div> -->

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
