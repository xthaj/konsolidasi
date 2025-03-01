@extends('layouts.app')
@section("content")
    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-4 lg:px-6">
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="p-6">

                    <!-- Download Template -->
                    <a href="#" class="text-primary-600 hover:underline font-medium">
                        Download Template
                    </a>

                    <!-- Periode Selection -->
                    <div class="grid grid-cols-3 gap-4 mt-4">
                        <!-- Bulan -->
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-900">Bulan</label>
                            <select class="border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 bg-gray-50 focus:ring-primary-500 focus:border-primary-500">
                                @foreach(['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'] as $bulan)
                                    <option>{{ $bulan }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Tahun -->
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-900">Tahun</label>
                            <select class="border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 bg-gray-50 focus:ring-primary-500 focus:border-primary-500">
                                @for ($year = 2020; $year <= 2025; $year++)
                                    <option>{{ $year }}</option>
                                @endfor
                            </select>
                        </div>

                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-900">Level Harga</label>
                            <select class="border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 bg-gray-50 focus:ring-primary-500 focus:border-primary-500">
                                    <option>{{ "Harga Konsumen Kota" }}</option>
                                    <option>{{ "Harga Konsumen Desa" }}</option>
                                    <option>{{ "Harga Perdagangan Besar" }}</option>
                                    <option>{{ "Harga Produsen Desa" }}</option>
                                    <option>{{ "Harga Produsen" }}</option>
                            </select>
                        </div>
                    </div>

                    <p id="helper-text-explanation" class="mt-2 text-sm text-gray-500">Periode aktif</p>

                    <!-- File Upload -->
                    <div class="mt-4">
                        <label class="block mb-1 text-sm font-medium text-gray-900" for="file_input">Upload File</label>
                        <input class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:ring-primary-500 focus:border-primary-500" id="file_input" type="file">
                        <p class="mt-1 text-xs text-gray-500">Format: Excel (XLSX, CSV). Maks 5MB.</p>
                    </div>

                    <!-- Submit Button -->
                    <div class="mt-6 text-right">
                        <button class="w-full sm:w-auto px-5 py-2.5 text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm">
                            Hapus Data
                        </button>

                        <button class="w-full sm:w-auto px-5 py-2.5 text-white bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm">
                            Upload Data
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
