<x-one-panel-layout>

    @section('vite')
    @vite(['resources/css/app.css', 'resources/js/alpine-init.js', 'resources/js/upload-data.js', 'resources/js/alpine-start.js'])
    @endsection

    @if($errors->any())
    <div class="flex p-4 mb-4 text-red-800 rounded-lg border border-red-300 bg-red-50" role="alert">
        <svg class="shrink-0 inline w-4 h-4 me-3 mt-[2px]" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
        </svg>
        <div>
            <span class="font-medium">Kesalahan:</span>
            <ul class="mt-1.5 list-disc list-inside">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    @if(session('success'))
    <div class="flex p-4 mb-4 text-green-800 border border-green-300 rounded-lg bg-green-50" role="alert">
        <svg class="shrink-0 inline w-4 h-4 me-3 mt-[2px]" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
        </svg>
        <div>
            <span class="font-medium">Berhasil:</span>
            <ul class="mt-1.5 list-disc list-inside">
                @foreach((array) session('success') as $line)
                <li>{{ $line }}</li>
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

    <hr class="h-px my-8 bg-gray-200 border-0 dark:bg-gray-700">

    <div class="my-4">
        <h1 class="text-lg font-semibold">Upload/Update Data</h1>
    </div>

    <!-- Upload Form -->
    <form action="{{ route('data.upload') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Bulan -->
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-900">Bulan</label>
                <select name="bulan" x-model="bulan" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 focus:ring-primary-500 focus:border-primary-500">
                    @foreach(['Januari' => '01', 'Februari' => '02', 'Maret' => '03', 'April' => '04', 'Mei' => '05', 'Juni' => '06', 'Juli' => '07', 'Agustus' => '08', 'September' => '09', 'Oktober' => '10', 'November' => '11', 'Desember' => '12'] as $nama => $bln)
                    <option value="{{ $bln }}" @selected(request('bulan')==$bln)>{{ $nama }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Tahun -->
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-900">Tahun</label>
                <select name="tahun" x-model="tahun" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 focus:ring-primary-500 focus:border-primary-500">
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
            <input name="file"
                x-ref="fileInput"
                class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:ring-primary-500 focus:border-primary-500"
                id="file_input"
                type="file">
            <p class="mt-1 text-xs text-gray-500">Format: Excel (XLSX, CSV). Maks 5MB.</p>
            @error('file')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Submit Button with Loading Indicator and Error Message -->
        <div class="mt-2" x-data="{ loading: false, showError: false }">
            <!-- Error Message -->
            <div x-show="showError"
                class="text-sm text-red-600">
                Pilih file terlebih dahulu.
            </div>

            <div class="flex justify-end items-center gap-3">
                <div x-show="loading"
                    class="flex items-center">
                    <svg aria-hidden="true" role="status" class="inline w-4 h-4 text-gray-200 animate-spin" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="#E5E7EB" />
                        <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentColor" />
                    </svg>
                    <span class="text-sm text-gray-600 ml-2">Loading...</span>
                </div>

                <button type="submit"
                    @click="if (!$refs.fileInput.files.length) { $event.preventDefault(); showError = true; } else { loading = true; showError = false; }"
                    class="w-full sm:w-auto px-5 py-2.5 text-white bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm">
                    Upload/Update Data
                </button>

            </div>
        </div>
    </form>

    <hr class="h-px my-8 bg-gray-200 border-0 dark:bg-gray-700">

    <!-- Bulk Delete Section -->
    <div class="my-4">
        <h1 class="text-lg font-semibold">Hapus Data</h1>
    </div>

    <!-- Delete Form with Checkbox -->
    <form action="{{ route('data.hapus') }}" method="POST" x-data="{ loading: false, isChecked: false }" @submit="console.log('Form submitting'); loading = true">
        @csrf
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-900">Bulan</label>
                <select name="bulan" x-model="bulan" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 focus:ring-primary-500 focus:border-primary-500">
                    @foreach(['Januari' => '01', 'Februari' => '02', 'Maret' => '03', 'April' => '04', 'Mei' => '05', 'Juni' => '06', 'Juli' => '07', 'Agustus' => '08', 'September' => '09', 'Oktober' => '10', 'November' => '11', 'Desember' => '12'] as $nama => $bln)
                    <option value="{{ $bln }}" @selected(request('bulan')==$bln)>{{ $nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-900">Tahun</label>
                <select name="tahun" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 focus:ring-primary-500 focus:border-primary-500">
                    <template x-for="year in tahunOptions" :key="year">
                        <option :value="year" :selected="year === tahun" x-text="year"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-900">Level Harga</label>
                <select name="level" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 focus:ring-primary-500 focus:border-primary-500">
                    <option value="01">Harga Konsumen Kota</option>
                    <option value="02">Harga Konsumen Desa</option>
                    <option value="03">Harga Perdagangan Besar</option>
                    <option value="04">Harga Produsen Desa</option>
                    <option value="05">Harga Produsen</option>
                </select>
            </div>
        </div>

        <p id="helper-text-explanation" class="mt-2 text-sm text-gray-500" x-show="isActivePeriod">Periode aktif</p>

        <div class="mt-4">
            <label class="inline-flex items-center">
                <input type="checkbox" x-model="isChecked" required class="w-4 h-4 text-red-600 bg-gray-100 border-gray-300 rounded focus:ring-red-500">
                <span class="ml-2 text-sm text-gray-900">Hapus semua rekonsiliasi terkait</span>
            </label>
            <p class="mt-1 text-xs text-gray-500">Data yang dihapus tidak dapat dikembalikan.</p>
        </div>
        <div class="mt-6 flex justify-end items-center gap-3">
            <div x-show="loading" class="flex items-center">
                <svg aria-hidden="true" role="status" class="inline w-4 h-4 text-gray-200 animate-spin" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="#E5E7EB" />
                    <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentColor" />
                </svg>
                <span class="text-sm text-gray-600 ml-2">Loading...</span>
            </div>

            <button data-tooltip-target="tooltip-dark" type="submit"
                :disabled="loading || !isChecked"
                class="w-full sm:w-auto px-5 py-2.5 text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm disabled:bg-red-400">
                Hapus Data
            </button>

            <div id="tooltip-dark" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg shadow-xs opacity-0 tooltip dark:bg-gray-700">
                Ceklis untuk mengonfirmasi penghapusan data.
                <div class="tooltip-arrow" data-popper-arrow></div>
            </div>

        </div>
    </form>
</x-one-panel-layout>