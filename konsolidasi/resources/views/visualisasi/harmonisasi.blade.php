<x-two-panel-layout>
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
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Periode</label>
                <select class="bg-gray-100 border border-gray-300 text-gray-500 text-sm rounded-lg cursor-not-allowed block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400" disabled>
                    <option>Month to month (MtM)</option>
                    <option>Year to date (YtD)</option>
                    <option>Year on year (YoY)</option>
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
                            <div x-show="dropdowns.province" x-transition @click.away="closeDropdown('province')" class="z-10 bg-white divide-y divide-gray-100 rounded-lg shadow-sm w-44 dark:bg-gray-700 absolute mt-2 max-h-60 overflow-y-auto">
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

    <div id="visualizationCanvas" class="w-full md:w-3/4 p-4 md:overflow-y-auto md:h-full transition-all duration-300 dark:bg-gray-900" :class="{ 'md:w-full': !isBuilderVisible }">
        <div class="grid grid-cols-1 md:grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
            <div class="bg-white p-4 rounded-lg shadow-md relative dark:bg-gray-800">
                <h3 class="text-lg font-bold">Total: 198M</h3>
                <canvas id="barChart" class="w-full h-64 md:h-auto"></canvas>
            </div>
        </div>
    </div>

    <script>
        function toggleFullscreen(canvasId) {
            const canvas = document.getElementById(canvasId);
            const fullscreenIcon = document.getElementById(`fullscreenIcon${canvasId.charAt(0).toUpperCase() + canvasId.slice(1)}`); // Get the correct icon

            if (!document.fullscreenElement) {
                canvas.parentElement.requestFullscreen().catch(err => {
                    console.log(`Error trying to enable fullscreen: ${err.message}`);
                });
                fullscreenIcon.textContent = 'close_fullscreen'; // Change icon to close
            } else {
                document.exitFullscreen();
                fullscreenIcon.textContent = 'fullscreen'; // Change icon back to fullscreen
            }
        }

        // Bar Chart
        new Chart(document.getElementById('barChart'), {
            type: 'bar',
            data: {
                labels: ['Service', 'Sales & Office', 'Production', 'Construction', 'Military', 'Management'],
                datasets: [{
                    label: 'Total Population',
                    data: [70, 50, 40, 20, 10, 80],
                    backgroundColor: ['#facc15', '#22c55e', '#3b82f6', '#ec4899', '#6366f1', '#f97316']
                }]
            }
        });

        // Line Chart
        new Chart(document.getElementById('lineChart'), {
            type: 'line',
            data: {
                labels: [2014, 2015, 2016, 2017, 2018, 2019, 2020, 2021, 2022],
                datasets: [{
                    label: 'Total Population',
                    data: [60, 62, 64, 66, 68, 70, 72, 74, 76],
                    borderColor: '#f97316',
                    fill: false
                }]
            }
        });

        // Pie Chart
        new Chart(document.getElementById('pieChart'), {
            type: 'pie',
            data: {
                labels: ['Category A', 'Category B', 'Category C', 'Category D'],
                datasets: [{
                    data: [300, 150, 100, 50],
                    backgroundColor: ['#3b82f6', '#ec4899', '#22c55e', '#f97316']
                }]
            }
        });

        // Area Chart
        new Chart(document.getElementById('areaChart'), {
            type: 'line',
            data: {
                labels: [2014, 2015, 2016, 2017, 2018, 2019, 2020, 2021, 2022],
                datasets: [{
                    label: 'Growth Rate',
                    data: [5, 6, 8, 12, 15, 18, 20, 22, 25],
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.3)',
                    fill: true
                }]
            }
        });
    </script>
</x-two-panel-layout>
