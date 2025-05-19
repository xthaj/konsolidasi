<x-two-panel-layout>
    @section('vite')
    @vite(['resources/css/app.css', 'resources/js/rekonsiliasi/pembahasan.js'])
    @endsection

    <x-slot name="sidebar">
        <form id="filter-form" x-ref="filterForm" @submit.prevent="fetchData">
            <div class="space-y-4 md:space-y-6 mt-4">
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
                <p x-show="isActivePeriod" class="text-sm text-gray-500">Periode aktif</p>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">Level Harga<span class="text-red-500 ml-1">*</span></label>
                    <select name="kd_level" x-model="pendingKdLevel" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="01" :selected="pendingKdLevel == '01'">Harga Konsumen Kota</option>
                        <option value="02" :selected="pendingKdLevel == '02'">Harga Konsumen Desa</option>
                        <option value="03" :selected="pendingKdLevel == '03'">Harga Perdagangan Besar</option>
                        <option value="04" :selected="pendingKdLevel == '04'">Harga Produsen Desa</option>
                        <option value="05" :selected="pendingKdLevel == '05'">Harga Produsen</option>
                    </select>
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">Level Wilayah<span class="text-red-500 ml-1">*</span></label>
                    <select name="level_wilayah" x-model="wilayahLevel" @change="updateWilayahOptions" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="semua">Semua Provinsi dan Kab/Kota</option>
                        <option value="semua-provinsi">Semua Provinsi</option>
                        <option value="semua-kabkot">Semua Kabupaten/Kota</option>
                        <option value="provinsi">Provinsi</option>
                        <option value="kabkot">Kabupaten/Kota</option>
                    </select>
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
                <div x-show="wilayahLevel === 'kabkot' && selectedKdLevel !== '01' && selectedKdLevel !== ''" class="mt-4 text-sm text-gray-500">
                    Data tidak tersedia untuk kabupaten/kota pada level harga ini.
                </div>
                <input type="hidden" name="kd_wilayah" x-model="kd_wilayah" required>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">Komoditas</label>
                    <select name="kd_komoditas" x-model="selectedKomoditas" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="">Semua Komoditas</option>
                        <template x-for="komoditi in komoditas" :key="komoditi.kd_komoditas">
                            <option :value="komoditi.kd_komoditas" x-text="komoditi.nama_komoditas" :selected="komoditi.kd_komoditas == selectedKomoditas"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">Status Rekonsiliasi<span class="text-red-500 ml-1">*</span></label>
                    <select name="status_rekon" x-model="status_rekon" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="00" :selected="status_rekon == '00'">Semua Status</option>
                        <option value="01" :selected="status_rekon == '01'">Belum diisi</option>
                        <option value="02" :selected="status_rekon == '02'">Sudah diisi</option>
                    </select>
                </div>
                <div x-show="errorMessage" class="my-2 text-sm text-red-600" x-text="errorMessage"></div>
                <x-primary-button type="submit" x-bind:disabled="!checkFormValidity()" class="w-full">
                    <span x-show="!loading">Filter</span>
                    <span x-show="loading">Loading...</span>
                </x-primary-button>
            </div>
        </form>
    </x-slot>

    <x-modal name="success-modal" title="Berhasil" maxWidth="md">
        <div class="text-gray-900 dark:text-white">
            <p x-text="modalMessage"></p>
            <div class="mt-4 flex justify-end">
                <x-primary-button type="button" x-on:click="$dispatch('close')">Tutup</x-primary-button>
            </div>
        </div>
    </x-modal>

    <x-modal name="error-modal" title="Kesalahan" maxWidth="md">
        <div class="text-gray-900 dark:text-white">
            <p x-text="modalMessage"></p>
            <div class="mt-4 flex justify-end">
                <x-primary-button type="button" x-on:click="$dispatch('close')">Tutup</x-primary-button>
            </div>
        </div>
    </x-modal>

    <div x-show="status === 'no_filters'" class="bg-white px-6 py-4 rounded-lg shadow-sm text-center text-gray-500">
        <div class="mb-1">
            <h2 class="text-lg font-semibold mb-2" x-text="data.title || 'Pembahasan Rekonsiliasi'"></h2>
        </div>
        <span x-text="message"></span>
    </div>
    <div x-show="status === 'no_data' && !data.rekonsiliasi?.length" class="bg-white px-6 py-4 rounded-lg shadow-sm text-center text-gray-500">
        <div class="mb-1">
            <h2 class="text-lg font-semibold mb-2" x-text="data.title || 'Pembahasan Rekonsiliasi'"></h2>
        </div>
        <span x-text="message"></span>
    </div>
    <div x-show="status === 'access_not_allowed'" class="bg-white px-6 py-4 rounded-lg shadow-sm text-center text-gray-500">
        <div class="mb-1">
            <h2 class="text-lg font-semibold mb-2" x-text="data.title || 'Pembahasan Rekonsiliasi'"></h2>
        </div>
        <span x-text="message"></span>
    </div>
    <div x-show="data.rekonsiliasi?.length || status === 'success'">
        <div class="mb-1">
            <h2 class="text-lg font-semibold mb-2" x-text="data.title || 'Pembahasan Rekonsiliasi'"></h2>
        </div>
        <div class="bg-white md:overflow-hidden shadow-sm sm:rounded-lg">
            <div class="relative overflow-x-auto shadow-md sm:rounded-lg md:max-h-[90vh] overflow-y-auto">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 sticky top-0 z-10">
                        <tr>
                            <th scope="col" class="px-6 py-3">No</th>
                            <th scope="col" class="px-6 py-3">Wilayah</th>
                            <th scope="col" class="px-6 py-3">Komoditas</th>
                            <th scope="col" class="px-6 py-3" x-text="selectedKdLevel === '01' || selectedKdLevel === '02' ? 'Inflasi Kota' : 'Inflasi'"></th>
                            <th scope="col" class="px-6 py-3" x-show="selectedKdLevel === '01' || selectedKdLevel === '02'">Inflasi Desa</th>
                            <th scope="col" class="px-6 py-3 min-w-[175px]">Alasan</th>
                            <th scope="col" class="px-6 py-3">Detail</th>
                            <th scope="col" class="px-6 py-3">Sumber</th>
                            <th scope="col" class="px-6 py-3">Pembahasan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in data.rekonsiliasi" :key="item.rekonsiliasi_id">
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <td class="px-6 py-4" x-text="index + 1"></td>
                                <td class="px-6 py-4" x-text="item.nama_wilayah || 'Tidak Dikenal'"></td>
                                <td class="px-6 py-4" x-text="item.nama_komoditas || 'N/A'"></td>
                                <td class="px-6 py-4 text-right" :class="item.inflasi_kota === null ? 'text-red-500' : ''" x-text="item.inflasi_kota !== null ? item.inflasi_kota : '-'"></td>
                                <td class="px-6 py-4 text-right" x-show="selectedKdLevel === '01' || selectedKdLevel === '02'" :class="item.inflasi_desa === null && selectedKdLevel === '01' ? 'text-red-500' : ''" x-text="item.inflasi_desa !== null ? item.inflasi_desa : '-'"></td>
                                <td class="px-6 py-4">
                                    <ul x-show="item.alasan" class="list-disc list-inside">
                                        <template x-for="alasan in (item.alasan ? item.alasan.split(', ') : [])">
                                            <li x-text="alasan"></li>
                                        </template>
                                    </ul>
                                    <span x-show="!item.alasan">-</span>
                                </td>
                                <td class="px-6 py-4" x-data="{ showFull: false }">
                                    <span x-text="showFull || (item.detail || '').length <= 50 ? (item.detail || '-') : (item.detail || '').slice(0, 50) + '...'"></span>
                                    <template x-if="item.detail && item.detail !== '-' && item.detail.length > 50">
                                        <button @click="showFull = !showFull" class="text-blue-500 underline ml-2">
                                            <span x-text="showFull ? 'Sembunyikan' : 'Selengkapnya'"></span>
                                        </button>
                                    </template>
                                </td>
                                <td class="px-6 py-4">
                                    <a x-show="item.sumber" :href="item.sumber" class="text-blue-600 hover:underline" target="_blank" x-text="item.sumber ? (() => { try { return new URL(item.sumber).host } catch { return item.sumber } })() : ''"></a>
                                    <span x-show="!item.sumber">-</span>
                                </td>
                                <td class="px-6 py-4">
                                    <input type="checkbox" class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500" :checked="!!item.pembahasan" @change="togglePembahasan(item.rekonsiliasi_id, $event.target.checked)">
                                </td>
                            </tr>
                        </template>
                        <tr x-show="!data.rekonsiliasi?.length && status === 'success'" class="bg-white dark:bg-gray-800">
                            <td colspan="9" class="px-6 py-4 text-center">Tidak ada data untuk ditampilkan.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-two-panel-layout>