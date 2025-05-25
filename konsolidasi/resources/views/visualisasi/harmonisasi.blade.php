<x-two-panel-layout>
    @section('vite')
    @vite(['resources/css/app.css', 'resources/js/harmonisasi.js'])
    @endsection

    <!-- Load eCharts via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/echarts@latest/dist/echarts.min.js"></script>

    <!-- Modals -->
    <x-modal name="success-modal" title="Berhasil" maxWidth="md">
        <div class="text-gray-900 ">
            <p x-text="modalMessage"></p>
            <div class="mt-4 flex justify-end">
                <x-primary-button type="button" x-on:click="$dispatch('close')">Tutup</x-primary-button>
            </div>
        </div>
    </x-modal>

    <x-modal name="error-modal" title="Kesalahan" maxWidth="md">
        <div class="text-gray-900 ">
            <p x-text="modalMessage"></p>
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
                    <label class="block mb-2 text-sm font-medium text-gray-900 ">Periode</label>
                    <select class="bg-gray-100 border border-gray-300 text-gray-500 text-sm rounded-lg cursor-not-allowed block w-full p-2.5   " disabled>
                        <option>Month to month (MtM)</option>
                        <option>Year to date (YtD)</option>
                        <option>Year on year (YoY)</option>
                    </select>
                </div>

                <!-- Wilayah -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">Level Wilayah<span class="text-red-500 ml-1">*</span></label>
                    <select name="level_wilayah" x-model="pendingWilayahLevel" @change="updateWilayahOptions" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="1" :selected="pendingWilayahLevel == 1">Nasional</option>
                        <option value="2" :selected="pendingWilayahLevel == 2">Provinsi</option>
                    </select>
                </div>

                <!-- Provinsi Dropdown -->
                <div x-show="pendingWilayahLevel == 2" class="mt-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Provinsi</label>
                    <select x-model="selectedProvince" @change="selectedKabkot = ''; updateKdWilayah();" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
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
                    <select name="kd_komoditas" x-model="selectedKomoditas" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <template x-for="komoditi in komoditas" :key="komoditi.kd_komoditas">
                            <option :value="komoditi.kd_komoditas" x-text="komoditi.nama_komoditas" :selected="komoditi.kd_komoditas == selectedKomoditas"></option>
                        </template>
                    </select>
                </div>

                <!-- Error Message Below Form -->
                <div x-show="errorMessage" class="my-2 text-sm text-red-600" x-text="errorMessage"></div>

                <x-primary-button type="submit" class="w-full">
                    Filter
                </x-primary-button>
            </div>
        </form>
    </x-slot>

    <div class="w-full md:overflow-y-auto md:h-full transition-all duration-300  p-4">
        <div class="grid grid-cols-1 md:grid-cols-10 gap-4">
            <!-- Error Alert Box -->
            <div x-show="errorMessage || errors.length > 0" id="alert-box" class="col-span-10 p-4 bg-yellow-100 border border-yellow-400 text-yellow-700 rounded-lg relative">
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
            <div class="bg-white p-4 rounded-lg shadow-md  col-span-10">
                <h2 x-text="data?.title || 'Loading...'" class="text-gray-900 "></h2>
            </div>

            <!-- Line Chart -->
            <div class="bg-white p-4 rounded-lg shadow-md  col-span-10">
                <h3 class="text-lg font-semibold mb-4" x-text="data?.chart_status?.['line']?.title || 'Tren Inflasi dan Andil'"></h3>
                <div id="lineChart" class="chart-container w-full h-96"></div>
                <button x-show="wilayahLevel === '1'" id="toggleAndilBtn" x-on:click="toggleAndil" class="block mx-auto mt-4 font-semibold underline">
                    <span x-text="showAndil ? 'Lihat Inflasi' : 'Lihat Andil'"></span>
                </button>
            </div>

            <!-- Summary Boxes -->
            <template x-if="wilayahLevel === '1'">
                <template x-for="priceLevel in priceLevels" :key="priceLevel">
                    <div
                        class="bg-white p-4 rounded-lg shadow-md  col-span-10 md:col-span-2 border-l-8"
                        :style="`border-left-color: ${colors[priceLevel] || '#5470C6'}`">
                        <h4 class="text-md font-semibold text-gray-800 " x-text="priceLevel"></h4>
                        <p class="text-gray-600 ">
                            Inflasi:
                            <span
                                class="font-bold text-gray-900 "
                                x-text="formatPercentage(summaryData?.[priceLevel]?.inflasi)"></span>
                        </p>
                        <p class="text-gray-600 ">
                            Andil:
                            <span
                                class="font-bold text-gray-900 "
                                x-text="formatPercentage(summaryData?.[priceLevel]?.andil)"></span>
                        </p>
                    </div>
                </template>
            </template>

            <!-- For wilayahLevel === '2': show only the first one -->
            <template x-if="wilayahLevel === '2'">
                <div
                    class="bg-white p-4 rounded-lg shadow-md  col-span-10 border-l-8"
                    :style="`border-left-color: ${colors[priceLevels[0]] || '#5470C6'}`">
                    <h4 class="text-md font-semibold text-gray-800 " x-text="priceLevels[0]"></h4>
                    <p class="text-gray-600 ">
                        Inflasi:
                        <span
                            class="font-bold text-gray-900 "
                            x-text="formatPercentage(summaryData?.[priceLevels[0]]?.inflasi)"></span>
                    </p>
                </div>
            </template>

            <!-- Horizontal Bar Chart -->
            <div x-show="wilayahLevel === '1'" class="bg-white p-4 rounded-lg shadow-md  col-span-10">
                <h3 class="text-lg font-semibold mb-4" x-text="data?.chart_status?.horizontalBar?.title || 'Perbandingan Inflasi dan Andil Antartingkat Harga'"></h3>
                <div id="horizontalBarChart" class="chart-container w-full h-96"></div>
            </div>

            <!-- Heatmap Chart -->
            <div x-show="wilayahLevel === '1'" class="bg-white p-4 rounded-lg shadow-md  col-span-10">
                <h3 class="text-lg font-semibold mb-4" x-text="data?.chart_status?.heatmap?.title || 'Inflasi per Provinsi Antartingkat Harga'"></h3>
                <div id="heatmapChart" class="chart-container w-full h-[550px]"></div>
            </div>

            <!-- Stacked Bar Chart -->
            <div x-show="wilayahLevel === '1'" class="bg-white p-4 rounded-lg shadow-md  col-span-10">
                <h3 class="text-lg font-semibold mb-4" x-text="data?.chart_status?.stackedBar?.title || 'Distribusi Inflasi per Tingkat Harga'"></h3>
                <div id="stackedBarChart" class="chart-container w-full h-96"></div>
            </div>

            <!-- Harga Konsumen Kota -->
            <div class="flex flex-col md:flex-row gap-4 items-center bg-primary-700 p-4 rounded-lg shadow-md  col-span-10">
                <h3 class="flex-1 text-white text-lg font-bold text-center md:text-left">Harga Konsumen Kota</h3>
            </div>
            <div x-show="wilayahLevel === '1'" class="bg-white p-4 rounded-lg shadow-md  col-span-10"" :class=" wilayahLevel==='1' ? 'md:col-span-5' : 'md:col-span-10'">
                <h3 class=" text-lg font-semibold mb-4" x-text="data?.chart_status?.provHorizontalBar?.title || 'Inflasi per Provinsi'">
                </h3>
                <div id="provHorizontalBarChart_01" class="chart-container w-full h-[550px]"></div>
            </div>
            <div
                class="bg-white p-4 rounded-lg shadow-md  col-span-10"
                :class="wilayahLevel === '1' ? 'md:col-span-5' : 'md:col-span-10'">
                <h3
                    class="text-lg font-semibold mb-4"
                    x-text="data?.chart_status?.kabkotHorizontalBar?.title || 'Inflasi per Kabupaten/Kota'"></h3>
                <div id="kabkotHorizontalBarChart_01" class="chart-container w-full h-[550px]"></div>
            </div>

            <div x-show="wilayahLevel === '1'" class="bg-white p-4 rounded-lg shadow-md  col-span-10">
                <h3 class="text-lg font-semibold mb-4" x-text="data?.chart_status?.provinsiChoropleth?.title || 'Peta Inflasi Provinsi'"></h3>
                <div id="provinsiChoropleth_01" class="chart-container w-full h-[400px]"></div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-md  col-span-10">
                <h3 class="text-lg font-semibold mb-4" x-text="data?.chart_status?.[wilayahLevel === '1' ? 'kabkotChoropleth' : 'provinsiKabkotChoropleth']?.title || 'Peta Inflasi Kabupaten/Kota'"></h3>
                <div id="kabkotChoropleth_01" class="chart-container w-full h-[400px]"></div>
            </div>
        </div>

        <!-- Additional Levels for National (wilayahLevel === '1') -->
        <div x-show="wilayahLevel === '1'">
            <div class="grid grid-cols-1 md:grid-cols-10 gap-4">
                <!-- Harga Konsumen Desa -->
                <div class="flex flex-col md:flex-row gap-4 items-center bg-primary-700 p-4 rounded-lg shadow-md  col-span-10 mt-4">
                    <h3 class="flex-1 text-white text-lg font-bold text-center md:text-left">Harga Konsumen Desa</h3>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-md  col-span-10">
                    <h3 class="text-lg font-semibold mb-4" x-text="data?.chart_status?.provHorizontalBar?.title || 'Inflasi per Provinsi'"></h3>
                    <div id="provHorizontalBarChart_02" class="chart-container w-full h-[550px]"></div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-md  col-span-10">
                    <h3 class="text-lg font-semibold mb-4" x-text="data?.chart_status?.provinsiChoropleth?.title || 'Peta Inflasi Provinsi'"></h3>
                    <div id="provinsiChoropleth_02" class="chart-container w-full h-[400px]"></div>
                </div>

                <!-- Harga Perdagangan Besar -->
                <div class="flex flex-col md:flex-row gap-4 items-center bg-primary-700 p-4 rounded-lg shadow-md  col-span-10">
                    <h3 class="flex-1 text-white text-lg font-bold text-center md:text-left">Harga Perdagangan Besar</h3>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-md  col-span-10">
                    <h3 class="text-lg font-semibold mb-4" x-text="data?.chart_status?.provHorizontalBar?.title || 'Inflasi per Provinsi'"></h3>
                    <div id="provHorizontalBarChart_03" class="chart-container w-full h-[550px]"></div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-md  col-span-10">
                    <h3 class="text-lg font-semibold mb-4" x-text="data?.chart_status?.provinsiChoropleth?.title || 'Peta Inflasi Provinsi'"></h3>
                    <div id="provinsiChoropleth_03" class="chart-container w-full h-[400px]"></div>
                </div>

                <!-- Harga Produsen Desa -->
                <div class="flex flex-col md:flex-row gap-4 items-center bg-primary-700 p-4 rounded-lg shadow-md  col-span-10">
                    <h3 class="flex-1 text-white text-lg font-bold text-center md:text-left">Harga Produsen Desa</h3>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-md  col-span-10">
                    <h3 class="text-lg font-semibold mb-4" x-text="data?.chart_status?.provHorizontalBar?.title || 'Inflasi per Provinsi'"></h3>
                    <div id="provHorizontalBarChart_04" class="chart-container w-full h-[550px]"></div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-md  col-span-10">
                    <h3 class="text-lg font-semibold mb-4" x-text="data?.chart_status?.provinsiChoropleth?.title || 'Peta Inflasi Provinsi'"></h3>
                    <div id="provinsiChoropleth_04" class="chart-container w-full h-[400px]"></div>
                </div>

                <!-- Harga Produsen -->
                <div class="flex flex-col md:flex-row gap-4 items-center bg-primary-700 p-4 rounded-lg shadow-md  col-span-10">
                    <h3 class="flex-1 text-white text-lg font-bold text-center md:text-left">Harga Produsen</h3>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-md  col-span-10">
                    <h3 class="text-lg font-semibold mb-4" x-text="data?.chart_status?.provHorizontalBar?.title || 'Inflasi per Provinsi'"></h3>
                    <div id="provHorizontalBarChart_05" class="chart-container w-full h-[550px]"></div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-md  col-span-10">
                    <h3 class="text-lg font-semibold mb-4" x-text="data?.chart_status?.provinsiChoropleth?.title || 'Peta Inflasi Provinsi'"></h3>
                    <div id="provinsiChoropleth_05" class="chart-container w-full h-[400px]"></div>
                </div>
            </div>
        </div>
    </div>
</x-two-panel-layout>