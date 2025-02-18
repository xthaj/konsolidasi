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

    <div id="visualizationCanvas" class="w-full p-4 md:overflow-y-auto md:h-full transition-all duration-300 dark:bg-gray-900" :class="{ 'md:w-full': !isBuilderVisible }">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-white  md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 lg:col-span-2 xl:col-span-3">
                <h3 class="text-lg font-bold">Inflasi</h3>
                <div class="flex justify-end space-x-2 mb-2">
                    <button onclick="showInflasiLine()" class="bg-blue-500 text-white px-4 py-2 rounded">Show Inflasi</button>
                    <button onclick="showAndilLine()" class="bg-green-500 text-white px-4 py-2 rounded">Show Andil</button>
                    <button onclick="showBothLine()" class="bg-gray-500 text-white px-4 py-2 rounded">Show Both</button>
                </div>
                <canvas id="multiAxisChart" class="w-full max-h-96 md:h-auto"></canvas>
            </div>

            <div class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 lg:col-span-2 xl:col-span-3">
                <h3 class="text-lg font-bold">Inflasi & Andil Maret 2025</h3>
                <canvas id="horizontalBarChart" class="w-full max-h-96 md:h-auto"></canvas>
            </div>

            <div class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 lg:col-span-2 xl:col-span-3">
                <h3 class="text-lg font-bold">Jumlah Provinsi berdasarkan Arah Inflasi</h3>
                <canvas id="stackedBarChart" class="w-full max-h-96 md:h-auto"></canvas>
            </div>

            <!-- <div class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 lg:col-span-2 xl:col-span-3">
                <h3 class="text-lg font-bold">Peringkat inflasi</h3>
                <div class="overflow-y-auto max-h-96">
                    <canvas id="rankBarChart" class="w-full"></canvas>
                </div>
            </div> -->

            <div class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 ">
                <h3 class="text-lg font-bold">Inflasi Provinsi</h3>
                <!-- <div class="overflow-x-auto"> -->
                <div class="max-h-64 overflow-y-auto">
                    <div class="h-[1000px]"> <!-- Set a large height to make it scrollable -->
                        <canvas id="rankBarChartProvinsi" class="w-full h-full"></canvas>
                    </div>
                </div>
                <!-- </div> -->
            </div>
            <div class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800">
                <h3 class="text-lg font-bold">Inflasi Kabupaten/Kota</h3>
                <!-- <div class="overflow-x-auto"> -->
                    <canvas id="rankBarChartKabkot" class="w-full max-h-96 md:h-auto"></canvas>
                <!-- </div> -->
            </div>

            <div class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 lg:col-span-2 xl:col-span-3">
                <h3 class="text-lg font-bold">Map</h3>
            </div>
        </div>
    </div>

    <!-- Include Chart.js library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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

        const labels = ['November 2024', 'December 2024', 'January 2025', 'February 2025', 'March 2025'];

        const datasets = [
            {
                label: 'Harga Produsen',
                inflasi: [5.5, 3.0, 2.8, 3.2, -3.1], // Inflasi values
                andil: [0.5, 0.6, 0.55, 0.65, 0.6], // Corresponding andil values
                stacked:[20,30,50]
            },
            {
                label: 'Harga Produsen Desa',
                inflasi: [3.8, 2.1, 2.0, 2.3, 2.2],
                andil: [0.4, 0.45, 0.43, 0.47, 0.44],
                stacked:[20,30,50]

            },
            {
                label: 'Harga Perdagangan Besar',
                inflasi: [2.5, -3.0, 2.8, -3.2, -3.1], // Inflasi values
                andil: [0.5, 0.80, 0.34, 0.15, 0.6], // Corresponding andil values
                stacked:[20,30,50]

            },
            {
                label: 'Harga Konsumen Desa',
                inflasi: [-1.8, 3.1, 4.0, 2.9, 7.2],
                andil: [0.4, 0.30, 0.68, 0.25, 0.43],
                stacked:[20,30,50]

            },
            {
                label: 'Harga Konsumen Kota',
                inflasi: [2.5, 3.0, 7.0, 3.2, 3.1], // Inflasi values
                andil: [0.22, 0.52, 0.32, 0.65, 0.6], // Corresponding andil values
                stacked:[20,30,50]

            },
            // Add more datasets for other price levels as needed
        ];

        const dataset3 = [

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
                    label: "Menurun (↓)",
                    data: [45, 30, 25, 50, 40], // Values for each harga level
                    backgroundColor: "#ff6384", // Red
                    stack: "stack1"
                },
                {
                    label: "Naik (↑)",
                    data: [20, 40, 35, 20, 30], // Values for each harga level
                    backgroundColor: "#36a2eb", // Blue
                    stack: "stack1"
                },
                {
                    label: "Stabil (-)",
                    data: [35, 30, 40, 30, 30], // Values for each harga level
                    backgroundColor: "#4bc0c0", // Green
                    stack: "stack1"
                }
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
                        position: 'bottom', // Positions the legend at the bottom
                        // Additional legend configurations can be added here
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

        const provinsiInflasi = provinsi.map(() => (Math.random() * 10).toFixed(2));
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
            },
            data: {
                labels: provinsi,
                datasets: [
                    {
                        label: 'Inflasi',
                        data: provinsiInflasi,
                        backgroundColor: '#4bc0c0',
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
