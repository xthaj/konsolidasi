<x-one-panel-layout>

    @section('vite')
    @vite(['resources/css/app.css', 'resources/js/pengaturan.js'])
    @endsection

    <!-- Success Modal -->
    <x-modal name="success-modal" title="Berhasil" maxWidth="md">
        <div class="text-gray-900 dark:text-white">
            <p x-text="modalMessage"></p>
            <div class="mt-4 flex justify-end">
                <x-primary-button type="button" x-on:click="$dispatch('close')">Tutup</x-primary-button>
            </div>
        </div>
    </x-modal>

    <!-- Error Modal -->
    <x-modal name="error-modal" title="Kesalahan" maxWidth="md">
        <div class="text-gray-900 dark:text-white">
            <p x-text="modalMessage"></p>
            <div class="mt-4 flex justify-end">
                <x-primary-button type="button" x-on:click="$dispatch('close')">Tutup</x-primary-button>
            </div>
        </div>
    </x-modal>


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
    </div>
</x-one-panel-layout>