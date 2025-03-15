@extends('layouts.app')

@section('vite')
    @vite(['resources/css/app.css', 'resources/js/alpine-init.js', 'resources/js/register.js', 'resources/js/alpine-start.js'])
@endsection

@section("content")
    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-4 lg:px-6">
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="p-6">
                    @if (session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('errors'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                            <ul>
                                @foreach (session('errors')->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (session('import_errors'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                            <ul>
                                @foreach (session('import_errors')->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="gap-4">
                        <!-- Download Template -->
                         <p>Download</p>
                        <a href="#" class="text-primary-600 hover:underline font-medium">
                            Template
                        </a>

                        <a href="/komoditas/export" class="text-primary-600 hover:underline font-medium">
                            Master Komoditas
                        </a>

                        <a href="/wilayah/export" class="text-primary-600 hover:underline font-medium">
                            Master Wilayah
                        </a>
                    </div>


                    <!-- Upload Form -->
                    <form action="{{ route('data.upload') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="grid grid-cols-3 gap-4 mt-4">
                            <!-- Bulan -->
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-900">Bulan</label>
                                <select id="bulan" name="bulan" class="border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 bg-gray-50 focus:ring-primary-500 focus:border-primary-500">
                                    @foreach(['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'] as $index => $bulan)
                                        <option value="{{ $index + 1 }}">{{ $bulan }}</option>
                                    @endforeach
                                </select>
                                @error('bulan')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Tahun -->
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-900">Tahun</label>
                                <select id="tahun" name="tahun" class="border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 bg-gray-50 focus:ring-primary-500 focus:border-primary-500">
                                    @for ($year = 2020; $year <= 2025; $year++)
                                        <option value="{{ $year }}">{{ $year }}</option>
                                    @endfor
                                </select>
                                @error('tahun')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Level Harga -->
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-900">Level Harga</label>
                                <select id="level" name="level" class="border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 bg-gray-50 focus:ring-primary-500 focus:border-primary-500">
                                    <option value="01">Harga Konsumen Kota</option>
                                    <option value="02">Harga Konsumen Desa</option>
                                    <option value="03">Harga Perdagangan Besar</option>
                                    <option value="04">Harga Produsen Desa</option>
                                    <option value="05">Harga Produsen</option>
                                </select>
                                @error('level')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <p id="helper-text-explanation" class="mt-2 text-sm text-gray-500">Periode aktif</p>

                        <!-- File Upload -->
                        <div class="mt-4">
                            <label class="block mb-1 text-sm font-medium text-gray-900" for="file_input">Upload File</label>
                            <input name="file" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:ring-primary-500 focus:border-primary-500" id="file_input" type="file">
                            <p class="mt-1 text-xs text-gray-500">Format: Excel (XLSX, CSV). Maks 5MB.</p>
                            @error('file')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Buttons (Side by Side) -->
                        <div class="mt-6 flex justify-end gap-4">
                            <button type="submit" class="px-5 py-2.5 text-white bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm">
                                Upload Data
                            </button>
                    </form>

                    <!-- Hapus Data Form -->
                    <form action="{{ route('data.hapus') }}" method="POST" x-data @submit.prevent="document.getElementById('hapus_bulan').value = document.getElementById('bulan').value; document.getElementById('hapus_tahun').value = document.getElementById('tahun').value; document.getElementById('hapus_level').value = document.getElementById('level').value; $el.submit();">
                        @csrf
                        <input type="hidden" id="hapus_bulan" name="bulan" value="">
                        <input type="hidden" id="hapus_tahun" name="tahun" value="">
                        <input type="hidden" id="hapus_level" name="level" value="">
                            <button type="submit" data-tooltip-target="tooltip-default" class="px-5 py-2.5 text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm">
                                Hapus Data
                            </button>
                        </div>

                        <div id="tooltip-default" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-xs opacity-0 tooltip dark:bg-gray-700">
                            Hapus semua data di waktu & level terpilih (revisi data)
                            <div class="tooltip-arrow" data-popper-arrow></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
