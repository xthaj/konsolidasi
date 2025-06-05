<nav class="bg-white relative z-20">
    <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto px-4 py-3 border-b border-gray-200">

        <!-- Logo and Page Title -->
        <div class="shrink-0 flex items-center">
            <a href="{{ route('dashboard') }}">
                <x-application-logo class="block h-7 w-auto fill-current text-gray-800" />
            </a>
            @php
            $pageTitle = match (true) {
            request()->routeIs('dashboard') => 'Dashboard',
            request()->routeIs('visualisasi.create') => 'Harmonisasi',
            request()->routeIs('data.edit') => 'Edit Data',
            request()->routeIs('data.finalisasi') => 'Finalisasi Data',
            request()->routeIs('master.komoditas') => 'Master Komoditas',
            request()->routeIs('master.wilayah') => 'Master Wilayah',
            request()->routeIs('master.alasan') => 'Master Alasan',
            request()->routeIs('rekon.pemilihan') => 'Pemilihan Komoditas',
            request()->routeIs('rekon.pengisian') => 'Pengisian Rekonsiliasi',
            request()->routeIs('rekon.pembahasan') => 'Pembahasan Rekonsiliasi',
            request()->routeIs('profile.edit', 'user.index') => 'User',
            request()->routeIs('pengaturan') => 'Pengaturan',
            default => 'Dashboard',
            };
            @endphp
            <span class="text-lg font-semibold text-primary-900 ml-2">{{ $pageTitle }}</span>
        </div>

        <!-- Mobile Menu Button -->
        <button data-collapse-toggle="navbar-default" type="button" class="inline-flex items-center p-2 w-10 h-10 justify-center text-sm text-gray-500 rounded-lg md:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200" aria-controls="navbar-default" aria-expanded="false">
            <span class="sr-only">Open main menu</span>
            <span class="material-symbols-rounded">keyboard_arrow_down</span>
        </button>

        <!-- Navigation -->
        <div class="hidden w-full md:block md:w-auto" id="navbar-default">
            <ul class="font-medium flex flex-col p-4 md:p-0 mt-4 border border-gray-100 rounded-lg bg-gray-50 md:flex-row md:space-x-8 md:mt-0 md:border-0 md:bg-white">

                <!-- Dashboard -->
                <li>
                    <a href="{{ route('dashboard') }}" class="block py-2 px-3 rounded-sm md:p-0 {{ request()->routeIs('dashboard') ? 'text-white bg-primary-700 md:bg-transparent md:text-primary-700' : 'text-gray-900 hover:bg-gray-100 md:hover:bg-transparent md:hover:text-primary-700' }}">Dashboard</a>
                </li>

                <!-- Administrasi -->
                @if (auth()->user()->isPusat())
                <li>
                    <button id="dropdownNavbarLink4" data-dropdown-toggle="dropdownAkun" class="flex items-center justify-between w-full py-2 px-3 rounded-sm md:p-0 md:w-auto {{ request()->routeIs('user.index', 'profile.edit') ? 'text-primary-700' : 'text-gray-900 hover:bg-gray-100 md:hover:bg-transparent md:hover:text-primary-700' }}">Administrasi
                        <span class="material-symbols-rounded">keyboard_arrow_down</span>
                    </button>
                    <div id="dropdownAkun" class="z-15 hidden font-normal bg-white divide-y divide-gray-100 rounded-lg shadow-sm w-44">
                        <ul class="py-2 text-sm text-gray-700">
                            <li><a href="{{ route('user.index') }}" class="block px-4 py-2 hover:bg-gray-100">Kelola Akun</a></li>
                        </ul>
                    </div>
                </li>
                @endif

                <!-- Data -->
                @if (auth()->user()->isPusat())
                <li>
                    <button id="dropdownNavbarLink" data-dropdown-toggle="dropdownNavbar" class="flex items-center justify-between w-full py-2 px-3 rounded-sm md:p-0 md:w-auto {{ request()->routeIs('data.*', 'master.*') ? 'text-primary-700' : 'text-gray-900 hover:bg-gray-100 md:hover:bg-transparent md:hover:text-primary-700' }}">Data
                        <span class="material-symbols-rounded">keyboard_arrow_down</span>
                    </button>
                    <div id="dropdownNavbar" class="z-10 hidden font-normal bg-white divide-y divide-gray-100 rounded-lg shadow-sm w-44">
                        <ul class="py-2 text-sm text-gray-700">
                            <li><a href="{{ route('data.create') }}" class="block px-4 py-2 hover:bg-gray-100">Upload</a></li>
                            <li><a href="{{ route('data.edit') }}" class="block px-4 py-2 hover:bg-gray-100">Edit</a></li>
                            <li><a href="{{ route('data.finalisasi') }}" class="block px-4 py-2 hover:bg-gray-100">Finalisasi</a></li>
                            <li>
                                <button id="doubleDropdownButton" data-dropdown-toggle="doubleDropdown" data-dropdown-placement="right-start" type="button" class="flex items-center justify-between w-full px-4 py-2 hover:bg-gray-100">Master
                                    <span class="material-symbols-rounded">keyboard_arrow_down</span>
                                </button>
                                <div id="doubleDropdown" class="z-10 hidden bg-white divide-y divide-gray-100 rounded-lg shadow-sm w-44">
                                    <ul class="py-2 text-sm text-gray-700">
                                        <li><a href="{{ route('master.komoditas') }}" class="block px-4 py-2 hover:bg-gray-100">Master Komoditas</a></li>
                                        <li><a href="{{ route('master.wilayah') }}" class="block px-4 py-2 hover:bg-gray-100">Master Wilayah</a></li>
                                        <li><a href="{{ route('master.alasan') }}" class="block px-4 py-2 hover:bg-gray-100">Master Alasan</a></li>
                                    </ul>
                                </div>
                            </li>
                        </ul>
                    </div>
                </li>
                @endif

                <!-- Harmonisasi -->
                @if (auth()->user()->isPusat())
                <li>
                    <a href="{{ route('visualisasi.create') }}" class="block py-2 px-3 rounded-sm md:p-0 {{ request()->routeIs('visualisasi.create') ? 'text-white bg-primary-700 md:bg-transparent md:text-primary-700' : 'text-gray-900 hover:bg-gray-100 md:hover:bg-transparent md:hover:text-primary-700' }}">Harmonisasi</a>
                </li>
                @endif

                <!-- Rekonsiliasi -->
                <li>
                    <button id="dropdownNavbarLink3" data-dropdown-toggle="dropdownRekon" class="flex items-center justify-between w-full py-2 px-3 rounded-sm md:p-0 md:w-auto {{ request()->routeIs('rekon.*') ? 'text-primary-700' : 'text-gray-900 hover:bg-gray-100 md:hover:bg-transparent md:hover:text-primary-700' }}">Rekonsiliasi
                        <span class="material-symbols-rounded">keyboard_arrow_down</span>
                    </button>
                    <div id="dropdownRekon" class="z-15 hidden font-normal bg-white divide-y divide-gray-100 rounded-lg shadow-sm w-44">
                        <ul class="py-2 text-sm text-gray-700">
                            @if (auth()->user()->isPusat())
                            <li><a href="{{ route('rekon.pemilihan') }}" class="block px-4 py-2 hover:bg-gray-100">Pemilihan</a></li>
                            <li><a href="{{ route('rekon.pengisian') }}" class="block px-4 py-2 hover:bg-gray-100">Pengisian</a></li>
                            <li><a href="{{ route('rekon.pembahasan') }}" class="block px-4 py-2 hover:bg-gray-100">Pembahasan</a></li>
                            @else
                            <li><a href="{{ route('rekon.pengisian-skl') }}" class="block px-4 py-2 hover:bg-gray-100">Pengisian</a></li>
                            @endif
                        </ul>
                    </div>
                </li>

                <!-- Pengaturan -->
                @if (auth()->user()->isPusat())
                <li>
                    <button id="dropdownNavbarLink5" data-dropdown-toggle="dropdownPengaturan" class="flex items-center justify-between w-full py-2 px-3 rounded-sm md:p-0 md:w-auto {{ request()->routeIs('pengaturan', 'profile.edit') ? 'text-primary-700' : 'text-gray-900 hover:bg-gray-100 md:hover:bg-transparent md:hover:text-primary-700' }}">Pengaturan
                        <span class="material-symbols-rounded">keyboard_arrow_down</span>
                    </button>
                    <div id="dropdownPengaturan" class="z-15 hidden font-normal bg-white divide-y divide-gray-100 rounded-lg shadow-sm w-44">
                        <ul class="py-2 text-sm text-gray-700">
                            <li><a href="{{ route('pengaturan') }}" class="block px-4 py-2 hover:bg-gray-100">Periode Aktif</a></li>
                        </ul>
                    </div>
                </li>
                @endif

                <!-- Logout/Profile -->
                <li>
                    <button id="dropdownNavbarLink6" data-dropdown-toggle="dropdownLogout" class="flex items-center justify-between w-full py-2 px-3 rounded-sm md:p-0 md:w-auto text-gray-900 hover:bg-gray-100 md:hover:bg-transparent md:hover:text-primary-700">
                        <span class="material-symbols-rounded">person</span>
                    </button>
                    <div id="dropdownLogout" class="z-15 hidden font-normal bg-white divide-y divide-gray-100 rounded-lg shadow-sm w-44">
                        <div class="px-4 py-3">
                            <span class="block text-sm text-gray-900 ">{{ auth()->user()->nama_lengkap }}</span>
                            <span class="block text-sm  text-gray-500 truncate ">{{ auth()->user()->wilayah_nama_display }}</span>
                            <span class="block text-sm  text-gray-500 truncate ">{{ auth()->user()->level_nama }}</span>
                        </div>

                        <ul class="py-2 text-sm text-gray-700">
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class=" px-4 py-2 hover:bg-gray-100 w-full text-left flex items-center">
                                        <span class="material-symbols-rounded mr-2">logout</span> Keluar
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </li>

            </ul>
        </div>
    </div>
</nav>