<x-two-panel-layout>
    @section('vite')
    @vite(['resources/css/app.css', 'resources/js/pemilihan.js'])
    @endsection

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

    <!-- Confirm Add Modal -->
    <x-modal name="confirm-add" focusable title="Konfirmasi Penambahan Data">
        <div class="px-6 py-4">
            <p x-show="modalContent.success" x-text="`Tambah ${modalContent.items.length} item?`"></p>
            <div x-show="!modalContent.success">
                <p class="text-red-600">Beberapa data tidak ditemukan:</p>
                <ul class="list-disc pl-5 mt-2">
                    <template x-for="missing in modalContent.missingItems" :key="missing">
                        <li x-text="missing"></li>
                    </template>
                </ul>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                <x-primary-button x-show="modalContent.success" @click="confirmAddToTable">Tambah</x-primary-button>
            </div>
        </div>
    </x-modal>

    <!-- Limit Error Modal -->
    <x-modal name="limit-error" focusable title="Kesalahan">
        <div class="px-6 py-4">
            <p>Terlalu banyak kombinasi yang dipilih (maksimal 100).</p>
            <p class="mt-2">Tambah rekonsiliasi dapat dilakukan berkali-kali sampai Konfirmasi Rekonsiliasi.</p>
            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">Mengerti</x-secondary-button>
            </div>
        </div>
    </x-modal>

    <!-- Sidebar -->
    <x-slot name="sidebar">
        <ol class="space-y-4 w-full mb-6">
            <li>
                <div class="w-full p-4 border rounded-lg" :class="tableData.length === 0 ? 'text-blue-700 bg-blue-100 border-blue-300 dark:bg-gray-800 dark:border-blue-800 dark:text-blue-400' : 'text-green-700 bg-green-50 border-green-300 dark:bg-gray-800 dark:border-green-800 dark:text-green-400'">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-medium">1. Tambah ke Tabel</h3>
                            <p class="text-xs text-gray-500  mt-1">
                                Pilih provinsi, kabupaten/kota, dan komoditas, lalu tambahkan ke tabel untuk ditinjau. Komoditas rekonsiliasi <b>belum</b> tersimpan.
                            </p>
                        </div>
                        <svg x-show="tableData.length > 0" class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 12">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5.917 5.724 10.5 15 1.5" />
                        </svg>
                        <svg x-show="tableData.length === 0" class="rtl:rotate-180 w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9" />
                        </svg>
                    </div>
                </div>
            </li>
            <li>
                <div class="w-full p-4 border rounded-lg" :class="tableData.length > 0 ? 'text-blue-700 bg-blue-100 border-blue-300 dark:bg-gray-800 dark:border-blue-800 dark:text-blue-400' : 'text-gray-900 bg-gray-100 border-gray-300 dark:bg-gray-800 dark:border-gray-700 '">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-medium">2. Konfirmasi Komoditas</h3>
                            <p class="text-xs text-gray-500  mt-1">
                                Klik konfirmasi untuk menyelesaikan pemilihan komoditas rekonsiliasi.
                            </p>
                        </div>
                        <svg x-show="tableData.length > 0" class="rtl:rotate-180 w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9" />
                        </svg>
                    </div>
                </div>
            </li>
        </ol>

        <div class="space-y-4">
            <!-- Bulan & Tahun -->
            <div class="flex gap-4">
                <div class="w-1/2">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Bulan</label>
                    <select name="bulan" x-model="bulan" required class="bg-gray-200 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 cursor-not-allowed" disabled>
                        <template x-for="[nama, bln] in bulanOptions" :key="bln">
                            <option :value="bln" :selected="bulan == bln" x-text="nama"></option>
                        </template>
                    </select>
                </div>
                <div class="w-1/2">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Tahun</label>
                    <select name="tahun" required class="bg-gray-200 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 cursor-not-allowed" disabled>
                        <template x-for="year in tahunOptions" :key="year">
                            <option :value="year" :selected="year == tahun" x-text="year"></option>
                        </template>
                    </select>
                </div>
            </div>

            <!-- Level Harga -->
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-900">Level Harga<span class="text-red-500 ml-1">*</span></label>
                <select name="kd_level" x-model="selectedKdLevel" @change="updateKdWilayah()" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                    <option value="01" :selected="selectedKdLevel === '01'">Harga Konsumen Kota</option>
                    <option value="02" :selected="selectedKdLevel === '02'">Harga Konsumen Desa</option>
                    <option value="03" :selected="selectedKdLevel === '03'">Harga Perdagangan Besar</option>
                    <option value="04" :selected="selectedKdLevel === '04'">Harga Produsen Desa</option>
                    <option value="05" :selected="selectedKdLevel === '05'">Harga Produsen</option>
                </select>
            </div>

            <!-- Provinsi -->
            <div class="flex justify-between items-center mb-2">
                <label class="text-sm font-medium text-gray-900 ">Provinsi</label>
                <div class="flex items-center p-2 rounded-sm hover:bg-gray-100 dark:hover:bg-gray-600">
                    <input type="checkbox" id="select-all-provinces" :checked="selectAllProvincesChecked" @click="selectAllProvinces($event.target.checked)" class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded-sm">
                    <label for="select-all-provinces" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Pilih Semua</label>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-300  dark:border-gray-600">
                <div class="p-3">
                    <div class="relative">
                        <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-500 " aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                            </svg>
                        </div>
                        <input type="text" id="input-group-search-provinsi" @input="searchProvince($event.target.value)" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full ps-10 p-2.5" placeholder="Cari provinsi">
                    </div>
                </div>
                <ul class="max-h-48 px-3 pb-3 overflow-y-auto text-sm text-gray-700 dark:text-gray-200">
                    <template x-for="provinsi in filteredProvinces" :key="provinsi.kd_wilayah">
                        <li>
                            <div class="flex items-center p-2 rounded-sm hover:bg-gray-100 dark:hover:bg-gray-600">
                                <input type="checkbox" :id="'provinsi-' + provinsi.kd_wilayah" :checked="selectedProvinces.some(p => p.kd_wilayah === provinsi.kd_wilayah)" @click="toggleProvince(provinsi)" class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded-sm">
                                <label :for="'provinsi-' + provinsi.kd_wilayah" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300" x-text="provinsi.nama_wilayah"></label>
                            </div>
                        </li>
                    </template>
                </ul>
            </div>

            <!-- Kabupaten/Kota -->
            <div x-show="selectedKdLevel === '01'" class="mb-4">
                <div class="flex justify-between items-center mb-2">
                    <label class="text-sm font-medium text-gray-900 ">Kabupaten/Kota</label>
                    <div class="flex items-center p-2 rounded-sm hover:bg-gray-100 dark:hover:bg-gray-600">
                        <input type="checkbox" id="select-all-kabkots" :checked="selectAllKabkotsChecked" @click="selectAllKabkots($event.target.checked)" class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded-sm">
                        <label for="select-all-kabkots" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Pilih Semua</label>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-300  dark:border-gray-600">
                    <div class="p-3">
                        <div class="relative">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-500 " aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                                </svg>
                            </div>
                            <input type="text" id="input-group-search-kabkot" @input="searchKabkot($event.target.value)" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full ps-10 p-2.5" placeholder="Cari kabupaten/kota">
                        </div>
                    </div>
                    <ul class="max-h-48 px-3 pb-3 overflow-y-auto text-sm text-gray-700 dark:text-gray-200">
                        <template x-for="kabkot in filteredKabkots" :key="kabkot.kd_wilayah">
                            <li>
                                <div class="flex items-center p-2 rounded-sm hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <input type="checkbox" :id="'kabkot-' + kabkot.kd_wilayah" :checked="selectedKabkots.some(k => k.kd_wilayah === kabkot.kd_wilayah)" @click="toggleKabkot(kabkot)" class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded-sm">
                                    <label :for="'kabkot-' + kabkot.kd_wilayah" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300" x-text="kabkot.nama_wilayah"></label>
                                </div>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>

            <!-- Komoditas -->
            <div>
                <div class="flex justify-between items-center mb-2">
                    <label class="text-sm font-medium text-gray-900 ">Komoditas</label>
                    <div class="flex items-center p-2 rounded-sm hover:bg-gray-100 dark:hover:bg-gray-600">
                        <input type="checkbox" id="select-all-komoditas" :checked="selectAllKomoditasChecked" @click="selectAllKomoditas($event.target.checked)" class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded-sm">
                        <label for="select-all-komoditas" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Pilih Semua</label>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-300  dark:border-gray-600">
                    <div class="p-3">
                        <div class="relative">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-500 " aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                                </svg>
                            </div>
                            <input type="text" id="input-group-search-komoditas" @input="searchKomoditas($event.target.value)" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full ps-10 p-2.5" placeholder="Cari komoditas">
                        </div>
                    </div>
                    <ul class="max-h-48 px-3 pb-3 overflow-y-auto text-sm text-gray-700 dark:text-gray-200">
                        <template x-for="komoditas in filteredKomoditas" :key="komoditas.kd_komoditas">
                            <li>
                                <div class="flex items-center p-2 rounded-sm hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <input type="checkbox" :id="'komoditas-' + komoditas.kd_komoditas" :value="komoditas.kd_komoditas" x-model="selectedKomoditas" @change="updateSelectAllKomoditas()" class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded-sm">
                                    <label :for="'komoditas-' + komoditas.kd_komoditas" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300" x-text="komoditas.nama_komoditas"></label>
                                </div>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>

            <!-- Error Message -->
            <div x-show="errorMessage" class="my-2 text-sm text-red-600">
                <span x-text="errorMessage"></span>
            </div>

            <x-secondary-button class="!w-full" @click="addRow">Tambah ke Tabel</x-secondary-button>

            <form @submit.prevent="confirmRekonsiliasi">
                @csrf
                <template x-for="item in tableData" :key="item.inflasi_id">
                    <div>
                        <input type="hidden" name="inflasi_ids[]" :value="item.inflasi_id">
                        <input type="hidden" name="bulan_tahun_ids[]" :value="item.bulan_tahun_id">
                    </div>
                </template>
                <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg text-sm px-5 py-2.5" :class="{ 'opacity-50 cursor-not-allowed': tableData.length === 0 }" :disabled="tableData.length === 0">Konfirmasi Komoditas</button>
            </form>
        </div>
    </x-slot>

    <!-- Main Content -->
    <div class="bg-white shadow-sm sm:rounded-lg">
        <div class="relative overflow-x-auto sm:rounded-lg md:max-h-[90vh] overflow-y-auto">
            <table class="w-full text-sm text-left text-gray-500 ">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 sticky top-0 z-10 shadow-sm">
                    <tr>
                        <th scope="col" class="px-6 py-3">No</th>
                        <th scope="col" class="px-6 py-3">Kode Wilayah</th>
                        <th scope="col" class="px-6 py-3">Nama Wilayah</th>
                        <th scope="col" class="px-6 py-3">Kode Komoditas</th>
                        <th scope="col" class="px-6 py-3">Nama Komoditas</th>
                        <th scope="col" class="px-6 py-3">Level Harga</th>
                        <th scope="col" class="px-6 py-3">Inflasi</th>
                        <th scope="col" class="px-6 py-3"><span class="sr-only">Hapus</span></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(item, index) in tableData" :key="index">
                        <tr class="bg-white border-b border-gray-200">
                            <td class="px-6 py-4" x-text="index + 1"></td>
                            <td class="px-6 py-4" x-text="item.kd_wilayah"></td>
                            <td class="px-6 py-4" x-text="item.nama_wilayah"></td>
                            <td class="px-6 py-4" x-text="item.kd_komoditas"></td>
                            <td class="px-6 py-4" x-text="item.nama_komoditas"></td>
                            <td class="px-6 py-4" x-text="item.nama_kd_level"></td>
                            <td class="px-6 py-4" x-text="item.nilai_inflasi === '-' ? '-' : (parseFloat(item.nilai_inflasi) < 1 && parseFloat(item.nilai_inflasi) > -1 && parseFloat(item.nilai_inflasi) !== 0 ? (parseFloat(item.nilai_inflasi) > 0 ? '0' : '-0') : '') + Math.abs(parseFloat(item.nilai_inflasi)).toFixed(2).replace(/^0+/, '') + '%'"></td>
                            <td class="px-6 py-4 text-right">
                                <button @click="removeRow(index)" class="font-medium text-red-600  hover:underline">Hapus</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</x-two-panel-layout>