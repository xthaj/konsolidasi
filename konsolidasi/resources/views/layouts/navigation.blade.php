<nav class="bg-white border-gray-200 dark:bg-gray-900 relative z-20">
    <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
        <!-- Logo and Page Title -->
        <div class="shrink-0 flex items-center">
            <a href="{{ route('dashboard') }}">
                <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
            </a>
            @php
            $pageTitle = match(true) {
            request()->routeIs('dashboard') => 'Beranda',
            request()->routeIs('visualisasi.create') => 'Visualisasi',
            request()->routeIs('data.edit') => 'Lihat Data',
            request()->routeIs('data.create') => 'Unggah Data',
            request()->routeIs('rekon.pemilihan') => 'Pemilihan Rekonsiliasi',
            request()->routeIs('rekon.progres') => 'Progres Rekonsiliasi',
            request()->routeIs('profile.edit') => 'Akun',
            request()->routeIs('settings') => 'Pengaturan',
            default => 'Beranda'
            };
            @endphp

            <span class="text-lg font-semibold text-primary-900 dark:text-white ml-2">
                {{ $pageTitle }}
            </span>
        </div>

        <!-- Mobile Menu Button -->
        <button data-collapse-toggle="navbar-default" type="button" class="inline-flex items-center p-2 w-10 h-10 justify-center text-sm text-gray-500 rounded-lg md:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600" aria-controls="navbar-default" aria-expanded="false">
            <span class="sr-only">Open main menu</span>
            <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 17 14">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h15M1 7h15M1 13h15" />
            </svg>
        </button>

        <!-- Navigation Links -->
        <div class="hidden w-full md:block md:w-auto" id="navbar-default">
            <ul class="font-medium flex flex-col p-4 md:p-0 mt-4 border border-gray-100 rounded-lg bg-gray-50 md:flex-row md:space-x-8 rtl:space-x-reverse md:mt-0 md:border-0 md:bg-white dark:bg-gray-800 md:dark:bg-gray-900 dark:border-gray-700">
                <li>
                    <a href="{{ route('dashboard') }}" class="block py-2 px-3 rounded-sm md:p-0 {{ request()->routeIs('dashboard') ? 'text-white bg-primary-700 md:bg-transparent md:text-primary-700 dark:text-white md:dark:text-primary-500' : 'text-gray-900 hover:bg-gray-100 md:hover:bg-transparent md:hover:text-primary-700 dark:text-white md:dark:hover:text-primary-500 dark:hover:bg-gray-700 dark:hover:text-white' }}" aria-current="page">Beranda</a>
                </li>

                @if (auth()->user()->isPusat())
                <li>
                    <a href="{{ route('visualisasi.create') }}" class="block py-2 px-3 rounded-sm md:p-0 {{ request()->routeIs('visualisasi.create') ? 'text-white bg-primary-700 md:bg-transparent md:text-primary-700 dark:text-white md:dark:text-primary-500' : 'text-gray-900 hover:bg-gray-100 md:hover:bg-transparent md:hover:text-primary-700 dark:text-white md:dark:hover:text-primary-500 dark:hover:bg-gray-700 dark:hover:text-white' }}">Visualisasi</a>
                </li>

                <li class="relative">
                    <button id="dropdownNavbarLink2" data-dropdown-toggle="dropdownData" class="flex items-center justify-between w-full py-2 px-3 rounded-sm md:p-0 md:w-auto {{ request()->routeIs('data.edit') || request()->routeIs('data.create') ? 'text-primary-700 dark:text-primary-500' : 'text-gray-900 hover:bg-gray-100 md:hover:bg-transparent md:hover:text-primary-700 dark:text-white md:dark:hover:text-primary-500 dark:hover:bg-gray-700 dark:hover:text-white' }}">Data
                        <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4" />
                        </svg>
                    </button>
                    <div id="dropdownData" class="relative z-15 hidden font-normal bg-white divide-y divide-gray-100 rounded-lg shadow-sm w-44 dark:bg-gray-700 dark:divide-gray-600">
                        <ul class="relative z-15 py-2 text-sm text-gray-700 dark:text-gray-400">
                            <li>
                                <a href="{{ route('data.edit') }}" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Lihat Data</a>
                            </li>
                            <li>
                                <a href="{{ route('data.create') }}" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Unggah Data</a>
                            </li>
                        </ul>
                    </div>
                </li>

                @endif
                <li>
                    <button id="dropdownNavbarLink3" data-dropdown-toggle="dropdownRekon" class="flex items-center justify-between w-full py-2 px-3 rounded-sm md:p-0 md:w-auto {{ request()->routeIs('rekon.pemilihan') || request()->routeIs('rekon.progres') ? 'text-primary-700 dark:text-primary-500' : 'text-gray-900 hover:bg-gray-100 md:hover:bg-transparent md:hover:text-primary-700 dark:text-white md:dark:hover:text-primary-500 dark:hover:bg-gray-700 dark:hover:text-white' }}">Rekonsiliasi
                        <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4" />
                        </svg>
                    </button>
                    <div id="dropdownRekon" class="z-15 hidden font-normal bg-white divide-y divide-gray-100 rounded-lg shadow-sm w-44 dark:bg-gray-700 dark:divide-gray-600">
                        <ul class="py-2 text-sm text-gray-700 dark:text-gray-400">
                            @if (auth()->user()->isPusat())
                            <li>
                                <a href="{{ route('rekon.pemilihan') }}" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Pemilihan</a>
                            </li>
                            @endif
                            <li>
                                <a href="{{ route('rekon.progres') }}" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Progres</a>
                            </li>
                        </ul>
                    </div>
                </li>

                @if (auth()->user()->isPusat())
                <li>
                    <a href="{{ route('pengaturan') }}" class="block py-2 px-3 rounded-sm md:p-0 {{ request()->routeIs('pengaturan') ? 'text-white bg-primary-700 md:bg-transparent md:text-primary-700 dark:text-white md:dark:text-primary-500' : 'text-gray-900 hover:bg-gray-100 md:hover:bg-transparent md:hover:text-primary-700 dark:text-white md:dark:hover:text-primary-500 dark:hover:bg-gray-700 dark:hover:text-white' }}" aria-current="page">Pengaturan</a>
                </li>
                @endif


                <li>
                    <button id="dropdownNavbarLink4" data-dropdown-toggle="dropdownAkun" class="flex items-center justify-between w-full py-2 px-3 rounded-sm md:p-0 md:w-auto {{ request()->routeIs('profile.edit') ? 'text-primary-700 dark:text-primary-500' : 'text-gray-900 hover:bg-gray-100 md:hover:bg-transparent md:hover:text-primary-700 dark:text-white md:dark:hover:text-primary-500 dark:hover:bg-gray-700 dark:hover:text-white' }}">Akun
                        <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4" />
                        </svg>
                    </button>
                    <div id="dropdownAkun" class="z-15 hidden font-normal bg-white divide-y divide-gray-100 rounded-lg shadow-sm w-44 dark:bg-gray-700 dark:divide-gray-600">
                        <ul class="py-2 text-sm text-gray-700 dark:text-gray-400">
                            @if (auth()->user()->isPusat())
                            <li>
                                <a href="{{ route('akun.index') }}" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Master Akun</a>
                            </li>
                            @endif
                            <li>
                                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Pengaturan</a>
                            </li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <a href="{{ route('logout') }}" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white" onclick="event.preventDefault(); this.closest('form').submit();">Logout</a>
                                </form>
                            </li>
                        </ul>
                    </div>
                </li>

            </ul>
        </div>
    </div>
</nav>