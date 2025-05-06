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
        <input
            type="text"
            id="username"
            name="username"
            x-model="username"
            @input="username = $event.target.value.toLowerCase()"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5"
            placeholder="hatta45"
            required />
        <template x-if="errors.usernameLength">
            <p class="mt-2 text-sm text-red-600">Username harus lebih dari 6 karakter.</p>
        </template>
        <template x-if="errors.usernameUnique">
            <p class="mt-2 text-sm text-red-600">Username sudah digunakan.</p>
        </template>
    </div>

    <!-- Wilayah Selection -->
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-900">Satuan Kerja</label>
        <select x-model="wilayah_level" @change="updateWilayah()" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
            <option value="pusat">Pusat</option>
            <option value="provinsi">Provinsi</option>
            <option value="kabkot">Kabupaten/Kota</option>
        </select>
    </div>
    <div x-show="wilayah_level === 'provinsi' || wilayah_level === 'kabkot'">
        <label class="block mb-2 text-sm font-medium text-gray-900">Provinsi</label>
        <select x-model="selectedProvince" @change="selectedKabkot = ''; updateWilayah()" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
            <option value="">Pilih Provinsi</option>
            <template x-for="province in provinces" :key="province.kd_wilayah">
                <option :value="province.kd_wilayah" x-text="province.nama_wilayah"></option>
            </template>
        </select>
    </div>
    <div x-show="wilayah_level === 'kabkot'">
        <label class="block mb-2 text-sm font-medium text-gray-900">Kabupaten/Kota</label>
        <select x-model="selectedKabkot" @change="updateWilayah()" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
            <option value="">Pilih Kabupaten/Kota</option>
            <template x-for="kabkot in filteredKabkots" :key="kabkot.kd_wilayah">
                <option :value="kabkot.kd_wilayah" x-text="kabkot.nama_wilayah"></option>
            </template>
        </select>
    </div>
    <input type="hidden" name="kd_wilayah" x-model="kd_wilayah">
    <template x-if="errors.kd_wilayah">
        <p class="mt-2 text-sm text-red-600">Satuan kerja belum dipilih.</p>
    </template>

    <!-- Password & Confirm -->
    <div>
        <label for="password" class="block mb-2 text-sm font-medium text-gray-900">Password</label>
        <input type="password" name="password" id="password" x-model="password" placeholder="••••••••" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required />
        <template x-if="errors.password">
            <p class="mt-2 text-sm text-red-600">Password minimal sepanjang 6 karakter.</p>
        </template>
    </div>
    <div>
        <label for="confirm-password" class="block mb-2 text-sm font-medium text-gray-900">Konfirmasi password</label>
        <input type="password" id="confirm-password" x-model="confirmPassword" placeholder="••••••••" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required />
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