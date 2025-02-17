<x-app-layout>
    <div x-data="webData">
        <div class="flex flex-col md:flex-row h-screen overflow-x-hidden" x-data="{ isSidebarVisible: true }">
            <!-- Sidebar -->
            <aside class="w-full md:w-1/3 bg-white p-6 shadow-lg md:overflow-y-auto md:h-full transition-transform duration-300 dark:bg-gray-800 dark:text-white"
                x-show="isSidebarVisible">
                {{ $sidebar }}
            </aside>

            <!-- Toggle Button -->
            <div class="hidden md:flex flex-col items-center p-2">
                <button @click="isSidebarVisible = !isSidebarVisible" class="p-2 text-gray-600 hover:text-gray-900 focus:outline-none dark:text-white">
                    <span class="material-symbols-outlined" x-text="isSidebarVisible ? 'arrow_menu_close' : 'arrow_menu_open'"></span>
                </button>
            </div>

            <!-- Main Content -->
            <main class="w-full p-4 md:overflow-y-auto md:h-full transition-all duration-300 dark:bg-gray-900"
                :class="{ 'md:w-2/3': isSidebarVisible, 'md:w-full': !isSidebarVisible }">
                {{ $slot }}
            </main>
        </div>
    </div>
</x-app-layout>
