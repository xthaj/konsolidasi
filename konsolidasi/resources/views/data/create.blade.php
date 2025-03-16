<x-one-panel-layout>

@section('vite')
    @vite(['resources/css/app.css', 'resources/js/alpine-init.js', 'resources/js/upload-data.js', 'resources/js/alpine-start.js'])
@endsection

@section("content")
                <!-- Success Message -->
                @if(session('success'))
                    <div class="flex p-4 mb-4 text-green-800 border border-green-300 rounded-lg bg-green-50" role="alert">
                        <svg class="shrink-0 inline w-4 h-4 me-3 mt-[2px]" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                        </svg>
                        <div>
                            <span class="font-medium">Berhasil:</span>
                            <p class="mt-1.5">{{ session('success') }}</p>
                        </div>
                    </div>
                @endif

                <!-- Error Message -->
                @if(session('error'))
                    <div class="flex p-4 mb-4 text-red-800 rounded-lg border border-red-300 bg-red-50" role="alert">
                        <svg class="shrink-0 inline w-4 h-4 me-3 mt-[2px]" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                        </svg>
                        <div>
                            <span class="font-medium">Kesalahan:</span>
                            <ul class="mt-1.5 list-disc list-inside">
                                @foreach(session('error') as $line)
                                    <li>{{ $line }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <!-- Validation Errors -->
                @if(session('errors'))
                    <div class="flex p-4 mb-4 text-yellow-800 rounded-lg bg-yellow-50" role="alert">
                        <svg class="shrink-0 inline w-4 h-4 me-3 mt-[2px]" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                        </svg>
                        <div>
                            <span class="font-medium">Perhatian:</span>
                            <ul class="mt-1.5 list-disc list-inside">
                                @foreach(session('errors') as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <!-- Download Section -->
                <div class="mb-4">
                    <h1 class="text-lg font-semibold">Download</h1>
                </div>

                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="#" class="text-primary-700 hover:text-white border border-primary-700 hover:bg-primary-700 focus:ring-4 focus:outline-none focus:ring-yellow-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center w-full sm:w-auto">
                        Template
                    </a>
                    <a href="/komoditas/export" class="text-primary-700 hover:text-white border border-primary-700 hover:bg-primary-700 focus:ring-4 focus:outline-none focus:ring-yellow-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center w-full sm:w-auto">
                        Master Komoditas
                    </a>
                    <a href="/wilayah/export" class="text-primary-700 hover:text-white border border-primary-700 hover:bg-primary-700 focus:ring-4 focus:outline-none focus:ring-yellow-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center w-full sm:w-auto">
                        Master Wilayah
                    </a>
                </div>

                <!-- Upload Form -->
                <form action="{{ route('data.upload') }}" method="POST" enctype="multipart/form-data" class="mt-6">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <!-- Bulan -->
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-900">Bulan</label>
                            <select name="bulan" x-model="bulan" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 focus:ring-primary-500 focus:border-primary-500">
                                @foreach(['Januari' => '01', 'Februari' => '02', 'Maret' => '03', 'April' => '04', 'Mei' => '05', 'Juni' => '06', 'Juli' => '07', 'Agustus' => '08', 'September' => '09', 'Oktober' => '10', 'November' => '11', 'Desember' => '12'] as $nama => $bln)
                                    <option value="{{ $bln }}" @selected(request('bulan') == $bln)>{{ $nama }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Tahun -->
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-900">Tahun</label>
                            <select name="tahun" x-model="tahun" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 focus:ring-primary-500 focus:border-primary-500">
                                <option value="">Pilih Tahun</option>
                                <template x-for="year in tahunOptions" :key="year">
                                    <option :value="year" :selected="year === tahun" x-text="year"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Level Harga -->
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-900">Level Harga</label>
                            <select id="level" name="level" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 focus:ring-primary-500 focus:border-primary-500">
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

                    <p id="helper-text-explanation" class="mt-2 text-sm text-gray-500" x-show="isActivePeriod">Periode aktif</p>

                    <!-- File Upload -->
                    <div class="mt-4">
                        <label class="block mb-1 text-sm font-medium text-gray-900" for="file_input">Upload File</label>
                        <input name="file" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:ring-primary-500 focus:border-primary-500" id="file_input" type="file">
                        <p class="mt-1 text-xs text-gray-500">Format: Excel (XLSX, CSV). Maks 5MB.</p>
                        @error('file')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="w-full sm:w-auto px-5 py-2.5 text-white bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm">
                            Upload Data
                        </button>
                    </div>
                </form>
@endsection

</x-one-panel-layout>

