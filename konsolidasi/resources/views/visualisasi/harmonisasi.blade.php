<x-two-panel-layout>
    @section('vite')
    @vite(['resources/css/app.css', 'resources/js/harmonisasi.js'])
    @endsection

    <!-- Load eCharts via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/echarts@latest/dist/echarts.min.js"></script>

    <!-- Modals -->
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
            <p x-text="errorMessage"></p>
            <div class="mt-4 flex justify-end">
                <x-primary-button type="button" x-on:click="$dispatch('close')">Tutup</x-primary-button>
            </div>
        </div>
    </x-modal>

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
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">Level Wilayah<span class="text-red-500 ml-1">*</span></label>
                    <select name="level_wilayah" x-model="wilayahLevel" @change="updateWilayahOptions" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="1">Nasional</option>
                        <option value="2">Provinsi</option>
                    </select>
                </div>

                <!-- Provinsi Dropdown -->
                <div x-show="wilayahLevel == 2" class="mt-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Provinsi</label>
                    <select x-model="selectedProvince" @change="selectedKabkot = ''; updateKdWilayah(); fetchData()" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="" selected>Pilih Provinsi</option>
                        <template x-for="province in provinces" :key="province.kd_wilayah">
                            <option :value="province.kd_wilayah" x-text="province.nama_wilayah" :selected="province.kd_wilayah == selectedProvince"></option>
                        </template>
                    </select>
                </div>
                <input type="hidden" name="kd_wilayah" x-model="kd_wilayah" required>

                <!-- Komoditas -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">Komoditas</label>
                    <select name="kd_komoditas" x-model="selectedKomoditas" @change="fetchData" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <template x-for="komoditi in komoditas" :key="komoditi.kd_komoditas">
                            <option :value="komoditi.kd_komoditas" x-text="komoditi.nama_komoditas" :selected="komoditi.kd_komoditas == selectedKomoditas"></option>
                        </template>
                    </select>
                </div>

                <!-- Error Message Below Form -->
                <div x-show="errorMessage" class="my-2 text-sm text-red-600" x-text="errorMessage"></div>

                <x-primary-button type="submit" x-bind:disabled="!checkFormValidity()" class="w-full">
                    <span x-show="!loading">Filter</span>
                    <span x-show="loading">Loading...</span>
                </x-primary-button>
            </div>
        </form>
    </x-slot>

    <div class="w-full md:overflow-y-auto md:h-full transition-all duration-300 dark:bg-gray-900 p-4">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
            <!-- Error Alert Box -->
            <div x-show="errorMessage || errors.length > 0" id="alert-box" class="col-span-12 p-4 bg-yellow-100 border border-yellow-400 text-yellow-700 rounded-lg relative">
                <p x-text="errorMessage"></p>
                <ul class="flex flex-wrap gap-2" x-show="errors.length > 0">
                    <template x-for="error in errors" :key="error">
                        <li>
                            <p x-text="error"></p>
                        </li>
                    </template>
                </ul>
                <button type="button" class="absolute top-2 right-2 p-1 text-yellow-400 bg-transparent rounded-full hover:bg-yellow-200 hover:text-yellow-900" x-on:click="dismissErrors">
                    <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                    </svg>
                    <span class="sr-only">Close alert</span>
                </button>
            </div>

            <!-- Title -->
            <div class="bg-white p-4 rounded-lg shadow-md dark:bg-gray-800 col-span-12">
                <h2 x-text="`Inflasi Komoditas ${selectedKomoditas ? komoditas.find(k => k.kd_komoditas === selectedKomoditas)?.nama_komoditas || '' : ''} ${wilayahLevel == 1 ? 'Nasional' : provinces.find(p => p.kd_wilayah === selectedProvince)?.nama_wilayah || ''} ${bulanOptions.find(b => b[1] == bulan)?.[0] || ''} ${tahun}`"></h2>
            </div>

            <!-- Stacked Line Chart -->
            <div x-show="data?.chart_data?.stackedLine" class="bg-white p-4 rounded-lg shadow-md dark:bg-gray-800 col-span-12">
                <h3 class="text-lg font-semibold mb-2">Tren Inflasi dan Andil</h3>
                <div id="stackedLineChart" class="chart-container w-full h-96"></div>
                <button id="toggleAndilBtn" x-on:click="toggleAndil" class="mt-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    <span x-text="showAndil ? 'Lihat Inflasi' : 'Lihat Andil'"></span>
                </button>
            </div>

            <!-- Summary Boxes -->
            <template x-for="label in ['Harga Konsumen Kota', 'Harga Konsumen Desa', 'Harga Perdagangan Besar', 'Harga Produsen Desa', 'Harga Produsen']" :key="label">
                <div class="bg-white p-4 rounded-lg shadow-md dark:bg-gray-800 col-span-12 md:col-span-2 border-l-8" :style="`border-left-color: ${colors[label]}`">
                    <h4 class="text-md font-semibold text-gray-800 dark:text-white" x-text="label"></h4>
                    <p class="text-gray-600 dark:text-gray-300">Inflasi:
                        <span class="font-bold text-gray-900 dark:text-white" x-text="data?.summary?.[label]?.inflasi ? data.summary[label].inflasi.toFixed(2) + '%' : 'N/A'"></span>
                    </p>
                    <p class="text-gray-600 dark:text-gray-300">Andil:
                        <span class="font-bold text-gray-900 dark:text-white" x-text="data?.summary?.[label]?.andil ? data.summary[label].andil.toFixed(2) + '%' : 'N/A'"></span>
                    </p>
                </div>
            </template>

            <!-- Horizontal Bar Chart -->
            <div x-show="data?.chart_data?.horizontalBar" class="bg-white p-4 rounded-lg shadow-md dark:bg-gray-800 col-span-12">
                <h3 class="text-lg font-semibold mb-2">Perbandingan Inflasi dan Andil Antartingkat Harga</h3>
                <div id="horizontalBarChart" class="chart-container w-full h-96"></div>
            </div>

            <!-- Heatmap Chart -->
            <div x-show="data?.chart_data?.heatmap && kd_wilayah === '0'" class="bg-white p-4 rounded-lg shadow-md dark:bg-gray-800 col-span-12">
                <h3 class="text-lg font-semibold mb-2">Inflasi per Provinsi Antartingkat Harga</h3>
                <div id="heatmapChart" class="chart-container w-full h-[550px]"></div>
            </div>

            <!-- Stacked Bar Chart -->
            <div x-show="data?.chart_data?.stackedBar && kd_wilayah === '0'" class="bg-white p-4 rounded-lg shadow-md dark:bg-gray-800 col-span-12">
                <h3 class="text-lg font-semibold mb-2">Distribusi Inflasi per Tingkat Harga</h3>
                <div id="stackedBarChart" class="chart-container w-full h-96"></div>
            </div>

            <!-- Level Selection -->
            <div class="flex flex-col md:flex-row gap-4 items-center bg-blue-700 p-4 rounded-lg shadow-md dark:bg-gray-800 col-span-12">
                <h3 class="flex-1 text-white text-lg font-bold text-center md:text-left">Inflasi</h3>
                <select id="levelHargaSelect" x-model="selectedLevel" x-on:change="selectLevel($event)" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5">
                    <option value="HK">Harga Konsumen Kota</option>
                    <option value="HD">Harga Konsumen Desa</option>
                    <option value="HPB">Harga Perdagangan Besar</option>
                    <option value="HPD">Harga Produsen Desa</option>
                    <option value="HP">Harga Produsen</option>
                </select>
            </div>

            <!-- Province and Kabkot Horizontal Bar Charts -->
            <div x-show="data?.chart_data?.provHorizontalBar" class="bg-white p-4 rounded-lg shadow-md dark:bg-gray-800 col-span-12" :class="selectedLevel === 'HK' ? 'md:col-span-6' : 'md:col-span-12'">
                <h3 class="text-lg font-semibold mb-2">Inflasi per Provinsi</h3>
                <div id="provHorizontalBarChart" class="chart-container w-full h-[550px]"></div>
            </div>

            <div x-show="selectedLevel === 'HK' && data?.chart_data?.kabkotHorizontalBar" class="bg-white p-4 rounded-lg shadow-md dark:bg-gray-800 col-span-12 md:col-span-6">
                <h3 class="text-lg font-semibold mb-2">Inflasi per Kabupaten/Kota</h3>
                <div id="kabkotHorizontalBarChart" class="chart-container w-full h-[550px]"></div>
            </div>

            <!-- Choropleth Maps -->
            <div x-show="data?.chart_data?.provinsiChoropleth && provinsiGeoJson" class="bg-white p-4 rounded-lg shadow-md dark:bg-gray-800 col-span-12">
                <h3 class="text-lg font-semibold mb-2">Peta Inflasi Provinsi</h3>
                <div id="provinsiChoropleth" class="chart-container w-full h-[1000px]"></div>
            </div>

            <div x-show="selectedLevel === 'HK' && data?.chart_data?.kabkotChoropleth && kabkotGeoJson" class="bg-white p-4 rounded-lg shadow-md dark:bg-gray-800 col-span-12">
                <h3 class="text-lg font-semibold mb-2">Peta Inflasi Kabupaten/Kota</h3>
                <div id="kabkotChoropleth" class="chart-container w-full h-[1000px]"></div>
            </div>
        </div>
    </div>
</x-two-panel-layout>