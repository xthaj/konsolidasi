<x-one-panel-layout>

    @section('vite')
    @vite(['resources/css/app.css', 'resources/js/pengaturan.js'])
    @endsection

    <!-- Bulan Tahun Aktif Section -->
    <div class="mb-4">
        <h1 class="text-lg font-semibold">Bulan dan Tahun Aktif</h1>

        <!-- Bulan & Tahun -->
        <div class="flex gap-4">
            <div class="w-1/2">
                <label class="block mb-2 text-sm font-medium text-gray-900">Bulan<span class="text-red-500 ml-1">*</span></label>
                <select name="bulan" x-model="bulan" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                    <template x-for="[nama, bln] in bulanOptions" :key="bln">
                        <option :value="bln" :selected="bulan == bln" x-text="nama"></option>
                    </template>
                </select>
            </div>
            <div class="w-1/2">
                <label class="block mb-2 text-sm font-medium text-gray-900">Tahun<span class="text-red-500 ml-1">*</span></label>
                <select name="tahun" x-model="tahun" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                    <template x-for="year in tahunOptions" :key="year">
                        <option :value="year" :selected="year == tahun" x-text="year"></option>
                    </template>
                </select>
            </div>
        </div>
        <p x-show="isActivePeriod" class="mt-4 text-sm text-gray-500">Periode aktif</p>

        <!-- Ganti Bulan Tahun Aktif Button -->
        <div class="justify-end flex mt-4">
            <x-primary-button
                type="button"
                @click="updateBulanTahun">
                Aktifkan
            </x-primary-button>
        </div>

        <!-- Efek Section -->
        <div class="mt-6">
            <h2 class="text-lg font-semibold text-gray-900 ">Efek</h2>
            <div class="mt-2">
                <span class="block text-sm font-medium text-gray-800">Pusat</span>
                <ul class="list-disc list-outside ml-5 mt-1 text-xs text-gray-600 ">
                    <li>Default bulan tahun untuk tiap halaman</li>
                    <li>Penambahan komoditas rekonsiliasi hanya di bulan tahun aktif</li>
                </ul>
                <span class="block text-sm font-medium text-gray-800 mt-3">Satuan Kerja Lainnya</span>
                <ul class="list-disc list-outside ml-5 mt-1 text-xs text-gray-600 ">
                    <li>Hanya dapat melihat & mengisi rekonsiliasi di bulan tahun aktif</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <x-modal name="success-modal" title="Berhasil" maxWidth="md">
        <div class="text-gray-900 ">
            <p x-text="modalMessage"></p>
            <div class="mt-4 flex justify-end">
                <x-primary-button type="button" x-on:click="$dispatch('close')">Tutup</x-primary-button>
            </div>
        </div>
    </x-modal>

    <!-- Error Modal -->
    <x-modal name="error-modal" title="Kesalahan" maxWidth="md">
        <div class="text-gray-900 ">
            <p x-text="modalMessage"></p>
            <div class="mt-4 flex justify-end">
                <x-primary-button type="button" x-on:click="$dispatch('close')">Tutup</x-primary-button>
            </div>
        </div>
    </x-modal>
</x-one-panel-layout>