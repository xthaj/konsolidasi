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
        <input
            type="text"
            id="nama_lengkap"
            name="nama_lengkap"
            x-model="nama_lengkap"
            @input="validateNamaLengkap()"
            x-bind:class="{ 'border-red-600': errors.nama_lengkap }"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5"
            placeholder="Muhammad Hatta"
            required />
        <template x-if="errors.nama_lengkap">
            <p class="mt-2 text-sm text-red-600" x-text="errors.nama_lengkap"></p>
        </template>
        @error('nama_lengkap')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- Username -->
    <div>
        <label for="username" class="block mb-2 text-sm font-medium text-gray-900">Username</label>
        <input
            type="text"
            id="username"
            name="username"
            x-model="username"
            @input="username = $event.target.value.toLowerCase(); validateUsername()"
            x-bind:class="{ 'border-red-600': errors.username }"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5"
            placeholder="hatta45"
            required />
        <template x-if="errors.username">
            <p class="mt-2 text-sm text-red-600" x-text="errors.username"></p>
        </template>
        @error('username')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
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
        <select
            x-model="selectedProvince"
            @change="selectedKabkot = ''; updateWilayah()"
            x-bind:class="{ 'border-red-600': errors.kd_wilayah && !selectedProvince }"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
            <option value="">Pilih Provinsi</option>
            <template x-for="province in provinces" :key="province.kd_wilayah">
                <option :value="province.kd_wilayah" x-text="province.nama_wilayah"></option>
            </template>
        </select>
    </div>
    <div x-show="wilayah_level === 'kabkot'">
        <label class="block mb-2 text-sm font-medium text-gray-900">Kabupaten/Kota</label>
        <select
            x-model="selectedKabkot"
            @change="updateWilayah()"
            x-bind:class="{ 'border-red-600': errors.kd_wilayah && !selectedKabkot }"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm focus:ring-4 focus:outline-none focus:ring-blue-500 focus:ring-opacity-50 rounded-lg block w-full p-2.5">
            <option value="">Pilih Kabupaten/Kota</option>
            <template x-for="kabkot in filteredKabkots" :key="kabkot.kd_wilayah">
                <option :value="kabkot.kd_wilayah" x-text="kabkot.nama_wilayah"></option>
            </template>
        </select>
    </div>

    <input type="hidden" name="kd_wilayah" x-model="kd_wilayah">
    <input type="hidden" name="level" x-model="level">
    <input type="hidden" name="user_sso" value="0">

    <template x-if="errors.kd_wilayah && (wilayah_level === 'provinsi' || wilayah_level === 'kabkot')">
        <p class="mt-2 text-sm text-red-600">Satuan kerja belum dipilih.</p>
    </template>
    @error('kd_wilayah')
    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
    @error('level')
    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror

    <!-- Password & Confirm -->
    <div>
        <label for="password" class="block mb-2 text-sm font-medium text-gray-900">Password</label>
        <input
            type="password"
            name="password"
            id="password"
            x-model="password"
            @input="validatePassword()"
            x-bind:class="{ 'border-red-600': errors.password }"
            placeholder="••••••••"
            maxlength="255"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5"
            required />
        <template x-if="errors.password">
            <p class="mt-2 text-sm text-red-600" x-text="errors.password"></p>
        </template>
        @error('password')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label for="password_confirmation" class="block mb-2 text-sm font-medium text-gray-900">Konfirmasi password</label>
        <input
            type="password"
            id="password_confirmation"
            name="password_confirmation"
            x-model="confirmPassword"
            @input="validatePassword()"
            x-bind:class="{ 'border-red-600': errors.confirmPassword }"
            placeholder="••••••••"
            maxlength="255"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5"
            required />
        <template x-if="errors.confirmPassword">
            <p class="mt-2 text-sm text-red-600" x-text="errors.confirmPassword"></p>
        </template>
        @error('password_confirmation')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <button
        type="submit"
        x-bind:disabled="hasErrors"
        x-bind:class="{ 'opacity-50 cursor-not-allowed': hasErrors }"
        class="w-full text-white bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:outline-none font-medium rounded-lg text-sm px-5 py-2.5 text-center">
        Buat Akun
    </button>
    <p class="text-sm font-light text-gray-500">
        Sudah memiliki akun? <a href="{{ route('login') }}" class="font-medium text-primary-600 hover:underline">Login di sini</a>
    </p>
</form>

@endsection