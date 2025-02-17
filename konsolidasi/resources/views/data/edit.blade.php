<x-two-panel-layout >

    <!-- Main modal -->
    <div
    id="authentication-modal"
    x-show="modalOpen"
    @click.away="closeModal()"
    class="fixed inset-0 z-50 flex justify-center items-center w-full h-full bg-black bg-opacity-50"
    >
    <div class="relative p-4 w-full max-w-md max-h-full">
        <!-- Modal content -->
        <div class="relative bg-white rounded-lg shadow-sm dark:bg-gray-700">
            <!-- Modal header -->
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600 border-gray-200">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Edit Harga
                </h3>
                <button
                    type="button"
                    @click="closeModal()"
                    class="end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>

            </div>
            <!-- Modal body -->
            <div class="p-4 md:p-5">
                <form class="space-y-4" action="#">
                <div>Wilayah: <span x-text="item.wilayah"></span></div>
                <div>Level Harga: <span x-text="item.levelHarga"></span></div>
                <div>Komoditas: <span x-text="item.komoditas"></span></div>
                <div>Periode: <span x-text="item.periode"></span></div>

                    <!-- Label untuk angka -->
                    <div>
                        <label for="harga" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Nilai inflasi baru</label>
                        <input type="text" name="harga" id="harga" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white" required />
                    </div>
                    <button type="submit" class="w-full text-white bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">Edit Nilai</button>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- </div> -->



    <x-slot name="sidebar">
        <div id="vizBuilderPanel" class="space-y-4 md:space-y-6 mt-4">
            <!-- Bulan & Tahun (Now in One Row) -->
            <div class="flex gap-4">
                <!-- Bulan -->
                <div class="w-1/2">
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Bulan</label>
                    <select class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @foreach(['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'] as $bulan)
                            <option>{{ $bulan }}</option>
                        @endforeach
                    </select>
                </div>
                <!-- Tahun -->
                <div class="w-1/2">
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Tahun</label>
                    <select class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @for ($year = 2020; $year <= 2025; $year++)
                            <option>{{ $year }}</option>
                        @endfor
                    </select>
                </div>
            </div>
            <!-- Periode (Disabled) -->
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Level Harga</label>
                <select class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option>Harga Konsumen Kota</option>
                    <option>Harga Konsumen Desa</option>
                    <option>Harga Perdagangan Besar</option>
                    <option>Harga Produsen Desa</option>
                    <option>Harga Produsen</option>
                </select>
            </div>
            <!-- Wilayah -->
            <div x-data="{ nasional: false }">
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Wilayah</label>
                <div class="flex items-start mb-6">
                    <div class="flex items-center h-5">
                        <input type="hidden" name="is_pusat" value="0">
                        <input type="checkbox" name="is_pusat" id="is_pusat" value="1" x-model="nasional" @click="togglePusat()" class="w-4 h-4 border border-gray-300 rounded-sm bg-gray-50 focus:ring-3 focus:ring-primary-300 dark:bg-gray-700 dark:border-gray-600 dark:focus:ring-primary-600 dark:ring-offset-gray-800" />
                    </div>
                    <label for="is_pusat" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Nasional</label>
                </div>
                <div :class="{ 'hidden': nasional }">
                    <div class="flex relative flex-col">
                        <div class="flex">
                            <button @click="toggleDropdown('province')" id="provinsi-button" class="shrink-0 z-10 inline-flex items-center py-2.5 px-4 text-sm font-medium text-center text-gray-500 bg-gray-100 border border-gray-300 rounded-s-lg hover:bg-gray-200 focus:ring-4 focus:outline-none focus:ring-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 dark:focus:ring-gray-700 dark:text-white dark:border-gray-600" type="button">
                                <span x-text="selectedProvince.nama_wilayah || 'Pilih Provinsi'"></span>
                            </button>
                            <div x-show="dropdowns.province" @click.away="closeDropdown('province')" class="z-10 bg-white divide-y divide-gray-100 rounded-lg shadow-sm w-44 dark:bg-gray-700 absolute mt-2 max-h-60 overflow-y-auto">
                                <ul class="py-2 text-sm text-gray-700 dark:text-gray-200 ">
                                    <template x-for="province in provinces" :key="province.kd_wilayah">
                                        <li>
                                            <button @click="selectProvince(province)" type="button" class="inline-flex text-left w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
                                                <span x-text="province.nama_wilayah"></span>
                                            </button>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                            <label for="kabkot" class="sr-only">Pilih Kabupaten</label>
                            <select id="kabkot" x-model="selectedKabkot" @change="updateKdWilayah()" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-e-lg border-s-gray-100 dark:border-s-gray-700 border-s-2 focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-400 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                <option value="" selected>Pilih Kabupaten</option>
                                <template x-for="kabkot in filteredKabkots" :key="kabkot.kd_wilayah">
                                    <option :value="kabkot.kd_wilayah" x-text="kabkot.nama_wilayah"></option>
                                </template>
                            </select>
                            <input type="hidden" name="kd_wilayah" x-model="kd_wilayah">
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Komoditas</label>
                <select name="komoditas_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">Pilih Komoditas</option>
                    @foreach($komoditas as $komoditi)
                        <option value="{{ $komoditi->id }}">{{ $komoditi->nama_komoditas }}</option>
                    @endforeach
                </select>
            </div>
            <button class="w-full bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:outline-none focus:ring-primary-300 text-white font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">Tampilkan</button>
        </div>
    </x-slot>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
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
                        <span class="sr-only">Edit</span>
                    </th>
                </tr>
                </thead>
                <tbody>
                @foreach($inflasi as $item)
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200">
                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        {{ $item->kd_komoditas }}
                    </th>
                    <td class="px-6 py-4">
                        {{ $item->komoditas->nama_komoditas }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        {{ number_format($item->harga, 2, '.', '') }}
                    </td>

                    <td class="px-6 py-4 text-right">
                    <button @click="openModal(
                        '{{ $item->id }}',
                        '{{ $item->komoditas->nama_komoditas }}',
                        '{{ $item->harga }}',
                        'Nasional',  <!-- Ganti sesuai data wilayah -->
                        'Harga Konsumen',  <!-- Ganti sesuai level harga -->
                        'Januari 2024'  <!-- Ganti sesuai periode -->
                    )" class="font-medium text-primary-600 dark:text-primary-500 hover:underline">
                        Edit
                    </button>
                    </td>


                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $inflasi->links() }}
    </div>


    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('webData', () => ({
                provinces: @json($wilayah->where('flag', 2)->values()), // Load all provinces
                kabkots: @json($wilayah->where('flag', 3)->values()), // Load all kab/kot
                selectedProvince: {},
                selectedKabkot: '',
                dropdowns: { province: false },

                nasional: false,
                kd_wilayah:'',

                komoditas: @json($komoditas), // Fetch from Laravel
                selectedKomoditas: '', // Default empty

                // Handle selection
                selectKomoditas(event) {
                    this.selectedKomoditas = event.target.value;
                },
                // Computed property: Filter kabupaten based on selected province
                get filteredKabkots() {
                    if (!this.selectedProvince.kd_wilayah) return [];
                    return this.kabkots.filter(k => k.parent_kd == this.selectedProvince.kd_wilayah);
                },

                // Select a province
                selectProvince(province) {
                    this.selectedProvince = province;
                    this.selectedKabkot = ''; // Reset kabkot
                    this.closeDropdown('province');
                    this.updateKdWilayah(); // Call updateKdWilayah here!
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
                    if (this.nasional) {
                        this.kd_wilayah = '1'; // Pusat
                    } else if (this.selectedKabkot) {
                        this.kd_wilayah = this.selectedKabkot; // Kabupaten/Kota
                    } else if (this.selectedProvince.kd_wilayah) {
                        this.kd_wilayah = this.selectedProvince.kd_wilayah; // Province
                    } else {
                        this.kd_wilayah = ''; // Default empty
                    }
                },

                // Watch changes in nasional
                togglePusat() {
                    this.updateKdWilayah(); // Call updateKdWilayah
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
