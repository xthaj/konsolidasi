<x-two-panel-layout>
    @section('vite')
    @vite(['resources/css/app.css', 'resources/js/rekonsiliasi/pengisian.js'])
    @endsection

    <x-slot name="sidebar">
        <form id="filter-form" x-ref="filterForm" @submit.prevent="fetchData">
            <div class="space-y-4 md:space-y-6 mt-4">
                <!-- Bulan & Tahun -->
                <div class="flex gap-4">
                    <div class="w-1/2">
                        <label class="block mb-2 text-sm font-medium text-gray-900">Bulan</label>
                        <select name="bulan" x-model="bulan" required disabled class="bg-gray-200 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                            <template x-for="[nama, bln] in bulanOptions" :key="bln">
                                <option :value="bln" :selected="bulan == bln" x-text="nama"></option>
                            </template>
                        </select>
                    </div>
                    <div class="w-1/2">
                        <label class="block mb-2 text-sm font-medium text-gray-900">Tahun</label>
                        <select name="tahun" x-model="tahun" required disabled class="bg-gray-200 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                            <template x-for="year in tahunOptions" :key="year">
                                <option :value="year" :selected="year == tahun" x-text="year"></option>
                            </template>
                        </select>
                    </div>
                </div>
                <!-- <p x-show="isActivePeriod" class="text-sm text-gray-500">Periode aktif</p> -->

                <!-- Level Harga -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">Level Harga</label>
                    <select name="kd_level" x-model="selectedKdLevel" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
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
                <div x-show="wilayahLevel === 'kabkot' && selectedKdLevel !== '01' && selectedKdLevel !== '' && selectedKdLevel !== '00'" class="mt-4 text-sm text-gray-500">
                    Data tidak tersedia untuk kabupaten/kota pada level harga ini.
                </div>
                <input type="hidden" name="kd_wilayah" x-model="kd_wilayah" required>

                <!-- Komoditas -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">Komoditas</label>
                    <select name="kd_komoditas" x-model="selectedKomoditas" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="">Semua Komoditas</option>
                        <template x-for="komoditi in komoditas" :key="komoditi.kd_komoditas">
                            <option :value="komoditi.kd_komoditas" x-text="komoditi.nama_komoditas"></option>
                        </template>
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">Status</label>
                    <select name="status_rekon" x-model="status_rekon" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="00" :selected="status_rekon == '00'">Semua Status</option>
                        <option value="02" :selected="status_rekon == '02'">Sudah diisi</option>
                        <option value="01" :selected="status_rekon == '01'">Belum diisi</option>
                    </select>
                </div>

                <!-- Error Message -->
                <div x-show="errorMessage" class="my-2 text-sm text-red-600" x-text="errorMessage"></div>

                <!-- Submit Button -->
                <x-primary-button type="submit" x-bind:disabled="!checkFormValidity()" class="w-full">
                    <span x-show="!loadingButton">Filter</span>
                    <span x-show="loadingButton">Loading...</span>
                </x-primary-button>
            </div>
        </form>
    </x-slot>

    <!-- Rekon table -->
    <div x-show="!data.rekonsiliasi?.length" class="bg-white px-6 py-4 rounded-lg shadow-sm text-center text-gray-500">
        <div class="mb-1">
            <h2 class="text-lg font-semibold mb-2" x-text="data.title || 'Inflasi'"></h2>
        </div>
        <span x-text="message"></span>
    </div>

    <div x-show="data.rekonsiliasi?.length">
        <div class="mb-1">
            <h2 class="text-lg font-semibold mb-2" x-text="data.title || 'Rekonsiliasi'"></h2>
        </div>
        <div class="bg-white md:overflow-hidden shadow-sm sm:rounded-lg">
            <div class="relative overflow-x-auto shadow-md sm:rounded-lg md:max-h-[90vh] overflow-y-auto">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50   sticky top-0 z-10">
                        <tr>
                            <th scope="col" class="px-6 py-3">No</th>
                            <!-- <th scope="col" class="px-6 py-3">Kode Wilayah</th> -->
                            <th scope="col" class="px-6 py-3">Wilayah</th>
                            <!-- <th scope="col" class="px-6 py-3">Kode Komoditas</th> -->
                            <th scope="col" class="px-6 py-3">Komoditas</th>
                            <th scope="col" class="px-6 py-3">Inflasi (persen)</th>
                            <th scope="col" class="px-6 py-3 min-w-[175px]">Alasan</th>
                            <th scope="col" class="px-6 py-3">Detail</th>
                            <th scope="col" class="px-6 py-3">Sumber</th>
                            <th scope="col" class="px-6 py-3">Terakhir Diedit Oleh</th>
                            <th scope="col" class="px-6 py-3" x-show="isActivePeriod">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in data.rekonsiliasi" :key="item.rekonsiliasi_id">
                            <tr class="bg-white border-b  border-gray-200 hover:bg-gray-50 ">
                                <td class="px-6 py-4" x-text="index + 1"></td>
                                <!-- <td class="px-6 py-4" x-text="item.kd_wilayah"></td> -->
                                <td class="px-6 py-4" x-text="item.nama_wilayah ? item.nama_wilayah.toUpperCase() : 'Tidak Dikenal'"></td>
                                <!-- <td class="px-6 py-4" x-text="item.kd_komoditas"></td> -->
                                <td class="px-6 py-4" x-text="item.nama_komoditas || 'N/A'"></td>
                                <td class="px-6 py-4 text-right" x-text="item.nilai_inflasi || '-'"></td>
                                <td class="px-6 py-4">
                                    <ul x-show="item.alasan" class="list-disc list-inside">
                                        <template x-for="alasan in (item.alasan ? item.alasan.split(', ') : [])">
                                            <li x-text="alasan"></li>
                                        </template>
                                    </ul>
                                    <span x-show="!item.alasan">-</span>
                                </td>
                                <td class="px-6 py-4" x-data="{ showFull: false }">
                                    <span x-text="showFull || (item.detail || '-').length <= 50 ? (item.detail || '-') : (item.detail || '-').slice(0, 50) + '...'"></span>
                                    <template x-if="item.detail && item.detail !== '-' && item.detail.length > 50">
                                        <button
                                            @click="showFull = !showFull"
                                            class="text-blue-500 underline ml-2">
                                            <span x-text="showFull ? 'Sembunyikan' : 'Selengkapnya'"></span>
                                        </button>
                                    </template>
                                </td>

                                <td class="px-6 py-4">
                                    <a x-show="item.sumber" :href="item.sumber" class="text-blue-600 hover:underline" target="_blank"
                                        x-text="item.sumber ? (() => { try { return new URL(item.sumber).host } catch { return item.sumber } })() : ''"></a>
                                    <span x-show="!item.sumber">-</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span x-show="item.editor_name" x-text="item.editor_name"></span>
                                    <span x-show="!item.editor_name">-</span>
                                </td>
                                <td class="px-6 py-4 text-left" x-show="isActivePeriod">
                                    <button
                                        @click="openEditRekonModal(item.rekonsiliasi_id, item.nama_komoditas, item.kd_level, item.alasan || '', item.detail || '', item.sumber || '', item.nama_wilayah)"
                                        class="font-medium text-indigo-600  hover:underline">
                                        Edit
                                    </button>
                                    <button
                                        x-show="isPusat"
                                        @click="openDeleteModal(item.rekonsiliasi_id, item.nama_komoditas, item.nama_wilayah, item.kd_level)"
                                        class="font-medium text-red-600 hover:underline">
                                        Hapus
                                    </button>
                                </td>

                            </tr>
                        </template>
                        <tr x-show="!data.rekonsiliasi?.length && status === 'success'" class="bg-white ">
                            <td colspan="12" class="px-6 py-4 text-center">Tidak ada data untuk ditampilkan.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <x-modal name="edit-rekonsiliasi" focusable title="Edit Rekonsiliasi" x-cloak x-data="editRekonComponent()">
        <div class="px-6 py-4">
            <form class="space-y-4" @submit.prevent="submitEditRekon()">
                <div class="space-y-2 text-sm text-gray-700">
                    <div>
                        <span class="font-medium">Level Harga:</span>
                        <span class="font-semibold text-gray-900 " x-text="
                            modalData.kd_level === '01' ? 'Harga Konsumen Kota' :
                            modalData.kd_level === '02' ? 'Harga Konsumen Desa' :
                            modalData.kd_level === '03' ? 'Harga Perdagangan Besar' :
                            modalData.kd_level === '04' ? 'Harga Produsen Desa' :
                            'Harga Produsen'
                        "></span>
                    </div>
                    <div>
                        <span class="font-medium">Komoditas:</span>
                        <span class="font-semibold text-gray-900 " x-text="modalData.nama_komoditas"></span>
                    </div>
                    <div>
                        <span class="font-medium">Wilayah:</span>
                        <span class="font-semibold text-gray-900 " x-text="modalData.nama_wilayah"></span>
                    </div>
                    <div>
                        <span class="font-medium">Periode:</span>
                        <span class="font-semibold text-gray-900 " x-text="`${activeBulan}/${activeTahun}`"></span>
                    </div>
                </div>


                <div class="flex justify-between items-center mb-2 mt-6">
                    <label class="text-sm font-medium text-gray-900 ">Alasan<span class="text-red-500 ml-1">*</span></label>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-300 ">
                    <div class="p-3">
                        <div class="relative">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-500 " aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                                </svg>
                            </div>
                            <input type="text" id="input-group-search-alasan" @input="searchAlasan($event.target.value)" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full ps-10 p-2.5" placeholder="Cari alasan">
                        </div>
                    </div>
                    <!-- Existing checkbox list -->
                    <ul class="max-h-48 px-3 pb-3 overflow-y-auto text-sm text-gray-700">
                        <template x-for="alasan in filteredAlasan" :key="alasan.alasan_id">
                            <li>
                                <div class="flex items-center p-2 rounded-sm hover:bg-gray-100 ">
                                    <input
                                        type="checkbox"
                                        :id="'alasan-' + alasan.alasan_id"
                                        :value="alasan.keterangan"
                                        @change="selectedAlasan.includes(alasan.keterangan) ? selectedAlasan = selectedAlasan.filter(a => a !== alasan.keterangan) : selectedAlasan.push(alasan.keterangan)"
                                        :checked="selectedAlasan.includes(alasan.keterangan)"
                                        class="rounded border-gray-300">
                                    <label :for="'alasan-' + alasan.alasan_id" class="ms-2 text-sm font-medium text-gray-900" x-text="alasan.keterangan"></label>
                                </div>
                            </li>
                        </template>
                    </ul>
                </div>

                <div>
                    <label for="detail" class="block mb-2 text-sm font-medium text-gray-900 ">Detail<span class="text-red-500 ml-1">*</span></label>
                    <textarea
                        id="detail"
                        rows="6"
                        x-model="detail"
                        @input="detail.length > 500 ? detail = detail.slice(0, 500) : null"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5  "
                        placeholder="Kenaikan harga karena permintaan yang mulai meningkat menjelang akhir tahun. Sebelumnya ..."
                        required
                        maxlength="500"></textarea>
                    <div class="mt-2 text-sm flex justify-between">
                        <p x-text="detail.length > 500 ? 'Maksimum 500 karakter tercapai' : ''" class="text-red-500"></p>
                        <p x-text="`${detail.length}/500`" class="text-gray-500 "></p>
                    </div>
                </div>
                <div>
                    <label for="link_terkait" class="block mb-2 text-sm font-medium text-gray-900 ">Sumber (opsional)</label>
                    <input
                        type="text"
                        placeholder="https://www.kompas.com/artikel"
                        id="link_terkait"
                        x-model="linkTerkait"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5  " />
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <x-secondary-button x-on:click="$dispatch('close-modal', 'edit-rekonsiliasi')">Batal</x-secondary-button>
                    <x-primary-button type="submit">Edit Nilai</x-primary-button>
                </div>
            </form>
        </div>
    </x-modal>

    <x-modal name="delete-rekonsiliasi" focusable title="Konfirmasi Hapus Komoditas Rekonsiliasi" x-cloak>
        <div class="px-6 py-4">
            <p x-text="'Hapus rekonsiliasi berikut?'"></p>
            <span x-text="
                modalData.kd_level === '01' ? 'Harga Konsumen Kota' :
                modalData.kd_level === '02' ? 'Harga Konsumen Desa' :
                modalData.kd_level === '03' ? 'Harga Perdagangan Besar' :
                modalData.kd_level === '04' ? 'Harga Produsen Desa' : 'Harga Produsen'">
            </span>
            <div>
                <span>Komoditas: </span>
                <span x-text="modalData.nama_komoditas"></span>
            </div>
            <div>
                <span>Wilayah: </span>
                <span x-text="modalData.nama_wilayah"></span>
            </div>
            <div>
                <span>Periode: </span>
                <span x-text="`${activeBulan} ${activeTahun}`"></span>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close-modal', 'delete-rekonsiliasi')">Batal</x-secondary-button>
                <x-primary-button
                    @click="confirmDelete(modalData.rekonsiliasi_id)">
                    Hapus
                </x-primary-button>
            </div>
        </div>
    </x-modal>

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
</x-two-panel-layout>