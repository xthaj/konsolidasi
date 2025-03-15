<x-two-panel-layout>

@section('vite')
    @vite(['resources/css/app.css', 'resources/js/alpine-init.js', 'resources/js/pemilihan.js', 'resources/js/alpine-start.js'])
@endsection

<!-- Modals -->
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

<x-modal name="error-modal" focusable title="Kesalahan">
    <div class="px-6 py-4">
        <div x-show="!modalContent.success">
            <p class="text-red-600">Pilih minimal satu provinsi atau kabupaten</p>
        </div>
        <div class="mt-6 flex justify-end gap-3">
            <x-secondary-button x-on:click="$dispatch('close')">Mengerti</x-secondary-button>
        </div>
    </div>
</x-modal>

<!-- sidebar -->
<x-slot name="sidebar">
    <div class="space-y-4 md:space-y-6 mt-4">
        <!-- Bulan & Tahun -->
        <div class="flex gap-4">
            <!-- Bulan -->
            <div class="w-1/2">
                <label class="block mb-2 text-sm font-medium text-gray-900">Bulan<span class="text-red-500 ml-1">*</span></label>
                <select name="bulan" x-model="bulan" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                    @foreach(['Januari' => '01', 'Februari' => '02', 'Maret' => '03', 'April' => '04', 'Mei' => '05', 'Juni' => '06', 'Juli' => '07', 'Agustus' => '08', 'September' => '09', 'Oktober' => '10', 'November' => '11', 'Desember' => '12'] as $nama => $bulan)
                        <option value="{{ $bulan }}" @selected(request('bulan') == $bulan)>{{ $nama }}</option>
                    @endforeach
                </select>
            </div>
            <!-- Tahun -->
            <div class="w-1/2">
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Tahun</label>
                <select name="tahun" x-model="tahun" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                    @for ($year = 2020; $year <= 2025; $year++)
                        <option value="{{ $year }}" @selected(request('tahun') == $year)>{{ $year }}</option>
                    @endfor
                </select>
            </div>
        </div>

        <!-- Level Harga -->
        <div>
            <label class="block mb-2 text-sm font-medium text-gray-900">Level Harga<span class="text-red-500 ml-1">*</span></label>
            <select name="kd_level" x-model="selectedKdLevel" @change="updateKdWilayah()" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                <option value="01" @selected(request('kd_level') == '01')>Harga Konsumen Kota</option>
                <option value="02" @selected(request('kd_level') == '02')>Harga Konsumen Desa</option>
                <option value="03" @selected(request('kd_level') == '03')>Harga Perdagangan Besar</option>
                <option value="04" @selected(request('kd_level') == '04')>Harga Produsen Desa</option>
                <option value="05" @selected(request('kd_level') == '05')>Harga Produsen</option>
            </select>
        </div>

    <!-- Wilayah: Provinsi -->
    <div>
        <div class="flex justify-between items-center mb-2">
            <label class="text-sm font-medium text-gray-900 dark:text-white">Provinsi</label>
            <div class="flex items-center p-2 rounded-sm hover:bg-gray-100 dark:hover:bg-gray-600">
                <input type="checkbox" id="select-all-provinces" :checked="selectAllProvincesChecked" @click="selectAllProvinces($event.target.checked)" class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded-sm focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                <label for="select-all-provinces" class="w-full ms-2 text-sm font-medium text-gray-900 rounded-sm dark:text-gray-300">Pilih Semua</label>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm w-full border border-gray-300 dark:bg-gray-700 dark:border-gray-600">
            <div class="p-3">
                <label for="input-group-search-provinsi" class="sr-only">Search</label>
                <div class="relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                        </svg>
                    </div>
                    <input type="text" id="input-group-search-provinsi" @input="searchProvince($event.target.value)" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full ps-10 p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Search provinsi">
                </div>
            </div>
            <ul class="max-h-48 px-3 pb-3 overflow-y-auto text-sm text-gray-700 dark:text-gray-200">
                <template x-for="provinsi in filteredProvinces" :key="provinsi.kd_wilayah">
                    <li>
                        <div class="flex items-center p-2 rounded-sm hover:bg-gray-100 dark:hover:bg-gray-600">
                            <input type="checkbox" :id="'provinsi-' + provinsi.kd_wilayah" :checked="selectedProvinces.some(p => p.kd_wilayah === provinsi.kd_wilayah)" @click="toggleProvince(provinsi)" class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded-sm focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                            <label :for="'provinsi-' + provinsi.kd_wilayah" class="w-full ms-2 text-sm font-medium text-gray-900 rounded-sm dark:text-gray-300" x-text="provinsi.nama_wilayah"></label>
                        </div>
                    </li>
                </template>
            </ul>
        </div>
    </div>

    <!-- Wilayah: Kabupaten/Kota -->
    <div x-show="selectedKdLevel === '01'" class="mb-4">
        <div class="flex justify-between items-center mb-2">
            <label class="text-sm font-medium text-gray-900 dark:text-white">Kabupaten/Kota</label>
            <div class="flex items-center p-2 rounded-sm hover:bg-gray-100 dark:hover:bg-gray-600">
                <input type="checkbox" id="select-all-kabkots" :checked="selectAllKabkotsChecked" @click="selectAllKabkots($event.target.checked)" class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded-sm focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                <label for="select-all-kabkots" class="w-full ms-2 text-sm font-medium text-gray-900 rounded-sm dark:text-gray-300">Pilih Semua</label>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm w-full border border-gray-300 dark:bg-gray-700 dark:border-gray-600">
            <div class="p-3">
                <label for="input-group-search-kabkot" class="sr-only">Search</label>
                <div class="relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                        </svg>
                    </div>
                    <input type="text" id="input-group-search-kabkot" @input="searchKabkot($event.target.value)" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full ps-10 p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Search kabupaten/kota">
                </div>
            </div>
            <ul class="max-h-48 px-3 pb-3 overflow-y-auto text-sm text-gray-700 dark:text-gray-200">
                <template x-for="kabkot in filteredKabkots" :key="kabkot.kd_wilayah">
                    <li>
                        <div class="flex items-center p-2 rounded-sm hover:bg-gray-100 dark:hover:bg-gray-600">
                            <input type="checkbox" :id="'kabkot-' + kabkot.kd_wilayah" :checked="selectedKabkots.some(k => k.kd_wilayah === kabkot.kd_wilayah)" @click="toggleKabkot(kabkot)" class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded-sm focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                            <label :for="'kabkot-' + kabkot.kd_wilayah" class="w-full ms-2 text-sm font-medium text-gray-900 rounded-sm dark:text-gray-300" x-text="kabkot.nama_wilayah"></label>
                        </div>
                    </li>
                </template>
            </ul>
        </div>
    </div>

    <!-- Komoditas -->
        <div>
            <div class="flex justify-between items-center mb-2">
                <label class="text-sm font-medium text-gray-900 dark:text-white">Komoditas</label>
                <div class="flex items-center p-2 rounded-sm hover:bg-gray-100 dark:hover:bg-gray-600">
                    <input type="checkbox" id="select-all-komoditas" :checked="selectAllKomoditasChecked" @click="selectAllKomoditas($event.target.checked)" class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded-sm focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                    <label for="select-all-komoditas" class="w-full ms-2 text-sm font-medium text-gray-900 rounded-sm dark:text-gray-300">Pilih Semua</label>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm w-full border border-gray-300 dark:bg-gray-700 dark:border-gray-600">
                <div class="p-3">
                    <label for="input-group-search-komoditas" class="sr-only">Search</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                            </svg>
                        </div>
                        <input type="text" id="input-group-search-komoditas" @input="searchKomoditas($event.target.value)" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full ps-10 p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Search komoditas">
                    </div>
                </div>
                <ul class="max-h-48 px-3 pb-3 overflow-y-auto text-sm text-gray-700 dark:text-gray-200">
                    <template x-for="komoditas in filteredKomoditas" :key="komoditas.kd_komoditas">
                        <li>
                            <div class="flex items-center p-2 rounded-sm hover:bg-gray-100 dark:hover:bg-gray-600">
                                <input type="checkbox" :id="'komoditas-' + komoditas.kd_komoditas" :value="komoditas.kd_komoditas" x-model="selectedKomoditas" @change="updateSelectAllKomoditas()" class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded-sm focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                                <label :for="'komoditas-' + komoditas.kd_komoditas" class="w-full ms-2 text-sm font-medium text-gray-900 rounded-sm dark:text-gray-300" x-text="komoditas.nama_komoditas"></label>
                            </div>
                        </li>
                    </template>
                </ul>
            </div>
        </div>

        <button class="w-full bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:outline-none focus:ring-primary-300 text-white font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800" @click="addRow">Tambah</button>
        <form @submit.prevent="confirmRekonsiliasi">
            @csrf
            <template x-for="item in tableData" :key="item.inflasi_id">
                <div>
                    <input type="hidden" name="inflasi_ids[]" :value="item.inflasi_id">
                    <input type="hidden" name="bulan_tahun_ids[]" :value="item.bulan_tahun_id">
                </div>
            </template>
            <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:outline-none focus:ring-primary-300 text-white font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">Konfirmasi Rekonsiliasi</button>
        </form>
    </div>
</x-slot>

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="px-6 py-3">
                    Nomor
                </th>
                <th scope="col" class="px-6 py-3">
                    Kode Wilayah
                </th>
                <th scope="col" class="px-6 py-3">
                    Wilayah
                </th>
                <th scope="col" class="px-6 py-3">
                    Level Harga
                </th>
                <th scope="col" class="px-6 py-3">
                    Kode Komoditas
                </th>
                <th scope="col" class="px-6 py-3">
                    Komoditas
                </th>
                <th scope="col" class="px-6 py-3">
                    Inflasi/RH
                </th>
                <th scope="col" class="px-6 py-3">
                    <span class="sr-only">Hapus</span>
                </th>
            </tr>
            </thead>
            <tbody>
            <template x-for="(item, index) in tableData" :key="index">
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200">
                    <td class="px-6 py-4" x-text="index + 1"></td>
                    <td class="px-6 py-4" x-text="item.kd_wilayah"></td>
                    <td class="px-6 py-4" x-text="item.nama_wilayah"></td>
                    <td class="px-6 py-4" x-text="item.level_harga"></td>
                    <td class="px-6 py-4" x-text="item.kd_komoditas"></td>
                    <td class="px-6 py-4" x-text="item.nama_komoditas"></td>
                    <td class="px-6 py-4" x-text="item.harga"></td>
                    <td class="px-6 py-4 text-right">
                        <button @click="removeRow(index)" class="font-medium text-red-600 dark:text-red-500 hover:underline">Hapus</button>
                    </td>
                </tr>
            </template>
            </tbody>
        </table>
    </div>
</div>
</x-two-panel-layout>
