@extends('layouts.guest')

@section('vite')
@vite(['resources/css/app.css', 'resources/js/register.js'])
@endsection

@section('content')
<h1 class="text-center text-xl font-bold leading-tight tracking-tight text-gray-900 md:text-2xl">
    Buat Akun
</h1>

<form method="POST" action="{{ route('register') }}" x-data="webData" x-init="init()" @submit.prevent="validateForm" class="space-y-4">
    @csrf
    <!-- Full Name -->
    <div>
        <label for="nama_lengkap" class="block mb-2 text-sm font-medium text-gray-900">Nama lengkap</label>
        <input type="text" id="nama_lengkap" name="nama_lengkap" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5" placeholder="Muhammad Hatta" required />
    </div>

    <!-- Username -->
    <div>
        <label for="username" class="block mb-2 text-sm font-medium text-gray-900">Username</label>
        <input type="text" id="username" name="username" x-model="username" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5" placeholder="hatta45" required />
        <template x-if="errors.usernameLength">
            <p class="mt-2 text-sm text-red-600">Username harus lebih dari 6 karakter.</p>
        </template>
        <template x-if="errors.usernameUnique">
            <p class="mt-2 text-sm text-red-600">Username sudah digunakan.</p>
        </template>
    </div>

    <div>
        <!-- Checkbox -->
        <label class="block mb-2 text-sm font-medium text-gray-900">Satuan Kerja</label>

        <div class="flex items-start my-2">
            <div class="flex items-center h-5">
                <input type="checkbox" name="is_pusat" id="is_pusat" value="1" x-model="isPusat" @click="togglePusat()" class="w-4 h-4 border border-gray-300 rounded-sm bg-gray-50 focus:ring-3 focus:ring-primary-300" />
            </div>
            <label for="is_pusat" class="ms-2 text-sm font-medium text-gray-900">Pusat</label>
        </div>

        <div :class="{ 'hidden': isPusat }">
            <div class="flex relative flex-col">
                <div class="flex">
                    <button @click="toggleDropdown('province')" id="provinsi-button" class="shrink-0 z-10 inline-flex items-center py-2.5 px-4 text-sm font-medium text-center text-gray-500 bg-gray-100 border border-gray-300 rounded-s-lg hover:bg-gray-200 focus:ring-4 focus:outline-none focus:ring-gray-100" type="button">
                        <span x-text="selectedProvince.nama_wilayah || 'Pilih Provinsi'"></span>
                    </button>

                    <!-- Province Dropdown -->
                    <div x-show="dropdowns.province" x-transition @click.away="closeDropdown('province')" class="z-10 bg-white divide-y divide-gray-100 rounded-lg shadow-sm w-44 absolute mt-2 max-h-60 overflow-y-auto">
                        <ul class="py-2 text-sm text-gray-700">
                            <template x-for="province in provinces" :key="province.kd_wilayah">
                                <li>
                                    <button @click="selectProvince(province)" type="button" class="inline-flex text-left w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <span x-text="province.nama_wilayah"></span>
                                    </button>
                                </li>
                            </template>
                        </ul>
                    </div>

                    <!-- Kabupaten Dropdown -->
                    <label for="kabkot" class="sr-only">Pilih Kabupaten</label>
                    <select id="kabkot" x-model="selectedKabkot" @change="updateKdWilayah()" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-e-lg border-s-gray-100 border-s-2 focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5">
                        <option value="" selected>Pilih Kabupaten</option>
                        <template x-for="kabkot in filteredKabkots" :key="kabkot.kd_wilayah">
                            <option :value="kabkot.kd_wilayah" x-text="kabkot.nama_wilayah"></option>
                        </template>
                    </select>

                    <input type="hidden" name="kd_wilayah" x-model="kd_wilayah">
                </div>
                <p id="helper-text-explanation" class="mt-2 text-sm text-gray-500">Pilih hanya provinsi atau provinsi kemudian Kab/Kota</p>
            </div>
        </div>
    </div>
    <template x-if="errors.kd_wilayah">
        <p class="mt-2 text-sm text-red-600">Satuan kerja belum dipilih.</p>
    </template>

    <!-- Password & confirm -->
    <div>
        <label for="password" class="block mb-2 text-sm font-medium text-gray-900">Password</label>
        <input type="password" name="password" id="password" x-model="password" placeholder="••••••••" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required="">
        <template x-if="errors.password">
            <p class="mt-2 text-sm text-red-600">Password minimal sepanjang 6 karakter.</p>
        </template>
    </div>
    <div>
        <label for="confirm-password" class="block mb-2 text-sm font-medium text-gray-900">Konfirmasi password</label>
        <input type="password" id="confirm-password" x-model="confirmPassword" placeholder="••••••••" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required="">
        <template x-if="errors.confirmPassword">
            <p class="mt-2 text-sm text-red-600">Password dan konfirmasi password berbeda.</p>
        </template>
    </div>

    <button type="submit" class="w-full text-white bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Buat Akun</button>
    <p class="text-sm font-light text-gray-500">
        Sudah memiliki akun? <a href="{{ route('login') }}" class="font-medium text-primary-600 hover:underline">Login di sini</a>
    </p>
</form>
@endsection