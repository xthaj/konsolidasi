<x-app-layout>


    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                <th scope="col" class="px-6 py-3">
                        No
                    </th>
                <th scope="col" class="px-6 py-3">
                        Kode Wilayah
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Wilayah
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Kode Komoditas
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Komoditas
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Level Harga
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Harga
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Alasan
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Detail
                    </th>
                    <th scope="col" class="px-6 py-3">
                        <span class="sr-only">Edit</span>
                    </th>
                </tr>
                </thead>
                <tbody>

                <!-- for alasan do chips  -->
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Level Harga</label>
                <button id="dropdownLevelHargaButton" data-dropdown-toggle="dropdownLevelHarga" class="w-full inline-flex items-center justify-between px-4 py-2 text-sm font-medium text-center text-white bg-primary-700 rounded-lg hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800" type="button">
                    Alasan
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


                </tbody>
            </table>


            <script>
                document.addEventListener('alpine:init', () => {
            Alpine.data('webData', () => ({

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

</x-app-layout>
