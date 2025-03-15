<x-two-panel-layout>

@section('vite')
    @vite(['resources/css/app.css', 'resources/js/alpine-init.js', 'resources/js/pemilihan.js', 'resources/js/alpine-start.js'])
@endsection

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
            <p id="helper-text-explanation" class="mt-2 text-sm text-gray-500 dark:text-gray-400">Periode aktif</p>

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
                        <select class="bg-gray-100 border border-gray-300 text-gray-500 text-sm rounded-lg  block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400">
                            <template x-for="province in provinces" :key="province.kd_wilayah">
                                <option x-text="province.nama_wilayah"></option>
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

    <div id="visualizationCanvas" class="w-full p-4 md:overflow-y-auto md:h-full transition-all duration-300 dark:bg-gray-900" :class="{ 'md:w-full': !isBuilderVisible }">
        <div class="grid grid-cols-1 md:grid-cols-10 gap-4">
            <!-- Big Div: Inflasi Chart -->
            <div class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-10">
                <button onclick="toggleFullscreen('multiAxisChart')" class="absolute top-2 right-2 p-2 hover:text-gray-200 text:hover:bg-gray-700">
                    <span id="fullscreenIconMultiAxisChart" class="material-symbols-outlined text-xl">fullscreen</span>
                </button>
                <button class="absolute top-2 right-12 p-2 hover:text-gray-200 text:hover:bg-gray-700">
                    <span class="material-symbols-outlined text-xl">download</span>
                </button>
                <h3 class="text-lg font-bold">Inflasi</h3>
                <div class="flex justify-end space-x-2 mb-2">
                    <button onclick="showInflasiLine()" class="bg-blue-500 text-white px-4 py-2 rounded">Show Inflasi</button>
                    <button onclick="showAndilLine()" class="bg-green-500 text-white px-4 py-2 rounded">Show Andil</button>
                </div>
                <canvas id="multiAxisChart" class="w-full max-h-96 md:h-auto"></canvas>
            </div>

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

            <!-- Big Div: Horizontal Bar Chart -->
            <div class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-10">
                <button onclick="toggleFullscreen('horizontalBarChart')" class="absolute top-2 right-2 p-2 hover:text-gray-200 text:hover:bg-gray-700">
                    <span id="fullscreenIconHorizontalBarChart" class="material-symbols-outlined text-xl">fullscreen</span>
                </button>
                <button class="absolute top-2 right-12 p-2 hover:text-gray-200 text:hover:bg-gray-700">
                    <span class="material-symbols-outlined text-xl">download</span>
                </button>
                <h3 class="text-lg font-bold">Inflasi & Andil Maret 2025</h3>
                <canvas id="horizontalBarChart" class="w-full max-h-96 md:h-auto"></canvas>
            </div>

            <!-- Big Div: Stacked Bar Chart -->
            <div class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-10">
                <button onclick="toggleFullscreen('stackedBarChart')" class="absolute top-2 right-2 p-2 hover:text-gray-200 text:hover:bg-gray-700">
                    <span id="fullscreenIconStackedBarChart" class="material-symbols-outlined text-xl">fullscreen</span>
                </button>
                <button class="absolute top-2 right-12 p-2 hover:text-gray-200 text:hover:bg-gray-700">
                    <span class="material-symbols-outlined text-xl">download</span>
                </button>
                <h3 class="text-lg font-bold">Jumlah Provinsi berdasarkan Arah Inflasi</h3>
                <canvas id="stackedBarChart" class="w-full max-h-96 md:h-auto"></canvas>
            </div>

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

            <!-- Inflasi Provinsi Bar -->
            <div class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-5">
                <h3 class="text-lg font-bold flex items-end gap-1">
                    Inflasi Provinsi
                    <span class="material-symbols-outlined text-base">swap_vert</span>
                </h3>
                <div class="max-h-64 overflow-y-auto">
                    <div class="h-[1000px]">
                        <canvas id="rankBarChartProvinsi" class="w-full h-full"></canvas>
                    </div>
                </div>
            </div>

            <!-- Inflasi Kabupaten/Kota Bar -->
            <div class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-5">
                <h3 class="text-lg font-bold flex items-end gap-1">
                    Inflasi Kabupaten/Kota
                    <span class="material-symbols-outlined text-base">swap_vert</span>
                </h3>
                <div class="max-h-64 overflow-y-auto">
                    <div class="h-[1000px]">
                        <canvas id="rankBarChartProvinsi2" class="w-full h-full"></canvas>
                    </div>
                </div>
            </div>

            <!-- Big Div: Map Provinsi -->
            <div class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-10">
                <button onclick="toggleFullscreenMap('map')" class="absolute top-2 right-2 p-2 hover:text-gray-200 text:hover:bg-gray-700">
                    <span id="fullscreenIconMap" class="material-symbols-outlined text-xl">fullscreen</span>
                </button>
                <button class="absolute top-2 right-12 p-2 hover:text-gray-200 text:hover:bg-gray-700">
                    <span class="material-symbols-outlined text-xl">download</span>
                </button>
                <h3 class="text-lg font-bold">Map Provinsi</h3>
                <div id="map" class="h-64"></div>
            </div>

            <!-- Big Div: Map Kabkot -->
            <div class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-10">
                <button onclick="toggleFullscreenMap('map2')" class="absolute top-2 right-2 p-2 hover:text-gray-200 text:hover:bg-gray-700">
                    <span id="fullscreenIconMap2" class="material-symbols-outlined text-xl">fullscreen</span>
                </button>
                <button class="absolute top-2 right-12 p-2 hover:text-gray-200 text:hover:bg-gray-700">
                    <span class="material-symbols-outlined text-xl">download</span>
                </button>
                <h3 class="text-lg font-bold">Map Kabkot</h3>
                <div id="map2" class="h-64"></div>
            </div>
        </div>
    </div>

    <!-- Include Chart.js library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
     integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
     crossorigin=""/>

     <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
     integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
     crossorigin=""></script>

    <script>

        const mapDemo = {
        "97": 3.07,
        "96": 5.61,
        "95": 5.79,
        "94": 6.79,
        "92": 0.55,
        "91": 4.71,
        "82": 3.15,
        "81": 3.64,
        "76": 2.61,
        "75": 3.79,
        "74": 3.65,
        "73": 4.96,
        "72": 2.74,
        "71": 2.66,
        "65": 3.1,
        "64": 6.16,
        "63": 4.57,
        "62": 0.09,
        "61": 0.98,
        "53": 2.69,
        "52": 3.8,
        "51": 4.16,
        "36": 4.96,
        "35": 1.1,
        "34": 0.7,
        "33": 0.12,
        "32": 2.39,
        "31": 2.98,
        "21": 1.49,
        "19": 0.96,
        "18": 1.56,
        "17": 4.63,
        "16": 6.44,
        "15": 5.46,
        "14": 1.41,
        "13": 5.59,
        "12": 4.54,
        "11": 2.37
        }

        const mapDemo2 = {
        "9702": 5.0,
        "9604": 4.62,
        "9601": 2.4,
        "9501": 5.71,
        "9471": 6.08,
        "9271": 4.82,
        "9203": 1.82,
        "9202": 5.52,
        "9105": 2.12,
        "8271": 0.51,
        "8202": 5.26,
        "8172": 1.44,
        "8171": 1.42,
        "8103": 5.62,
        "7604": 1.9,
        "7472": 5.67,
        "7571": 4.48,
        "7502": 1.57,
        "7471": 0.59,
        "7404": 1.21,
        "7403": 6.93,
        "7373": 5.23,
        "7372": 1.13,
        "7371": 5.71,
        "7325": 1.92,
        "7314": 4.94,
        "7313": 2.78,
        "7311": 4.85,
        "7302": 0.3,
        "7271": 5.29,
        "7206": 2.35,
        "7203": 3.31,
        "7202": 4.18,
        "7174": 1.84,
        "7171": 2.05,
        "7106": 2.25,
        "7105": 1.85,
        "6571": 1.91,
        "6504": 5.36,
        "6502": 6.15,
        "6472": 4.63,
        "6471": 0.19,
        "6409": 4.24,
        "6405": 2.33,
        "6371": 0.16,
        "6309": 5.68,
        "6307": 4.73,
        "6302": 4.64,
        "6301": 1.82,
        "6271": 1.62,
        "6206": 4.67,
        "6203": 2.02,
        "6202": 5.63,
        "6172": 5.54,
        "6171": 3.4,
        "6111": 0.08,
        "6107": 6.2,
        "6106": 5.37,
        "5371": 3.66,
        "5312": 3.49,
        "5310": 0.64,
        "5304": 4.24,
        "5302": 4.21,
        "5272": 1.04,
        "5271": 2.39,
        "5204": 0.18,
        "5171": 4.57,
        "5108": 6.76,
        "5103": 4.22,
        "5102": 0.23,
        "3673": 6.56,
        "3672": 6.58,
        "3671": 3.64,
        "3602": 5.22,
        "3601": 5.3,
        "3578": 2.41,
        "3577": 6.05,
        "3574": 3.72,
        "3573": 1.53,
        "3571": 3.23,
        "3529": 1.21,
        "3525": 5.96,
        "3522": 0.21,
        "3510": 5.23,
        "3509": 0.68,
        "3504": 4.4,
        "3471": 4.03,
        "3403": 4.91,
        "3376": 6.4,
        "3374": 4.6,
        "3372": 5.71,
        "3319": 0.19,
        "3317": 1.32,
        "3312": 3.54,
        "3307": 2.05,
        "3302": 6.28,
        "3301": 6.45,
        "3278": 6.61,
        "3276": 5.76,
        "3275": 1.48,
        "3274": 4.86,
        "3273": 4.63,
        "3272": 4.14,
        "3271": 0.9,
        "3213": 2.29,
        "3210": 2.1,
        "3204": 4.01,
        "3100": 6.33,
        "2172": 3.05,
        "2171": 0.21,
        "2101": 1.77,
        "1971": 1.96,
        "1906": 4.81,
        "1903": 4.04,
        "1902": 1.65,
        "1872": 6.87,
        "1871": 1.35,
        "1811": 0.46,
        "1804": 1.69,
        "1771": 6.35,
        "1706": 6.58,
        "1674": 3.27,
        "1671": 3.37,
        "1603": 5.17,
        "1602": 5.06,
        "1571": 5.99,
        "1509": 4.51,
        "1501": 4.18,
        "1473": 2.34,
        "1471": 1.17,
        "1406": 1.97,
        "1403": 6.35,
        "1375": 1.97,
        "1371": 0.04,
        "1312": 2.28,
        "1311": 0.98,
        "1278": 4.71,
        "1277": 3.66,
        "1275": 1.13,
        "1273": 5.98,
        "1271": 2.46,
        "1212": 4.98,
        "1211": 4.76,
        "1207": 5.18,
        "1174": 3.28,
        "1171": 1.5,
        "1114": 4.49,
        "1107": 1.51,
        "1106": 0.19
        }

        // Initialize the map
        var map = L.map('map').setView([-2.5489, 118.0149], 5); // Centered on Indonesia

        // Add a tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        // Initialize the second map
        var map2 = L.map('map2').setView([-2.5489, 118.0149], 5); // Centered on Indonesia

        // Add a tile layer to the second map
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map2);

        // Load the GeoJSON file
        fetch("{{asset('geojson/Provinsi.json')}}")
            .then(response => response.json())
            .then(data => {
                // Loop through GeoJSON features and add inflation data dynamically
                data.features.forEach(feature => {
                    const regionCode = feature.properties.KODE_PROV;  // Get the region code from GeoJSON
                    const inflationRate = mapDemo[regionCode];  // Get the inflation rate from your data

                    // If inflation data exists for the province, add it to the feature properties
                    if (inflationRate !== undefined) {
                        feature.properties.inflation_rate = inflationRate;
                    } else {
                        feature.properties.inflation_rate = null;  // Or you can set a default value
                    }
                });

                L.geoJSON(data, {
                style: function (feature) {
                    return {
                        fillColor: getColor(feature.properties.inflation_rate),
                        weight: 1,
                        opacity: 1,
                        color: 'white',
                        dashArray: '3',
                        fillOpacity: 0.7
                    };
                },
                onEachFeature: function (feature, layer) {
                    // Add a popup showing the inflation data
                    layer.bindPopup('<strong>' + feature.properties.PROVINSI + '</strong><br>Inflation Rate: ' + feature.properties.inflation_rate);
                }
            }).addTo(map);
        })
        .catch(err => console.error('Error loading GeoJSON:', err));

        fetch("{{asset('geojson/kab_indo_dummy4.json')}}")
            .then(response => response.json())
            .then(data => {
                // Loop through GeoJSON features and add inflation data dynamically
                data.features.forEach(feature => {
                    const regionCode = feature.properties.idkab;  // Get the region code from GeoJSON
                    const inflationRate = mapDemo2[regionCode];  // Get the inflation rate from your data

                    // If inflation data exists for the province, add it to the feature properties
                    if (inflationRate !== undefined) {
                        feature.properties.inflation_rate = inflationRate;
                    } else {
                        feature.properties.inflation_rate = null;  // Or you can set a default value
                    }
                });

                L.geoJSON(data, {
                style: function (feature) {
                    return {
                        fillColor: getColor2(feature.properties.inflation_rate),
                        weight: 1,
                        opacity: 1,
                        color: 'white',
                        dashArray: '3',
                        fillOpacity: 0.7
                    };
                },
                onEachFeature: function (feature, layer) {
                    // Add a popup showing the inflation data
                    layer.bindPopup('<strong>' + feature.properties.nmkab + '</strong><br>Inflation Rate: ' + feature.properties.inflation_rate);
                }
            }).addTo(map2);
        })
        .catch(err => console.error('Error loading GeoJSON:', err));

    // Color function for choropleth (adjust according to inflation rates)
    function getColor(value) {
        return value > 6 ? '#800026' :
            value > 5 ? '#BD0026' :
            value > 4 ? '#E31A1C' :
            value > 3 ? '#FC4E2A' :
            value > 2 ? '#FD8D3C' :
            value > 0 ? '#800026':
                        '#FEB24C';
    }

    function getColor2(value) {
        return value > 0 ? '#800026':
                        '#FEB24C';
    }
    </script>

    <script>

        document.addEventListener('alpine:init', () => {
            Alpine.data('webData', () => ({
                provinces: @json($wilayah->where('flag', 2)->values()), // Load all provinces
                kabkots: @json($wilayah->where('flag', 3)->values()), // Load all kab/kot
                selectedProvince: {},
                selectedKabkot: '',
                dropdowns: { province: false },

                nasional: false,
                kd_wilayah: '',

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

        const labels = ['November 2024', 'December 2024', 'January 2025', 'February 2025', 'March 2025'];

        const datasets = [
            {
                label: 'Harga Produsen',
                inflasi: [5.5, 3.0, 2.8, 3.2, -3.1], // Inflasi values
                andil: [0.5, 0.6, 0.55, 0.65, 0.6], // Corresponding andil values
                stacked: [20, 30, 50]
            },
            {
                label: 'Harga Produsen Desa',
                inflasi: [3.8, 2.1, 2.0, 2.3, 2.2],
                andil: [0.4, 0.45, 0.43, 0.47, 0.44],
                stacked: [20, 30, 50]
            },
            {
                label: 'Harga Perdagangan Besar',
                inflasi: [2.5, -3.0, 2.8, -3.2, -3.1], // Inflasi values
                andil: [0.5, 0.80, 0.34, 0.15, 0.6], // Corresponding andil values
                stacked: [20, 30, 50]
            },
            {
                label: 'Harga Konsumen Desa',
                inflasi: [-1.8, 3.1, 4.0, 2.9, 7.2],
                andil: [0.4, 0.30, 0.68, 0.25, 0.43],
                stacked: [20, 30, 50]
            },
            {
                label: 'Harga Konsumen Kota',
                inflasi: [2.5, 3.0, 7.0, 3.2, 3.1], // Inflasi values
                andil: [0.22, 0.52, 0.32, 0.65, 0.6], // Corresponding andil values
                stacked: [20, 30, 50]
            },
            // Add more datasets for other price levels as needed
        ];

        const provinsi = [
            "ACEH", "SUMATERA UTARA", "SUMATERA BARAT", "RIAU", "JAMBI", "SUMATERA SELATAN", "BENGKULU",
            "LAMPUNG", "KEPULAUAN BANGKA BELITUNG", "KEPULAUAN RIAU", "DKI JAKARTA", "JAWA BARAT",
            "JAWA TENGAH", "DI YOGYAKARTA", "JAWA TIMUR", "BANTEN", "BALI", "NUSA TENGGARA BARAT",
            "NUSA TENGGARA TIMUR", "KALIMANTAN BARAT", "KALIMANTAN TENGAH", "KALIMANTAN SELATAN",
            "KALIMANTAN TIMUR", "KALIMANTAN UTARA", "SULAWESI UTARA", "SULAWESI TENGAH", "SULAWESI SELATAN",
            "SULAWESI TENGGARA", "GORONTALO", "SULAWESI BARAT", "MALUKU", "MALUKU UTARA", "PAPUA BARAT",
            "PAPUA BARAT DAYA", "PAPUA", "PAPUA SELATAN", "PAPUA TENGAH", "PAPUA PEGUNUNGAN"
        ];

        const kota = [
            "KAB JAYAWIJAYA", "KAB NABIRE", "TIMIKA", "MERAUKE", "KOTA JAYAPURA", "KOTA SORONG",
            "KAB SORONG SELATAN", "KAB SORONG", "MANOKWARI", "KOTA TERNATE", "KAB HALMAHERA TENGAH",
            "KOTA TUAL", "KOTA AMBON", "KAB MALUKU TENGAH", "MAMUJU", "KAB MAJENE", "KOTA GORONTALO",
            "KAB GORONTALO", "KOTA BAU BAU", "KOTA KENDARI", "KAB KOLAKA", "KAB KONAWE", "KOTA PALOPO",
            "KOTA PARE PARE", "KOTA MAKASSAR", "KAB LUWU TIMUR", "KAB SIDENRENG RAPPANG", "KAB WAJO",
            "WATAMPONE", "BULUKUMBA", "KOTA PALU", "KAB TOLI TOLI", "KAB MOROWALI", "LUWUK",
            "KOTA KOTAMOBAGU", "KOTA MANADO", "KAB MINAHASA UTARA", "KAB MINAHASA SELATAN", "KOTA TARAKAN",
            "KAB NUNUKAN", "TANJUNG SELOR", "KOTA SAMARINDA", "KOTA BALIKPAPAN", "KAB PENAJAM PASER UTARA",
            "KAB BERAU", "KOTA BANJARMASIN", "TANJUNG", "KAB HULU SUNGAI TENGAH", "KOTABARU",
            "KAB TANAH LAUT", "KOTA PALANGKARAYA", "KAB SUKAMARA", "KAB KAPUAS", "SAMPIT",
            "KOTA SINGKAWANG", "KOTA PONTIANAK", "KAB KAYONG UTARA", "SINTANG", "KAB KETAPANG",
            "KOTA KUPANG", "KAB NGADA", "MAUMERE", "KAB TIMOR TENGAH SELATAN", "WAINGAPU",
            "KOTA BIMA", "KOTA MATARAM", "KAB SUMBAWA", "KOTA DENPASAR", "SINGARAJA", "KAB BADUNG",
            "KAB TABANAN", "KOTA SERANG", "KOTA CILEGON", "KOTA TANGERANG", "KAB LEBAK", "KAB PANDEGLANG",
            "KOTA SURABAYA", "KOTA MADIUN", "KOTA PROBOLINGGO", "KOTA MALANG", "KOTA KEDIRI", "SUMENEP",
            "KAB GRESIK", "KAB BOJONEGORO", "BANYUWANGI", "JEMBER", "KAB TULUNGAGUNG", "KOTA YOGYAKARTA",
            "KAB GUNUNGKIDUL", "KOTA TEGAL", "KOTA SEMARANG", "KOTA SURAKARTA", "KUDUS", "KAB REMBANG",
            "KAB WONOGIRI", "KAB WONOSOBO", "PURWOKERTO", "CILACAP", "KOTA TASIKMALAYA", "KOTA DEPOK",
            "KOTA BEKASI", "KOTA CIREBON", "KOTA BANDUNG", "KOTA SUKABUMI", "KOTA BOGOR", "KAB SUBANG",
            "KAB MAJALENGKA", "KAB BANDUNG", "DKI JAKARTA", "KOTA TANJUNG PINANG", "KOTA BATAM",
            "KAB KARIMUN", "KOTA PANGKAL PINANG", "KAB BELITUNG TIMUR", "KAB BANGKA BARAT", "TANJUNG PANDAN",
            "KOTA METRO", "KOTA BANDAR LAMPUNG", "KAB MESUJI", "KAB LAMPUNG TIMUR", "KOTA BENGKULU",
            "KAB MUKO MUKO", "KOTA LUBUK LINGGAU", "KOTA PALEMBANG", "KAB MUARA ENIM", "KAB OGAN KOMERING ILIR",
            "KOTA JAMBI", "MUARA BUNGO", "KAB KERINCI", "KOTA DUMAI", "KOTA PEKANBARU", "KAB KAMPAR",
            "TEMBILAHAN", "KOTA BUKITTINGGI", "KOTA PADANG", "KAB PASAMAN BARAT", "KAB DHARMASRAYA",
            "KOTA GUNUNGSITOLI", "KOTA PADANGSIDIMPUAN", "KOTA MEDAN", "KOTA PEMATANG SIANTAR", "KOTA SIBOLGA",
            "KAB DELI SERDANG", "KAB KARO", "KAB LABUHANBATU", "KOTA LHOKSEUMAWE", "KOTA BANDA ACEH",
            "KAB ACEH TAMIANG", "MEULABOH", "KAB ACEH TENGAH"
        ];

        const inflasi_provinsi = [
            2.5, 3.1, 2.8, 3.3, 2.9, 3.5, 2.7, -3.0, 3.2, 2.6,
            2.9, 3.4, 3.1, 2.7, 3.6, 2.8, 3.3, 3.0, 2.5, 3.2,
            3.1, 2.9, 3.5, -2.8, 3.0, 2.7, 3.3, 3.1, 2.6, 3.4,
            2.9, 3.0, 2.5, 2.8, 3.2, 3.1, -2.7, 3.5
        ];

        const chartData = {
            labels: [
                "Harga Produsen",
                "Harga Produsen Desa",
                "Harga Perdagangan Besar",
                "Harga Konsumen Desa",
                "Harga Konsumen Kota"
            ],
            datasets: [
                {
                    label: "Naik (↑)",
                    data: [20, 47, 35, 20, 30], // Values for each harga level
                    backgroundColor: "#36a2eb", // Blue
                    stack: "stack1"
                },
                {
                    label: "Stabil (-)",
                    data: [35, 30, 40, 22, 30], // Values for each harga level
                    backgroundColor: "#4bc0c0", // Green
                    stack: "stack1"
                },
                {
                    label: "Menurun (↓)",
                    data: [45, 4, 25, 50, 40], // Values for each harga level
                    backgroundColor: "red", // Red
                    stack: "stack1"
                },
            ]
        };

        // Get the context of the canvas element
        var ctx = document.getElementById('multiAxisChart').getContext('2d');
        var hbx = document.getElementById('horizontalBarChart').getContext('2d');
        var sbx = document.getElementById('stackedBarChart').getContext('2d');

        // Create the chart
        var multiAxisChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: datasets.map(dataset => ({
                    label: dataset.label,
                    data: dataset.inflasi,
                    inflasi: dataset.inflasi,
                    andil: dataset.andil,
                }))
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'point',
                    intersect: false
                },
                title: function() {
                        return '';
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const dataset = context.dataset;
                                const inflasi = dataset.inflasi[context.dataIndex];
                                const andil = dataset.andil[context.dataIndex];
                                return [
                                    `${dataset.label}`,
                                    `Inflasi = ${inflasi}%`,
                                    `Andil = ${andil}%`
                                ];
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Inflasi (%)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Month'
                        }
                    }
                }
            }
        });

        var latestMonthIndex = labels.length - 1;

        var horizontalBarChart = new Chart(hbx, {
            type: 'bar',
            options: {
                indexAxis: 'y',
                // Elements options apply to all of the options unless overridden in a dataset
                // In this case, we are setting the border of each horizontal bar to be 2px wide
                elements: {
                    bar: {
                        borderWidth: 2,
                    },
                },
            },
            data: {
                labels: datasets.map(dataset => dataset.label),
                datasets: [
                    {
                        label: 'Inflasi',
                        data: datasets.map(dataset => dataset.inflasi[latestMonthIndex]), // Data inflasi
                    },
                    {
                        label: 'Andil',
                        data: datasets.map(dataset => dataset.andil[latestMonthIndex]), // Data andil
                    },
                ],
            },
        });

        function showInflasiLine() {

            multiAxisChart.data.datasets = datasets.map(dataset => ({
                label: dataset.label,
                data: dataset.inflasi,
                inflasi: dataset.inflasi,
                andil: dataset.andil,
            }))
            multiAxisChart.update();
        }

        function showAndilLine() {
            multiAxisChart.data.datasets = datasets.map(dataset => ({
                label: dataset.label,
                data: dataset.andil,
                inflasi: dataset.inflasi,
                andil: dataset.andil,
            }));
            multiAxisChart.update();
        }

        function showBothLine() {
            multiAxisChart.data.datasets = datasets.flatMap(dataset => [
                {
                    label: `${dataset.label} - Inflasi`,
                    data: dataset.inflasi,
                    inflasi: dataset.inflasi,
                    andil: dataset.andil,
                    borderColor: "#4FCFCF",
                },
                {
                    label: `${dataset.label} - Andil`,
                    data: dataset.andil,
                    inflasi: dataset.inflasi,
                    andil: dataset.andil,
                    borderColor: 'gray',
                }
            ]);
            multiAxisChart.update();
        }

        function toggleFullscreen(canvasId) {
            const canvas = document.getElementById(canvasId);
            const fullscreenIcon = document.getElementById(`fullscreenIcon${canvasId.charAt(0).toUpperCase() + canvasId.slice(1)}`);
            const container = canvas.parentElement; // Get the parent div

            if (!document.fullscreenElement) {
                container.requestFullscreen().catch(err => {
                    console.log(`Error trying to enable fullscreen: ${err.message}`);
                });
                canvas.classList.remove("max-h-96"); // Remove height limit in fullscreen
                fullscreenIcon.textContent = 'close_fullscreen'; // Change icon to close
            } else {
                document.exitFullscreen();
                canvas.classList.add("max-h-96"); // Restore height limit when exiting fullscreen
                fullscreenIcon.textContent = 'fullscreen'; // Change icon back to fullscreen
            }
        }

        function toggleFullscreenMap(mapId) {
            const mapElement = document.getElementById(mapId);
            const fullscreenIcon = document.getElementById(`fullscreenIcon${mapId.charAt(0).toUpperCase() + mapId.slice(1)}`);
            const container = mapElement.parentElement; // Get the parent div

            if (!document.fullscreenElement) {
                container.requestFullscreen().catch(err => {
                    console.log(`Error trying to enable fullscreen: ${err.message}`);
                });
                mapElement.classList.remove("h-64"); // Remove height limit in fullscreen
                mapElement.style.width = "100%";
                mapElement.style.height = "100vh"; // Set height to full viewport height
                fullscreenIcon.textContent = 'close_fullscreen'; // Change icon to close
            } else {
                document.exitFullscreen();
                mapElement.classList.add("h-64"); // Restore height limit when exiting fullscreen
                mapElement.style.width = "";
                mapElement.style.height = "";
                fullscreenIcon.textContent = 'fullscreen'; // Change icon back to fullscreen
            }
        }

        var stackedBarChart = new Chart(sbx, {
            type: "bar",
            data: chartData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                },
                scales: {
                    x: { stacked: true },
                    y: { stacked: true, beginAtZero: true, max: 100 }
                }
            }
        });

        // Generate random inflation values
        const provinsiInflasi = provinsi.map(() => (Math.random() * 10).toFixed(2));

        // Combine provinsi with their respective inflasi values
        const sortedData = provinsi
            .map((name, index) => ({ name, value: parseFloat(provinsiInflasi[index]) }))
            .sort((a, b) => b.value - a.value); // Sort in descending order

        // Extract sorted labels and data
        const sortedProvinsi = sortedData.map(item => item.name);
        const sortedInflasi = sortedData.map(item => item.value);

        const kabkotInflasi = kota.map(() => (Math.random() * 10).toFixed(2));

        var rankBarChartProvinsi = new Chart(document.getElementById('rankBarChartProvinsi').getContext('2d'), {
            type: 'bar',
            options: {
                indexAxis: 'y',
                elements: {
                    bar: {
                        borderWidth: 2,
                    },
                },
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: (context) => `Inflasi: ${context.raw.toFixed(2)}%`, // Fix tooltip display
                        },
                    },
                },
            },
            data: {
                labels: provinsi,
                datasets: [
                    {
                        label: sortedProvinsi,
                        data: sortedInflasi,
                    },
                ],
            },
        });

        var rankBarChartProvinsi2 = new Chart(document.getElementById('rankBarChartProvinsi2').getContext('2d'), {
            type: 'bar',
            options: {
                indexAxis: 'y',
                elements: {
                    bar: {
                        borderWidth: 2,
                    },
                },
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: (context) => `Inflasi: ${context.raw.toFixed(2)}%`, // Fix tooltip display
                        },
                    },
                },
            },
            data: {
                labels: provinsi,
                datasets: [
                    {
                        label: sortedProvinsi,
                        data: sortedInflasi,
                    },
                ],
            },
        });

        var rankBarChartKabkot = new Chart(document.getElementById('rankBarChartKabkot').getContext('2d'), {
            type: 'bar',
            options: {
                indexAxis: 'y',
                elements: {
                    bar: {
                        borderWidth: 2,
                    },
                },
                responsive: true,
                maintainAspectRatio: true,
            },
            data: {
                labels: kota,
                datasets: [
                    {
                        label: 'Inflasi',
                        data: kabkotInflasi,
                        backgroundColor: '#36a2eb',
                    },
                ],
            },
        });

    </script>
</x-two-panel-layout>
