<x-two-panel-layout>
    <x-slot name="sidebar">
        <div id="vizBuilderPanel" class="space-y-4 md:space-y-6 mt-4">
            <!-- Bulan & Tahun -->
            <div class="flex gap-4">
                <!-- Bulan -->
                <div class="w-1/2">
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Bulan</label>
                    <select name="bulan" x-model="bulan" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @foreach(['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'] as $bulan)
                            <option>{{ $bulan }}</option>
                        @endforeach
                    </select>
                </div>
                <!-- Tahun -->
                <div class="w-1/2">
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Tahun</label>
                    <select name="tahun" x-model="tahun" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @for ($year = 2020; $year <= 2025; $year++)
                            <option>{{ $year }}</option>
                        @endfor
                    </select>
                </div>
            </div>

            <!-- Level Harga -->
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Level Harga</label>
                <button id="dropdownLevelHargaButton" data-dropdown-toggle="dropdownLevelHarga" class="w-full inline-flex items-center justify-between px-4 py-2 text-sm font-medium text-center text-white bg-primary-700 rounded-lg hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800" type="button">
                    Pilih Level Harga
                    <svg class="w-2.5 h-2.5 ml-auto" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                    </svg>
                </button>

                <!-- Dropdown menu -->
                <div id="dropdownLevelHarga" class="z-10 hidden bg-white rounded-lg shadow-sm w-60 dark:bg-gray-700">
                    <ul class="h-48 px-3 pb-3 overflow-y-auto text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownLevelHargaButton">
                        <li>
                            <div class="flex items-center p-2 rounded-sm hover:bg-gray-100 dark:hover:bg-gray-600">
                                <input id="level-harga-konsumen-kota" type="checkbox" value="Harga Konsumen Kota" x-model="levelHarga" class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded-sm focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                                <label for="level-harga-konsumen-kota" class="w-full ms-2 text-sm font-medium text-gray-900 rounded-sm dark:text-gray-300">Harga Konsumen Kota</label>
                            </div>
                        </li>
                        <li>
                            <div class="flex items-center p-2 rounded-sm hover:bg-gray-100 dark:hover:bg-gray-600">
                                <input id="level-harga-konsumen-desa" type="checkbox" value="Harga Konsumen Desa" x-model="levelHarga" class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded-sm focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                                <label for="level-harga-konsumen-desa" class="w-full ms-2 text-sm font-medium text-gray-900 rounded-sm dark:text-gray-300">Harga Konsumen Desa</label>
                            </div>
                        </li>
                        <li>
                            <div class="flex items-center p-2 rounded-sm hover:bg-gray-100 dark:hover:bg-gray-600">
                                <input id="level-harga-perdagangan-besar" type="checkbox" value="Harga Perdagangan Besar" x-model="levelHarga" class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded-sm focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                                <label for="level-harga-perdagangan-besar" class="w-full ms-2 text-sm font-medium text-gray-900 rounded-sm dark:text-gray-300">Harga Perdagangan Besar</label>
                            </div>
                        </li>
                        <li>
                            <div class="flex items-center p-2 rounded-sm hover:bg-gray-100 dark:hover:bg-gray-600">
                                <input id="level-harga-produsen-desa" type="checkbox" value="Harga Produsen Desa" x-model="levelHarga" class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded-sm focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                                <label for="level-harga-produsen-desa" class="w-full ms-2 text-sm font-medium text-gray-900 rounded-sm dark:text-gray-300">Harga Produsen Desa</label>
                            </div>
                        </li>
                        <li>
                            <div class="flex items-center p-2 rounded-sm hover:bg-gray-100 dark:hover:bg-gray-600">
                                <input id="level-harga-produsen" type="checkbox" value="Harga Produsen" x-model="levelHarga" class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded-sm focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                                <label for="level-harga-produsen" class="w-full ms-2 text-sm font-medium text-gray-900 rounded-sm dark:text-gray-300">Harga Produsen</label>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Wilayah -->
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Wilayah</label>
                <!-- Provinsi -->
                <button id="dropdownProvinsiButton" data-dropdown-toggle="dropdownProvinsi" class="w-full inline-flex items-center px-4 py-2 text-sm font-medium text-center text-white bg-primary-700 rounded-lg hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800" type="button">
                    Pilih Provinsi
                    <svg class="w-2.5 h-2.5 ms-2.5 ml-auto" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                    </svg>
                </button>

                <!-- Dropdown menu Provinsi -->
                <div id="dropdownProvinsi" class="z-10 hidden bg-white rounded-lg shadow-sm w-60 dark:bg-gray-700">
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
                    <ul class="h-48 px-3 pb-3 overflow-y-auto text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownProvinsiButton">
                        <template x-for="provinsi in filteredProvinces" :key="provinsi.kd_wilayah">
                            <li>
                                <div class="flex items-center p-2 rounded-sm hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <input type="checkbox" :id="'provinsi-' + provinsi.kd_wilayah" :value="provinsi.kd_wilayah" @click="toggleProvince(provinsi)" class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded-sm focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                                    <label :for="'provinsi-' + provinsi.kd_wilayah" class="w-full ms-2 text-sm font-medium text-gray-900 rounded-sm dark:text-gray-300" x-text="provinsi.nama_wilayah"></label>
                                </div>
                            </li>
                        </template>
                    </ul>
                </div>

                <!-- Kabupaten/Kota -->
                <button id="dropdownKabkotButton" data-dropdown-toggle="dropdownKabkot" class="w-full inline-flex items-center px-4 py-2 text-sm font-medium text-center text-white bg-primary-700 rounded-lg hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 mt-2" type="button">
                    Pilih Kabupaten/Kota
                    <svg class="w-2.5 h-2.5 ms-2.5 ml-auto" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                    </svg>
                </button>

                <!-- Dropdown menu Kabupaten/Kota -->
                <div id="dropdownKabkot" class="z-10 hidden bg-white rounded-lg shadow-sm w-60 dark:bg-gray-700">
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
                    <ul class="h-48 px-3 pb-3 overflow-y-auto text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownKabkotButton">
                        <template x-for="kabkot in filteredKabkots" :key="kabkot.kd_wilayah">
                            <li>
                                <div class="flex items-center p-2 rounded-sm hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <input type="checkbox" :id="'kabkot-' + kabkot.kd_wilayah" :value="kabkot.kd_wilayah" @click="toggleKabkot(kabkot)" class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded-sm focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                                    <label :for="'kabkot-' + kabkot.kd_wilayah" class="w-full ms-2 text-sm font-medium text-gray-900 rounded-sm dark:text-gray-300" x-text="kabkot.nama_wilayah"></label>
                                </div>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>

            <div>
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Komoditas</label>
                <button id="dropdownKomoditasButton" @click="toggleDropdown('komoditas')" class="w-full inline-flex items-center justify-between px-4 py-2 text-sm font-medium text-center text-white bg-primary-700 rounded-lg hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800" type="button">
                    Pilih Komoditas
                    <svg class="w-2.5 h-2.5 ml-auto" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                    </svg>
                </button>

                <!-- Dropdown menu Komoditas -->
                <div id="dropdownKomoditas" x-show="dropdowns.komoditas" @click.away="closeDropdown('komoditas')" class="z-10 bg-white rounded-lg shadow-sm w-60 dark:bg-gray-700">
                    <!-- <input type="text" placeholder="Cari Komoditas..." class="w-full px-3 py-2 text-sm border-b focus:outline-none" @input="searchKomoditas($event.target.value)"> -->

                    <div class="p-3">
                        <label for="input-group-search-kabkot" class="sr-only">Search</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                </svg>
                            </div>
                            <input type="text" id="input-group-search-kabkot" @input="searchKomoditas($event.target.value)" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full ps-10 p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Search komoditas">
                        </div>
                    </div>

                    <ul class="h-48 px-3 pb-3 overflow-y-auto text-sm text-gray-700 dark:text-gray-200">
                        <template x-for="komoditas in filteredKomoditas" :key="komoditas.kd_komoditas">
                            <li>
                                <div class="flex items-center p-2 rounded-sm hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <input type="checkbox" :id="'komoditas-' + komoditas.kd_komoditas" :value="komoditas.kd_komoditas" x-model="selectedKomoditas" class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded-sm focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                                    <label :for="'komoditas-' + komoditas.kd_komoditas" class="w-full ml-2 text-sm font-medium text-gray-900 rounded-sm dark:text-gray-300" x-text="komoditas.nama_komoditas"></label>
                                </div>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>

            <button class="w-full bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:outline-none focus:ring-primary-300 text-white font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800" @click="addRow">Tambah</button>
            <button class="w-full bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:outline-none focus:ring-primary-300 text-white font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">Konfirmasi Rekonsiliasi</button>

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
                        Harga
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Bulan
                    </th>
                     <th scope="col" class="px-6 py-3">
                        Tahun
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
                        <td class="px-6 py-4" x-text="item.bulan"></td>
                        <td class="px-6 py-4" x-text="item.tahun"></td>
                        <td class="px-6 py-4 text-right">
                            <button @click="removeRow(index)" class="font-medium text-red-600 dark:text-red-500 hover:underline">Hapus</button>
                        </td>
                    </tr>
                </template>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('webData', () => ({
                provinces: @json($wilayah->where('flag', 2)->values()), // Load all provinces
                kabkots: @json($wilayah->where('flag', 3)->values()), // Load all kab/kot
                komoditas: @json($komoditas),
                selectedProvinces: [],
                selectedKabkots: [],
                selectedKomoditas: [],
                dropdowns: { province: false },
                kd_wilayah:'',
                bulan: '',
                tahun: '',
                levelHarga: [],
                harga: '',
                tableData: [],
                filteredProvinces: @json($wilayah->where('flag', 2)->values()),
                filteredKabkots: @json($wilayah->where('flag', 3)->values()),
                filteredKomoditas: [],

                init() {
                    this.bulan = document.querySelector('select[name="bulan"]').value;
                    this.tahun = document.querySelector('select[name="tahun"]').value;
                    this.filteredKomoditas = [...this.komoditas]; // Initialize filteredKomoditas
                },

                addRow() {
                    console.log("Adding Row:");
                    console.log(this.kd_wilayah, this.selectedKabkots, this.selectedProvinces, this.komoditas); // Log the variables to see if they have values
                    this.tableData.push({
                        kd_wilayah: "Text",
                        nama_wilayah: "Text",
                        level_harga: "Text",
                        kd_komoditas: "Text",
                        nama_komoditas: "Text",
                        harga: "Text",
                        bulan: "Text",
                        tahun: "Text",
                    });
                },



                removeRow(index) {
                    this.tableData.splice(index, 1);
                },

                toggleProvince(province) {
                    const index = this.selectedProvinces.findIndex(p => p.kd_wilayah === province.kd_wilayah);
                    if (index === -1) {
                        this.selectedProvinces.push(province);
                    } else {
                        this.selectedProvinces.splice(index, 1);
                    }
                },

                toggleKabkot(kabkot) {
                    const index = this.selectedKabkots.findIndex(k => k.kd_wilayah === kabkot.kd_wilayah);
                    if (index === -1) {
                        this.selectedKabkots.push(kabkot);
                    } else {
                        this.selectedKabkots.splice(index, 1);
                    }
                },

                selectKomoditas(komoditas) {
                    this.selectedKomoditas = komoditas;
                },

                searchProvince(query) {
                    query = query.toLowerCase();
                    this.filteredProvinces = this.provinces.filter(province => {
                        return province.nama_wilayah.toLowerCase().includes(query);
                    });
                },

                searchKabkot(query) {
                    query = query.toLowerCase();
                    this.filteredKabkots = this.kabkots.filter(kabkot => {
                        return kabkot.nama_wilayah.toLowerCase().includes(query);
                    });
                },

                 searchKomoditas(query) {
                    query = query.toLowerCase();
                    this.filteredKomoditas = this.komoditas.filter(komoditas => {
                        return komoditas.nama_komoditas.toLowerCase().includes(query);
                    });
                },

                // Dropdown handlers
                toggleDropdown(menu) {
                    this.dropdowns[menu] = !this.dropdowns[menu];
                },
                closeDropdown(menu) {
                    this.dropdowns[menu] = false;
                },
                // Update kd_wilayah when kabkot is selected
                updateKdWilayah() {
                     if (this.selectedKabkot) {
                        this.kd_wilayah = this.selectedKabkot; // Kabupaten/Kota
                    } else if (this.selectedProvince.kd_wilayah) {
                        this.kd_wilayah = this.selectedProvince.kd_wilayah; // Province
                    } else {
                        this.kd_wilayah = ''; // Default empty
                    }
                },

                modalOpen: false,
                item: { id: null, komoditas: 'Example Komoditas', harga: '1000' },

                openModal(id, komoditas, harga, wilayah, levelHarga, periode) {
                    this.item = { id, komoditas, harga, wilayah, levelHarga, periode };
                    this.modalOpen = true;
                },

                closeModal() {
                    this.modalOpen = false;
                    this.item = { id: null, komoditas: '', harga: '', wilayah: '', levelHarga: '', periode: '' };
                },

            }));
        });
    </script>
</x-two-panel-layout>


