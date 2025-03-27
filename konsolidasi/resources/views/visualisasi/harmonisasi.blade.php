<x-two-panel-layout>

    @section('vite')
    @vite(['resources/css/app.css', 'resources/js/alpine-init.js', 'resources/js/harmonisasi.js'])
    @endsection

    <script src="https://cdn.jsdelivr.net/npm/echarts@latest/dist/echarts.min.js"></script>

    <!-- Modals -->
    <x-modal name="data-not-found" focusable title="Data Tidak Ditemukan">
        <div class="px-6 py-4">
            <!-- <div>
                <p class="text-red-600">Beberapa data tidak ditemukan:</p>
                <ul class="list-disc pl-5 mt-2">
                    <template x-for="missing in modalContent.missingItems" :key="missing">
                        <li x-text="missing"></li>
                    </template>
                </ul>
            </div> -->
            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
            </div>
        </div>
    </x-modal>

    <x-slot name="sidebar">
        <div id="vizBuilderPanel" class="space-y-4 md:space-y-6 mt-4">
            <form action="{{ route('visualisasi.create') }}">
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
                            <template x-for="komoditi in komoditas" :key="komoditi.kd_komoditas">
                                <option :value="komoditi.kd_komoditas" x-text="komoditi.nama_komoditas" :selected="komoditi.kd_komoditas == '{{ request('kd_komoditas') }}'"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Buttons -->
                    <div class="mt-4">
                        <!-- Helper text for validation -->
                        <div x-show="!checkFormValidity()" class="my-2 text-sm text-red-600">
                            <span x-text="getValidationMessage()"></span>
                        </div>

                        <button type="submit"
                            :disabled="!checkFormValidity()" type="submit" class="w-full bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:outline-none focus:ring-primary-300 text-white font-medium rounded-lg text-sm px-5 py-2.5 text-center">Tampilkan</button>
                        </button>
                    </div>
            </form>
        </div>
    </x-slot>

    <div id="visualizationCanvas" class="w-full p-4 md:overflow-y-auto md:h-full transition-all duration-300 dark:bg-gray-900">
        <div class="grid grid-cols-1 md:grid-cols-10 gap-4">
            <div class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-10">
                <h2>{{ $title }}</h2>
            </div>

            <!-- Display message if present -->
            @if(!empty($errors))
            <div id="alert-box" class="col-span-1 md:col-span-10 p-4 bg-yellow-100 border border-yellow-400 text-yellow-700 rounded-lg relative">
                @if(!empty($message))
                <p>{{ $message }}</p> <!-- Display general message if set -->
                @endif
                <ul class="flex flex-wrap gap-2">
                    @foreach($errors as $error)
                    <li>
                        <p>
                            {{ $error }}
                        </p>
                    </li>
                    @endforeach
                </ul>
                <!-- Dismiss button outside the loop -->
                <button type="button" class="absolute top-2 right-2 p-1 text-yellow-400 bg-transparent rounded-full hover:bg-yellow-200 hover:text-yellow-900"
                    data-dismiss-target="#alert-box" aria-label="Close">
                    <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                    </svg>
                    <span class="sr-only">Close alert</span>
                </button>
            </div>
            @endif


            <!-- Stacked Line Chart -->
            @if(!empty($data['stackedLine']))
            <div class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-10">
                <div>
                    <div id="stackedLineChartParent">
                        <div id="stackedLineChart" class="w-full h-96"></div>
                    </div>
                </div>
                <button id="toggleAndilBtn"
                    class="block mx-auto mt-4 font-semibold underline">
                    Lihat Andil
                </button>
            </div>
            @endif

            <div class="bg-white p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-2">
                <h4 class="text-md font-semibold">Harga Konsumen Kota</h4>
                <p>Inflasi: <span class="font-bold">{{ number_format($data['summary']['Harga Konsumen Kota']['inflasi'] ?? 0, 2) }}%</span></p>
                <p>Andil: <span class="font-bold">{{ number_format($data['summary']['Harga Konsumen Kota']['andil'] ?? 0, 2) }}%</span></p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-2">
                <h4 class="text-md font-semibold">Harga Konsumen Desa</h4>
                <p>Inflasi: <span class="font-bold">{{ number_format($data['summary']['Harga Konsumen Desa']['inflasi'] ?? 0, 2) }}%</span></p>
                <p>Andil: <span class="font-bold">{{ number_format($data['summary']['Harga Konsumen Desa']['andil'] ?? 0, 2) }}%</span></p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-2">
                <h4 class="text-md font-semibold">Harga Perdagangan Besar</h4>
                <p>Inflasi: <span class="font-bold">{{ number_format($data['summary']['Harga Perdagangan Besar']['inflasi'] ?? 0, 2) }}%</span></p>
                <p>Andil: <span class="font-bold">{{ number_format($data['summary']['Harga Perdagangan Besar']['andil'] ?? 0, 2) }}%</span></p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-2">
                <h4 class="text-md font-semibold">Harga Produsen Desa</h4>
                <p>Inflasi: <span class="font-bold">{{ number_format($data['summary']['Harga Produsen Desa']['inflasi'] ?? 0, 2) }}%</span></p>
                <p>Andil: <span class="font-bold">{{ number_format($data['summary']['Harga Produsen Desa']['andil'] ?? 0, 2) }}%</span></p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-2">
                <h4 class="text-md font-semibold">Harga Produsen</h4>
                <p>Inflasi: <span class="font-bold">{{ number_format($data['summary']['Harga Produsen']['inflasi'] ?? 0, 2) }}%</span></p>
                <p>Andil: <span class="font-bold">{{ number_format($data['summary']['Harga Produsen']['andil'] ?? 0, 2) }}%</span></p>
            </div>
            <!-- Horizontal Bar Chart -->
            @if(!empty($data['horizontalBar']))
            <div id="horizontalBarChartParent" class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-10">
                <div id="horizontalBarChart" class="w-full h-96"></div>
            </div>
            @endif

            <!-- Heatmap Chart -->
            @if(!empty($data['heatmap']))
            <div id="heatmapChartParent" class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-10">
                <div id="heatmapChart" class="w-full h-[550px]"></div>

            </div>
            @endif

            <!-- Bar Charts Container -->
            @if(!empty($data['heatmap']))
            <div id="barChartsContainerParent" class="bg-white p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-10">
                <div id="barChartsContainer" class="w-full h-96"></div>
            </div>
            @endif

            <!-- Stacked Bar Chart -->
            @if(!empty($data['stackedBar']))
            <div id="stackedBarChartParent" class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-10">
                <div id="stackedBarChart" class="w-full h-96"></div> <!-- Fixed syntax: `width=100%` → `w-full` -->
            </div>
            @endif

            <div class="flex gap-8 items-center bg-primary-700 md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-10">
                <h3 class="flex-1 text-white text-lg font-bold">Inflasi</h3>
                <select id="levelHargaSelect" x-model="selectedLevel" class="w-64 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="HK">Harga Konsumen Kota</option>
                    <option value="HD">Harga Konsumen Desa</option>
                    <option value="HPB">Harga Perdagangan Besar</option>
                    <option value="HPD">Harga Produsen Desa</option>
                    <option value="HP">Harga Produsen</option>
                </select>
            </div>

            @if(!empty($data['provHorizontalBar']) && !empty($data['kabkotHorizontalBar']))
            <div id="provHorizontalBarContainer" :class="selectedLevel === 'HK' ? 'col-span-5' : 'col-span-10'" class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800">
                <div id="provHorizontalBarChart" class="min-w-[350px] h-[550px]"></div>
            </div>
            <div id="kabkotHorizontalBarContainer" x-show="selectedLevel === 'HK'" class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-5">
                <div id="kabkotHorizontalBarChart" class="min-w-[350px] h-[550px]"></div>
            </div>
            @endif
            <div class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-10">
                <div id="provinsiChoropleth" class="w-full h-96"></div>
            </div>
            <div id="kabkotChoroplethContainer" x-show="selectedLevel === 'HK'" class="bg-white md:h-auto p-4 rounded-lg shadow-md relative dark:bg-gray-800 col-span-1 md:col-span-10">
                <div id="kabkotChoropleth" class="w-full h-96"></div>
            </div>

        </div>

        <script>
            window.chartTitle = @json($title);
            @if(isset($data['stackedLine']))
            window.stackedLineData = @json($data['stackedLine']);
            @else
            window.stackedLineData = null;
            @endif
            @if(isset($data['horizontalBar']))
            window.horizontalBarData = @json($data['horizontalBar']);
            @else
            window.horizontalBarData = null;
            @endif
            @if(isset($data['heatmap']))
            window.heatmapData = @json($data['heatmap']);
            heatMapValues = heatmapData.values.map((item) => [
                item[0],
                item[1],
                item[2] || "-",
            ]);
            console.log('Heatmap Values:', heatMapValues);
            @else
            window.heatmapData = null;
            @endif

            @if(isset($data['barCharts']))
            window.barChartsData = @json($data['barCharts']);
            @else
            window.barChartsData = null;
            @endif

            @if(isset($data['stackedBar']))
            window.stackedBarData = @json($data['stackedBar']);
            @else
            window.stackedBarData = null;
            @endif
            console.log('Stacked Line Data:', window.stackedLineData);
            console.log('Horizontal Bar Data:', window.horizontalBarData);
            console.log('Heatmap Data:', window.heatmapData);

            @if(!empty($data['errors']))
            document.addEventListener('DOMContentLoaded', () => {
                window.modalContent = {
                    missingItems: @json($data['errors'])
                };
                window.dispatchEvent(new CustomEvent('open-modal', {
                    detail: 'data-not-found'
                }));
            });
            @endif

            @if(isset($data['provHorizontalBar']))
            window.provHorizontalBarData = @json($data['provHorizontalBar']);
            @else
            window.provHorizontalBarData = null;
            @endif

            @if(isset($data['kabkotHorizontalBar']))
            window.kabkotHorizontalBarData = @json($data['kabkotHorizontalBar']);
            @else
            window.kabkotHorizontalBarData = null;
            @endif
        </script>
</x-two-panel-layout>