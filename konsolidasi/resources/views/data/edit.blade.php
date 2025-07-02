<x-two-panel-layout>
    @section('vite')
    @vite(['resources/css/app.css', 'resources/js/data/edit.js'])
    @endsection

    <!-- Modal for table methods -->
    <x-modal name="confirm-delete" focusable title="Konfirmasi Hapus Inflasi" x-cloak>
        <div class="px-6 py-4">
            <p x-text="'Hapus inflasi komoditas ' + modalData.komoditas + '?'"></p>
            <div class="mt-4">
                <label class="flex items-center">
                    <input
                        type="checkbox"
                        x-model="deleteRekonsiliasi"
                        class="rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500">
                    <span class="ml-2 text-sm text-gray-600">Hapus juga rekonsiliasi berkaitan (wajib)</span>
                </label>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close-modal', 'confirm-delete')">Batal</x-secondary-button>
                <x-primary-button
                    @click="confirmDelete()"
                    x-bind:disabled="!deleteRekonsiliasi"
                    x-bind:class="{ 'opacity-50 cursor-not-allowed': !deleteRekonsiliasi }">
                    Hapus
                </x-primary-button>
            </div>
        </div>
    </x-modal>

    <!-- Success Modal -->
    <x-modal name="success-modal" title="Berhasil" maxWidth="md">
        <div class="text-gray-900">
            <p x-text="modalMessage"></p>
            <div class="mt-4 flex justify-end">
                <x-primary-button
                    type="button"
                    x-on:click="$dispatch('close-modal', 'success-modal')">
                    Tutup
                </x-primary-button>
            </div>
        </div>
    </x-modal>

    <!-- Edit Modal -->
    <x-modal name="edit-modal" title="Edit Inflasi" maxWidth="md">
        <div class="text-gray-900">
            <form id="edit-form" x-ref="editForm" @submit.prevent="editData()">
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900">Inflasi (persen)<span class="text-red-500 ml-1">*</span></label>
                        <input
                            type="number"
                            step="0.01"
                            x-model="edit_nilai_inflasi"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5"
                            required>
                    </div>
                    <div x-show="kd_wilayah == '0'">
                        <label class="block mb-2 text-sm font-medium text-gray-900">Andil (persen)</label>
                        <input
                            type="number"
                            step="0.01"
                            x-model="edit_andil"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                    </div>
                </div>
                <div class="mt-4 flex justify-end gap-3 px-6">
                    <x-secondary-button x-on:click="$dispatch('close-modal', 'edit-modal')">Batal</x-secondary-button>
                    <x-primary-button type="submit">
                        Simpan
                    </x-primary-button>
                </div>
            </form>
        </div>
    </x-modal>

    <!-- Error Modal -->
    <x-modal name="error-modal" title="Gagal" maxWidth="md">
        <div class="text-gray-900">
            <p x-text="modalMessage"></p>
            <div class="mt-4 flex justify-end">
                <x-primary-button
                    type="button"
                    x-on:click="$dispatch('close-modal', 'error-modal')">
                    Tutup
                </x-primary-button>
            </div>
        </div>
    </x-modal>

    <x-slot name="sidebar">
        <form id="filter-form" x-ref="filterForm" @submit.prevent="fetchData(1)">
            <div class="space-y-4 md:space-y-6 mt-4">
                <!-- Bulan & Tahun -->
                <div>
                    <div class="flex gap-4">
                        <div class="w-1/2">
                            <label class="block mb-2 text-sm font-medium text-gray-900">Bulan</label>
                            <select name="bulan" x-model="bulan" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                                <template x-for="[nama, bln] in bulanOptions" :key="bln">
                                    <option :value="bln" :selected="bulan == bln" x-text="nama"></option>
                                </template>
                            </select>
                        </div>
                        <div class="w-1/2">
                            <label class="block mb-2 text-sm font-medium text-gray-900">Tahun</label>
                            <select name="tahun" x-model="tahun" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                                <template x-for="year in tahunOptions" :key="year">
                                    <option :value="year" :selected="year == tahun" x-text="year"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                </div>

                <p id="helper-text-explanation" class="text-sm text-gray-500" x-show="isActivePeriod">Periode aktif</p>

                <!-- Level Harga -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">Level Harga</label>
                    <select name="kd_level" x-model="selectedKdLevel" @change="updateKdWilayah()" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="00" :selected="selectedKdLevel == '00'">Semua Level Harga</option>
                        <option value="01" :selected="selectedKdLevel == '01'">Harga Konsumen Kota</option>
                        <option value="02" :selected="selectedKdLevel == '02'">Harga Konsumen Desa</option>
                        <option value="03" :selected="selectedKdLevel == '03'">Harga Perdagangan Besar</option>
                        <option value="04" :selected="selectedKdLevel == '04'">Harga Produsen Desa</option>
                        <option value="05" :selected="selectedKdLevel == '05'">Harga Produsen</option>
                    </select>
                </div>

                <!-- Wilayah Selection -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">Level Wilayah</label>
                    <select x-model="wilayahLevel" @change="isPusat = wilayahLevel === 'pusat'; selectedProvince = ''; selectedKabkot = ''; updateKdWilayah()" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="pusat" :selected="isPusat">Nasional</option>
                        <option value="provinsi" :disabled="selectedKdLevel === '00'" :selected="!isPusat && selectedKabkot === ''">Provinsi</option>
                        <option value="kabkot" :disabled="selectedKdLevel !== '01'"
                            :selected="!isPusat && selectedKabkot !== ''">Kabupaten/Kota</option>
                    </select>
                </div>
                <div x-show="selectedKdLevel == '00'" class="mt-4 text-sm text-gray-500">
                    Data untuk seluruh level harga hanya dapat ditampilkan di level nasional.
                </div>
                <div x-show="wilayahLevel === 'provinsi' || wilayahLevel === 'kabkot'" class="mt-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Provinsi</label>
                    <select x-model="selectedProvince" @change="selectedKabkot = ''; updateKdWilayah()" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="" selected>Pilih Provinsi</option>
                        <template x-for="province in provinces" :key="province.kd_wilayah">
                            <option :value="province.kd_wilayah" x-text="province.nama_wilayah" :selected="province.kd_wilayah == selectedProvince"></option>
                        </template>
                    </select>
                </div>
                <div x-show="wilayahLevel === 'kabkot' && selectedKdLevel === '01'" class="mt-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Kabupaten/Kota</label>
                    <select x-model="selectedKabkot" @change="updateKdWilayah()" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="" selected>Pilih Kabupaten/Kota</option>
                        <template x-for="kabkot in filteredKabkots" :key="kabkot.kd_wilayah">
                            <option :value="kabkot.kd_wilayah" x-text="kabkot.nama_wilayah" :selected="kabkot.kd_wilayah == selectedKabkot"></option>
                        </template>
                    </select>
                </div>
                <div x-show="selectedKdLevel !== '01' && selectedKdLevel !== '00'" class="text-sm text-gray-500 mt-2">
                    Pilihan Kabupaten/Kota hanya tersedia untuk level harga <strong>Harga Konsumen Kota</strong>.
                </div>


                <input type="hidden" name="kd_wilayah" :value="kd_wilayah" required>

                <!-- Komoditas (Not Required) -->
                <div x-show="selectedKdLevel !== '00'">
                    <label for="komoditas" class="block mb-2 text-sm font-medium text-gray-900">Komoditas</label>
                    <select id="komoditas" name="kd_komoditas" x-model="selectedKomoditas" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                        <option value="">Semua Komoditas</option>
                        <template x-for="komoditi in komoditas" :key="komoditi.kd_komoditas">
                            <option :value="komoditi.kd_komoditas" x-text="komoditi.nama_komoditas"></option>
                        </template>
                    </select>
                </div>

                <!-- Sorting -->
                <div x-show="selectedKdLevel !== '00'" class="flex gap-4">
                    <div class="w-1/2">
                        <label class="block mb-2 text-sm font-medium text-gray-900">Urut Berdasarkan</label>
                        <select name="sort" x-model="sort" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                            <option value="kd_komoditas" :selected="sort === 'kd_komoditas'">Kode Komoditas</option>
                            <option value="nilai_inflasi" :selected="sort === 'nilai_inflasi'">Nilai Inflasi</option>
                        </select>
                    </div>
                    <div class="w-1/2">
                        <label class="block mb-2 text-sm font-medium text-gray-900">Pengurutan</label>
                        <select name="direction" x-model="direction" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                            <option value="asc" :selected="direction === 'asc'">Naik</option>
                            <option value="desc" :selected="direction === 'desc'">Turun</option>
                        </select>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="mt-4">
                    <!-- Helper text for validation -->
                    <div x-show="!checkFormValidity()" class="my-2 text-sm text-red-600">
                        <span x-text="getValidationMessage()"></span>
                    </div>

                    <x-primary-button
                        type="submit"
                        x-bind:disabled="!checkFormValidity()"
                        class="w-full ">
                        Tampilkan
                    </x-primary-button>
                </div>
            </div>
        </form>
    </x-slot>

    <div x-show="!data.inflasi?.length" class="bg-white px-6 py-4 rounded-lg shadow-sm text-center text-gray-500">
        <div class="mb-1">
            <h2 class="text-lg font-semibold mb-2" x-text="data.title || 'Inflasi'"></h2>
        </div>
        <span x-text="message"></span>
    </div>

    <div x-show="data.inflasi?.length">
        <div class="mb-1">
            <h2 class="text-lg font-semibold mb-2" x-text="data.title || 'Inflasi'"></h2>
        </div>
        <div class="bg-white shadow-sm sm:rounded-lg">
            <div class="relative overflow-x-auto sm:rounded-lg md:max-h-[90vh] overflow-y-auto">
                <!-- Table for kd_level === '00' -->
                <table x-show="data.kd_level == '00'" class="w-full text-sm text-left rtl:text-right text-gray-500">
                    <colgroup>
                        <col span="2">
                    </colgroup>
                    <colgroup class="bg-gray-50">
                        <col span="2">
                    </colgroup>
                    <colgroup>
                        <col span="2">
                    </colgroup>
                    <colgroup class="bg-gray-50">
                        <col span="2">
                    </colgroup>
                    <colgroup>
                        <col span="2">
                    </colgroup>
                    <colgroup class="bg-gray-50">
                        <col span="2">
                    </colgroup>
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 sticky top-0 z-10 shadow-sm">
                        <tr>
                            <th scope="col" class="px-6 py-3">Kode Komoditas</th>
                            <th scope="col" class="px-6 py-3">Komoditas</th>
                            <th scope="col" class="px-6 py-3 bg-gray-50" colspan="2">Harga Produsen</th>
                            <th scope="col" class="px-6 py-3" colspan="2">Harga Produsen Desa</th>
                            <th scope="col" class="px-6 py-3 bg-gray-50" colspan="2">Harga Perdagangan Besar</th>
                            <th scope="col" class="px-6 py-3" colspan="2">Harga Konsumen Desa</th>
                            <th scope="col" class="px-6 py-3 bg-gray-50" colspan="2">Harga Konsumen Kota</th>
                        </tr>
                        <tr>
                            <th scope="col" class="px-6 py-3"></th>
                            <th scope="col" class="px-6 py-3"></th>
                            <th scope="col" class="px-6 py-3 bg-gray-50">Inflasi</th>
                            <th scope="col" class="px-6 py-3 bg-gray-50">Andil</th>
                            <th scope="col" class="px-6 py-3">Inflasi</th>
                            <th scope="col" class="px-6 py-3">Andil</th>
                            <th scope="col" class="px-6 py-3 bg-gray-50">Inflasi</th>
                            <th scope="col" class="px-6 py-3 bg-gray-50">Andil</th>
                            <th scope="col" class="px-6 py-3">Inflasi</th>
                            <th scope="col" class="px-6 py-3">Andil</th>
                            <th scope="col" class="px-6 py-3 bg-gray-50">Inflasi</th>
                            <th scope="col" class="px-6 py-3 bg-gray-50">Andil</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="item in data.inflasi" :key="item.kd_komoditas">
                            <tr class="bg-white border-b  border-gray-200 hover:bg-gray-50">
                                <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap" x-text="item.kd_komoditas"></th>
                                <td class="px-6 py-4" x-text="item.nama_komoditas"></td>
                                <td class="px-6 py-4 text-right bg-gray-50" x-text="item.inflasi_05 || '-'"></td>
                                <td class="px-6 py-4 text-right bg-gray-50" x-text="item.andil_05 || '-'"></td>
                                <td class="px-6 py-4 text-right" x-text="item.inflasi_04 || '-'"></td>
                                <td class="px-6 py-4 text-right" x-text="item.andil_04 || '-'"></td>
                                <td class="px-6 py-4 text-right bg-gray-50" x-text="item.inflasi_03 || '-'"></td>
                                <td class="px-6 py-4 text-right bg-gray-50" x-text="item.andil_03 || '-'"></td>
                                <td class="px-6 py-4 text-right" x-text="item.inflasi_02 || '-'"></td>
                                <td class="px-6 py-4 text-right" x-text="item.andil_02 || '-'"></td>
                                <td class="px-6 py-4 text-right bg-gray-50" x-text="item.inflasi_01 || '-'"></td>
                                <td class="px-6 py-4 text-right bg-gray-50" x-text="item.andil_01 || '-'"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                <!-- Table for kd_level !== '00' -->
                <table x-show="data.kd_level != '00'" class="w-full text-sm text-left rtl:text-right text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 sticky top-0 z-10 shadow-sm">
                        <tr>
                            <th scope="col" class="px-6 py-3">Kode Komoditas</th>
                            <th scope="col" class="px-6 py-3">Komoditas</th>
                            <th scope="col" class="px-6 py-3">Inflasi</th>
                            <th scope="col" class="px-6 py-3" x-show="data.kd_wilayah === '0'">Andil</th>
                            <th scope="col" class="px-6 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="item in data.inflasi" :key="item.kd_komoditas">
                            <tr class="bg-white border-b  border-gray-200 hover:bg-gray-50">
                                <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap" x-text="item.kd_komoditas"></th>
                                <td class="px-6 py-4" x-text="item.nama_komoditas"></td>
                                <td class="px-6 py-4 text-right" x-text="item.nilai_inflasi"></td>
                                <td class="px-6 py-4 text-right" x-show="data.kd_wilayah === '0'" x-text="item.andil"></td>
                                <td class="px-6 py-4 text-right">
                                    <button
                                        x-show="item.inflasi_id"
                                        @click="openEditModal(item.inflasi_id, item.nilai_inflasi, item.andil)"
                                        class="font-medium text-blue-600 hover:underline mr-3">
                                        Edit
                                    </button>
                                    <button
                                        x-show="item.inflasi_id"
                                        @click="openDeleteModal(item.inflasi_id, item.nama_komoditas)"
                                        class="font-medium text-red-600 hover:underline">
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-two-panel-layout>