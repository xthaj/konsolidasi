<x-two-panel-layout>

    @section('vite')
    @vite(['resources/css/app.css', 'resources/js/alpine-init.js', 'resources/js/harmonisasi.js', 'resources/js/alpine-start.js'])
    @endsection


    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
        crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""></script>
    <script src="https://cdn.jsdelivr.net/npm/echarts@latest/dist/echarts.min.js"></script>

    <!-- Modals -->
    <x-modal name="data-not-found" focusable title="Data Tidak Ditemukan">
        <div class="px-6 py-4">
            <div>
                <p class="text-red-600">Beberapa data tidak ditemukan:</p>
                <ul class="list-disc pl-5 mt-2">
                    <template x-for="missing in modalContent.missingItems" :key="missing">
                        <li x-text="missing"></li>
                    </template>
                </ul>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
            </div>
        </div>
    </x-modal>

    <x-slot name="sidebar">
        <div id="vizBuilderPanel" class="space-y-4 md:space-y-6 mt-4">
            <form @submit.prevent="dataCheck">
                @csrf
                <div class="space-y-4 md:space-y-6 mt-4">
                    <!-- Bulan & Tahun -->

                    <div>
                        <div class="flex gap-4">
                            <div class="w-1/2">
                                <label class="block mb-2 text-sm font-medium text-gray-900">Bulan<span class="text-red-500 ml-1">*</span></label>
                                <select name="bulan" x-model="bulan" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 focus:ring-primary-500 focus:border-primary-500">
                                    @foreach(['Januari' => '01', 'Februari' => '02', 'Maret' => '03', 'April' => '04', 'Mei' => '05', 'Juni' => '06', 'Juli' => '07', 'Agustus' => '08', 'September' => '09', 'Oktober' => '10', 'November' => '11', 'Desember' => '12'] as $nama => $bln)
                                    <option value="{{ $bln }}" @selected(request('bulan')==$bln)>{{ $nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="w-1/2">
                                <label class="block mb-2 text-sm font-medium text-gray-900">Tahun<span class="text-red-500 ml-1">*</span></label>
                                <select name="tahun" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 focus:ring-primary-500 focus:border-primary-500">
                                    <template x-for="year in tahunOptions" :key="year">
                                        <option :value="year" :selected="year === tahun" x-text="year"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                    </div>

                    <p id="helper-text-explanation" class="text-sm text-gray-500" x-show="isActivePeriod">Periode aktif</p>

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
                    <!-- this shoudnt be checkbox just dropdown level -->
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900">Wilayah<span class="text-red-500 ml-1">*</span></label>
                        <div class="flex items-start mb-6">
                            <div class="flex items-center h-5">
                                <input type="checkbox" id="is_pusat" x-model="isPusat" @click="togglePusat()" class="w-4 h-4 border border-gray-300 rounded-sm bg-gray-50 focus:ring-3 focus:ring-primary-300" />
                            </div>
                            <label for="is_pusat" class="ms-2 text-sm font-medium text-gray-900">Nasional</label>
                        </div>

                        <!-- Provinsi Dropdown -->
                        <div x-show="!isPusat" class="mb-4">
                            <label class="block mb-2 text-sm font-medium text-gray-900">Provinsi</label>
                            <select
                                x-model="selectedProvince"
                                @change="selectedKabkot = ''; updateKdWilayah()"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                                <option value="" selected>Pilih Provinsi</option>
                                <template x-for="province in provinces" :key="province.kd_wilayah">
                                    <option
                                        :value="province.kd_wilayah"
                                        x-text="province.nama_wilayah"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Kabkot Dropdown -->
                        <div x-show="!isPusat && selectedKdLevel === '01'" class="mb-4">
                            <label class="block mb-2 text-sm font-medium text-gray-900">Kabupaten/Kota</label>
                            <select x-model="selectedKabkot" @change="updateKdWilayah()" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                                <option value="" selected>Pilih Kabupaten/Kota</option>
                                <template x-for="kabkot in filteredKabkots" :key="kabkot.kd_wilayah">
                                    <option :value="kabkot.kd_wilayah" x-text="kabkot.nama_wilayah" :selected="kabkot.kd_wilayah == '{{ request('kd_wilayah') }}'"></option>
                                </template>
                            </select>
                        </div>

                        <div x-show="!isPusat && selectedKdLevel !== '01' && selectedKdLevel !== ''" class="text-sm text-gray-500">
                            Data tidak tersedia untuk kabupaten/kota pada level harga ini.
                        </div>

                        <input type="hidden" name="kd_wilayah" :value="isPusat ? '0' : kd_wilayah" required>
                    </div>

                    <!-- Komoditas (Not Required) -->
                    <div>
                        <label for="komoditas" class="block mb-2 text-sm font-medium text-gray-900">Komoditas</label>
                        <select id="komoditas" name="kd_komoditas" x-model="selectedKomoditas" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5">
                            <option value="">Pilih Komoditas</option>
                            <template x-for="komoditi in komoditas" :key="komoditi.kd_komoditas">
                                <option :value="komoditi.kd_komoditas" x-text="komoditi.nama_komoditas" :selected="komoditi.kd_komoditas == '{{ request('kd_komoditas') }}'"></option>
                            </template>
                        </select>
                    </div>



                    <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:outline-none focus:ring-primary-300 text-white font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">Konfirmasi Rekonsiliasi</button>
            </form>
        </div>
    </x-slot>

    <div id="visualizationCanvas" class="w-full p-4 md:overflow-y-auto md:h-full transition-all duration-300 dark:bg-gray-900">
        <div class="grid grid-cols-1 md:grid-cols-10 gap-4">
            <h2>{{ $title }}</h2>

            <!-- Display message if present -->
            @if(isset($message))
            <div class="col-span-1 md:col-span-10 p-4 bg-yellow-100 border border-yellow-400 text-yellow-700 rounded-lg">
                {{ $message }}
            </div>
            @endif

            <!-- Show stacked line chart only if data is successful -->
            @if(!empty($data['stackedLine']))
            <div class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-10">
                <div id="stackedLineChart" class="w-full h-96"></div>
            </div>
            @endif



            <!-- <div class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-10">
                <div id="heatMap" class="w-full h-96"></div>
            </div>

            <div class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-10">
                <div id="verticalBarChart" class="w-full h-96"></div>
            </div>

            <div class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-10">
                <div id="stackedBarChart" class="w-full h-96"></div>
            </div>

            <div class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-10">
                <div id="doubleVerticalBarChart" class="w-full h-96"></div>
            </div> -->

            <!-- Dropdown Div -->
            <div class="flex gap-8 items-center bg-primary-700 md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-10">
                <h3 class="flex-1 text-white text-lg font-bold">Level Harga</h3>
                <select class="w-64 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option>Harga Konsumen Kota</option>
                    <option>Harga Konsumen Desa</option>
                    <option>Harga Perdagangan Besar</option>
                    <option>Harga Produsen Desa</option>
                    <option>Harga Produsen</option>
                </select>
            </div>

            <div class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-10">
                <div id="provinsiChoropleth" class="w-full h-96"></div>
            </div>

            <div class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-10">
                <div id="kabkotChoropleth" class="w-full h-96"></div>
            </div>

            <!-- Big Div: Inflasi Chart -->
            <!-- <div class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-10">
                <button @click="toggleFullscreen('multiAxisChart')" class="absolute top-2 right-2 p-2">
                    <span id="fullscreenIconMultiAxisChart" class="material-symbols-outlined text-xl">fullscreen</span>
                </button>
                <button class="absolute top-2 right-12 p-2 hover:text-gray-200 text:hover:bg-gray-700">
                    <span class="material-symbols-outlined text-xl">download</span>
                </button>
                <h3 class="text-lg font-bold">Inflasi</h3>
                <div class="flex justify-end space-x-2 mb-2">
                    <button @click="showInflasiLine()" class="bg-blue-500 text-white px-4 py-2 rounded">Show Inflasi</button>
                    <button @click="showAndilLine()" class="bg-green-500 text-white px-4 py-2 rounded">Show Andil</button>
                </div>
                <div id="multiAxisChart" class="w-full h-96"></div>
            </div> -->

            <!-- 5 Mini Divs for Price Levels -->
            <div class="bg-white p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-2">
                <h4 class="text-md font-semibold">Harga Konsumen Kota</h4>
                <p>Inflasi: <span class="font-bold">2.5%</span></p>
                <p>Andil: <span class="font-bold">0.8%</span></p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-2">
                <h4 class="text-md font-semibold">Harga Konsumen Desa</h4>
                <p>Inflasi: <span class="font-bold">2.1%</span></p>
                <p>Andil: <span class="font-bold">0.6%</span></p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-2">
                <h4 class="text-md font-semibold">Harga Perdagangan Besar</h4>
                <p>Inflasi: <span class="font-bold">3.0%</span></p>
                <p>Andil: <span class="font-bold">1.2%</span></p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-2">
                <h4 class="text-md font-semibold">Harga Produsen Desa</h4>
                <p>Inflasi: <span class="font-bold">1.8%</span></p>
                <p>Andil: <span class="font-bold">0.5%</span></p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-2">
                <h4 class="text-md font-semibold">Harga Produsen</h4>
                <p>Inflasi: <span class="font-bold">2.7%</span></p>
                <p>Andil: <span class="font-bold">0.9%</span></p>
            </div>

            <!-- Heatmap -->
            <!-- <div class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-10">
                <h3 class="text-lg font-bold flex items-end gap-1 mb-4">
                    Inflasi per Tingkat Harga di Seluruh Provinsi
                    <span class="material-symbols-outlined text-base">swap_vert</span>
                </h3>
                <div id="inflationHeatmap" class="w-full h-[600px]"></div>
            </div> -->

            <!-- Small Multiples (Replace with ECharts) -->
            <!-- <div class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-10">
                <h3 class="text-lg font-bold flex items-end gap-1 mb-4">
                    Inflasi per Tingkat Harga di Seluruh Provinsi
                    <span class="material-symbols-outlined text-base">swap_vert</span>
                </h3>
                <div class="max-h-64 overflow-y-auto overflow-x-auto">
                    <div class="h-[1000px] flex flex-row gap-4">
                        <div class="relative w-[350px]">
                            <h4 class="text-sm font-semibold mb-2">Harga Produsen</h4>
                            <div id="priceLevelChart1" class="w-full h-full"></div>
                            <button @click="toggleFullscreen('priceLevelChart1')" class="absolute top-2 right-2 p-2">
                                <span id="fullscreenIconPriceLevelChart1" class="material-symbols-outlined text-xl">fullscreen</span>
                            </button>
                        </div>
                        <div class="relative w-[210px]">
                            <h4 class="text-sm font-semibold mb-2">Harga Produsen Desa</h4>
                            <div id="priceLevelChart2" class="w-full h-full"></div>
                            <button @click="toggleFullscreen('priceLevelChart2')" class="absolute top-2 right-2 p-2">
                                <span id="fullscreenIconPriceLevelChart2" class="material-symbols-outlined text-xl">fullscreen</span>
                            </button>
                        </div>
                        <div class="relative w-[210px]">
                            <h4 class="text-sm font-semibold mb-2">Harga Perdagangan Besar</h4>
                            <div id="priceLevelChart3" class="w-full h-full"></div>
                            <button @click="toggleFullscreen('priceLevelChart3')" class="absolute top-2 right-2 p-2">
                                <span id="fullscreenIconPriceLevelChart3" class="material-symbols-outlined text-xl">fullscreen</span>
                            </button>
                        </div>
                        <div class="relative w-[210px]">
                            <h4 class="text-sm font-semibold mb-2">Harga Konsumen Desa</h4>
                            <div id="priceLevelChart4" class="w-full h-full"></div>
                            <button @click="toggleFullscreen('priceLevelChart4')" class="absolute top-2 right-2 p-2">
                                <span id="fullscreenIconPriceLevelChart4" class="material-symbols-outlined text-xl">fullscreen</span>
                            </button>
                        </div>
                        <div class="relative w-[210px]">
                            <h4 class="text-sm font-semibold mb-2">Harga Konsumen Kota</h4>
                            <div id="priceLevelChart5" class="w-full h-full"></div>
                            <button @click="toggleFullscreen('priceLevelChart5')" class="absolute top-2 right-2 p-2">
                                <span id="fullscreenIconPriceLevelChart5" class="material-symbols-outlined text-xl">fullscreen</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div> -->

            <!-- Big Div: Horizontal Bar Chart -->
            <!-- <div class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-10">
                <button @click="toggleFullscreen('horizontalBarChart')" class="absolute top-2 right-2 p-2">
                    <span id="fullscreenIconHorizontalBarChart" class="material-symbols-outlined text-xl">fullscreen</span>
                </button>
                <button class="absolute top-2 right-12 p-2 hover:text-gray-200 text:hover:bg-gray-700">
                    <span class="material-symbols-outlined text-xl">download</span>
                </button>
                <h3 class="text-lg font-bold">Inflasi & Andil Maret 2025</h3>
                <div id="horizontalBarChart" class="w-full h-96"></div>
            </div> -->

            <!-- Big Div: Stacked Bar Chart -->
            <!-- <div class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-10">
                <button @click="toggleFullscreen('stackedBarChart')" class="absolute top-2 right-2 p-2">
                    <span id="fullscreenIconStackedBarChart" class="material-symbols-outlined text-xl">fullscreen</span>
                </button>
                <button class="absolute top-2 right-12 p-2 hover:text-gray-200 text:hover:bg-gray-700">
                    <span class="material-symbols-outlined text-xl">download</span>
                </button>
                <h3 class="text-lg font-bold">Jumlah Provinsi berdasarkan Arah Inflasi</h3>
                <div id="stackedBarChart" class="w-full h-96"></div>
            </div> -->

            <!-- Dropdown Div -->
            <!-- <div class="flex gap-8 items-center bg-primary-700 md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-10">
                <h3 class="flex-1 text-white text-lg font-bold">Level Harga</h3>
                <select class="w-64 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option>Harga Konsumen Kota</option>
                    <option>Harga Konsumen Desa</option>
                    <option>Harga Perdagangan Besar</option>
                    <option>Harga Produsen Desa</option>
                    <option>Harga Produsen</option>
                </select>
            </div> -->

            <!-- Inflasi Provinsi Bar -->
            <!-- <div class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-5">
                <h3 class="text-lg font-bold flex items-end gap-1">
                    Inflasi Provinsi
                    <span class="material-symbols-outlined text-base">swap_vert</span>
                </h3>
                <div class="max-h-64 overflow-y-auto">
                    <div class="h-[1000px]">
                        <div id="rankBarChartProvinsi" class="w-full h-full"></div>
                    </div>
                </div>
            </div> -->

            <!-- Inflasi Kabupaten/Kota Bar -->
            <!-- <div class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-5">
                <h3 class="text-lg font-bold flex items-end gap-1">
                    Inflasi Kabupaten/Kota
                    <span class="material-symbols-outlined text-base">swap_vert</span>
                </h3>
                <div class="max-h-64 overflow-y-auto">
                    <div class="h-[1000px]">
                        <div id="rankBarChartProvinsi2" class="w-full h-full"></div>
                    </div>
                </div>
            </div> -->

            <!-- Big Div: Map Provinsi (Choropleth) -->
            <!-- <div class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-10">
                <button @click="toggleFullscreen('map')" class="absolute top-2 right-2 p-2">
                    <span id="fullscreenIconMap" class="material-symbols-outlined text-xl">fullscreen</span>
                </button>
                <button class="absolute top-2 right-12 p-2 hover:text-gray-200 text:hover:bg-gray-700">
                    <span class="material-symbols-outlined text-xl">download</span>
                </button>
                <h3 class="text-lg font-bold">Map Provinsi</h3>
                <div id="map" class="h-64"></div>
            </div> -->

            <!-- Big Div: Map Kabkot (Choropleth) -->
            <!-- <div class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-10">
                <button @click="toggleFullscreen('map2')" class="absolute top-2 right-2 p-2">
                    <span id="fullscreenIconMap2" class="material-symbols-outlined text-xl">fullscreen</span>
                </button>
                <button class="absolute top-2 right-12 p-2 hover:text-gray-200 text:hover:bg-gray-700">
                    <span class="material-symbols-outlined text-xl">download</span>
                </button>
                <h3 class="text-lg font-bold">Map Kabkot</h3>
                <div id="map2" class="h-64"></div>
            </div> -->
        </div>
    </div>

    <!-- Pass data to JavaScript -->
    <script>
        // Define global variables for JS to use
        window.chartTitle = @json($title);
        @if(isset($data['stackedLine']) && !isset($message))
        window.stackedLineData = @json($data['stackedLine']);
        @else
        window.stackedLineData = null; // Explicitly set to null if no data or message exists
        @endif
    </script>
</x-two-panel-layout>