<x-app-layout>
<div x-data="webData">
    <!-- Main modal -->
    <div id="authentication-modal" x-show="modalOpen" @click.away="closeModal()" class="fixed inset-0 z-50 flex justify-center items-center w-full h-full bg-black bg-opacity-50">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <!-- Modal content -->
            <div class="relative bg-white rounded-lg shadow-sm dark:bg-gray-700">
                <!-- Modal header -->
                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600 border-gray-200">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Input Rekonsiliasi</h3>
                    <button type="button" @click="closeModal()" class="end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                        </svg>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <!-- Modal body -->
                <div class="p-4 md:p-5 max-h-[calc(100vh-10rem)] overflow-y-auto">
                    <form class="space-y-4" action="#">
                        <span>Level Harga: Harga Konsumen Kota </span><br>
                        <span>Komoditas: Beras</span><br>
                        <span>Periode: Februari 2024</span><br>

                        <!-- Dropdown menu -->
                        <button id="dropdownCheckboxButton" data-dropdown-toggle="dropdownDefaultCheckbox" class="w-full text-white bg-primary-900 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button">
                            Alasan
                            <svg class="ml-auto w-2.5 h-2.5 ms-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                            </svg>
                        </button>

                        <!-- Dropdown menu -->
                        <div id="dropdownDefaultCheckbox" class="z-10 hidden w-80 bg-white divide-y divide-gray-100 rounded-lg shadow-sm dark:bg-gray-700 dark:divide-gray-600">
                            <ul class="h-48 px-3 pb-3 overflow-y-auto text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownCheckboxButton">
                                <template x-for="(alasan, index) in alasanList" :key="index">
                                    <li>
                                        <div class="flex items-center p-2 rounded-sm hover:bg-gray-100 dark:hover:bg-gray-600">
                                            <input type="checkbox" :id="'alasan-' + index" :value="alasan" x-model="selectedAlasan" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded-sm focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                                            <label :for="'alasan-' + index" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300" x-text="alasan"></label>
                                        </div>
                                    </li>
                                </template>
                            </ul>
                        </div>

                        <!-- alasan -->
                        <div>
                            <label for="alasan" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Detail</label>
                            <textarea id="alasan" rows="6" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Kenaikan harga karena permintaan yang mulai meningkat menjelang akhir tahun. Sebelumnya ..." required></textarea>
                            <p id="helper-text-explanation" class="mt-2 text-sm text-gray-500 dark:text-gray-400">Maksimal 500 karakter</p>
                        </div>

                        <!-- link terkait -->
                        <div>
                            <label for="link_terkait" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Link media</label>
                            <input type="text" id="link_terkait" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                        </div>

                        <button type="submit" class="w-full text-white bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">Edit Nilai</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="p-4">
        <div class="bg-white w-full md:w-1/2 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="flex flex-col md:flex-row gap-4 p-4">
                <!-- Bulan -->
                <div class="w-full md:w-1/3">
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Bulan</label>
                    <select class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @foreach(['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'] as $bulan)
                            <option>{{ $bulan }}</option>
                        @endforeach
                    </select>
                </div>
                <!-- Tahun -->
                <div class="w-full md:w-1/3">
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Tahun</label>
                    <select class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @for ($year = 2020; $year <= 2025; $year++)
                            <option>{{ $year }}</option>
                        @endfor
                    </select>
                </div>
                <!-- submit -->
                <div class="w-full md:w-1/3 flex flex-col justify-end">
                    <button class="w-full text-white bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">Submit</button>
                </div>
            </div>
        </div>
    </div>

    <div class="p-4">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                <table class="table-auto w-full  text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">No</th>
                            <th scope="col" class="px-6 py-3">Kode Wilayah</th>
                            <th scope="col" class="px-6 py-3">Wilayah</th>
                            <th scope="col" class="px-6 py-3">Kode Komoditas</th>
                            <th scope="col" class="px-6 py-3">Komoditas</th>
                            <th scope="col" class="px-6 py-3">Level Harga</th>
                            <th scope="col" class="px-6 py-3">Inflasi/RH Kabupaten</th>
                            <th scope="col" class="px-6 py-3">Inflasi/RH Desa</th>
                            <th scope="col" class="px-6 w-1/4 py-3">Alasan</th>
                            <th scope="col" class="px-6 py-3">Detail</th>
                            <th scope="col" class="px-6 py-3">Media</th>
                            <th scope="col" class="px-6 py-3"><span class="sr-only">Edit</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200">
                            <td class="px-6 py-4">1</td>
                            <td class="px-6 py-4">1277</td>
                            <td class="px-6 py-4">KOTA PADANGSIDIMPUAN</td>
                            <td class="px-6 py-4">006</td>
                            <td class="px-6 py-4">Beras Premium</td>
                            <td class="px-6 py-4">Harga Konsumen Kota</td>
                            <td class="px-6 py-4">3.1%</td>
                            <td class="px-6 py-4">2.8%</td>
                            <td class="px-6 py-4">
                                <span id="badge-dismiss-default" class="inline-flex items-center px-2 py-1 me-2 my-1 text-sm font-medium text-blue-800 bg-blue-100 rounded-sm dark:bg-blue-900 dark:text-blue-300">stok melimpah</span>
                                <span id="badge-dismiss-default" class="inline-flex items-center px-2 py-1 me-2 my-1 text-sm font-medium text-blue-800 bg-blue-100 rounded-sm dark:bg-blue-900 dark:text-blue-300">persaingan harga</span>
                            </td>
                            <td class="px-6 py-4">
                                Ada promo pada tarif angkutan udara maskapai garuda Indonesia
                                <!-- , selain itu pada tarif angkutan udara maskapai Super Jet juga ada promo di minggu ke 3 Okt hingga minggu pertama Nov, ada persaingan harga dengan Citilink yg mulai ada rute penerbangan dari Banda Aceh ke Medan. -->
                            </td>

                            <!-- <td class="px-6 py-4"><a "https://sport.detik.com/sepakbola/liga-indonesia/d-7781510/timnas-indonesia-u-20-dihukum-uzbekistan-kalah-tersingkir"></a></td> -->
                            <td class="px-6 py-4">
                                <a href="https://sport.detik.com/sepakbola/liga-indonesia/d-7781510/timnas-indonesia-u-20-dihukum-uzbekistan-kalah-tersingkir" class="text-blue-600 hover:underline">
                                    detik.com
                                </a>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button @click="openModal()" class="font-medium text-primary-600 dark:text-primary-500 hover:underline">Edit</button>
                            </td>
                        </tr>
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200">
                            <td class="px-6 py-4">2</td>
                            <td class="px-6 py-4">1371</td>
                            <td class="px-6 py-4">KOTA PADANG</td>
                            <td class="px-6 py-4">010</td>
                            <td class="px-6 py-4">Jagung</td>
                            <td class="px-6 py-4">Harga Perdagangan Besar</td>
                            <td class="px-6 py-4">4.2%</td>
                            <td class="px-6 py-4">3.9%</td>
                            <td class="px-6 py-4">Permintaan industri meningkat</td>
                            <td class="px-6 py-4">Harga naik karena stok berkurang</td>
                            <td class="px-6 py-4">https://sport.detik.com/sepakbola/liga-indonesia/d-7781510/timnas-indonesia-u-20-dihukum-uzbekistan-kalah-tersingkir</td>
                            <td class="px-6 py-4 text-right">
                                <a href="#" class="font-medium text-blue-600 dark:text-blue-500 hover:underline">Edit</a>
                            </td>
                        </tr>
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200">
                            <td class="px-6 py-4">3</td>
                            <td class="px-6 py-4">1312</td>
                            <td class="px-6 py-4">KAB PASAMAN BARAT</td>
                            <td class="px-6 py-4">011</td>
                            <td class="px-6 py-4">Daging Ayam Ras</td>
                            <td class="px-6 py-4">Harga Produsen Desa</td>
                            <td class="px-6 py-4">2.5%</td>
                            <td class="px-6 py-4">2.1%</td>
                            <td class="px-6 py-4">Pakan ayam naik</td>
                            <td class="px-6 py-4">Biaya produksi meningkat</td>
                            <td class="px-6 py-4">https://sport.detik.com/sepakbola/liga-indonesia/d-7781510/timnas-indonesia-u-20-dihukum-uzbekistan-kalah-tersingkir</td>
                            <td class="px-6 py-4 text-right">
                                <a href="#" class="font-medium text-blue-600 dark:text-blue-500 hover:underline">Edit</a>
                            </td>
                        </tr>
                    </tbody>
                </table>

            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('webData', () => ({
            alasanList: [
                "Kondisi Alam", "Masa Panen", "Gagal Panen", "Promo dan Diskon",
                "Harga Stok Melimpah", "Stok Menipis/Langka", "Harga Kembali Normal",
                "Turun Harga dari Distributor", "Kenaikan Harga dari Distributor",
                "Perbedaan Kualitas", "Supplier Menaikkan Harga", "Supplier Menurunkan Harga",
                "Persaingan Harga", "Permintaan Meningkat", "Permintaan Menurun",
                "Operasi Pasar", "Kebijakan Pemerintah Pusat", "Kebijakan Pemerintah Daerah",
                "Kesalahan Petugas Mencacah", "Penurunan Produksi", "Kenaikan Produksi",
                "Salah Entri Data", "Penggantian Responden", "Lainnya"
            ],
            selectedAlasan: [],

            // Dropdown handlers
            toggleDropdown(menu) {
                this.dropdowns[menu] = !this.dropdowns[menu];
            },
            closeDropdown(menu) {
                this.dropdowns[menu] = false;
            },

            modalOpen: false,
            item: { id: null, komoditas: 'Example Komoditas', harga: '1000' },

            openModal() {
                this.modalOpen = true;
            },

            closeModal() {
                this.modalOpen = false;
                this.item = { id: null, komoditas: '', harga: '', wilayah: '', levelHarga: '', periode: '' };
            },
        }));
    });
</script>
</x-app-layout>
