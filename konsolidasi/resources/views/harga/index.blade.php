<x-two-panel-layout>
    @section('vite')
    @vite(['resources/css/app.css', 'resources/js/harga.js'])
    @endsection

    <script src="https://cdn.jsdelivr.net/npm/echarts@latest/dist/echarts.min.js"></script>

    <x-slot name="sidebar">
        <form id="filter-form" x-ref="filterForm" @submit.prevent="fetchData">
            <div class="space-y-4 md:space-y-6 mt-4">
                <div class="flex gap-4">
                    <div class="w-1/2">
                        <label class="block mb-2 text-sm font-medium text-gray-900">Bulan</label>
                        <select name="bulan" x-model="bulan" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                            <template x-for="[nama, bln] in bulanOptions" :key="bln">
                                <option :value="bln" :selected="bulan == bln" x-text="nama"></option>
                            </template>
                        </select>
                    </div>
                    <div class="w-1/2">
                        <label class="block mb-2 text-sm font-medium text-gray-900">Tahun</label>
                        <select name="tahun" x-model="tahun" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                            <template x-for="year in tahunOptions" :key="year">
                                <option :value="year" :selected="year == tahun" x-text="year"></option>
                            </template>
                        </select>
                    </div>
                </div>
                <p x-show="isActivePeriod" class="text-sm text-gray-500">Periode aktif</p>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">Komoditas</label>
                    <select name="kd_komoditas" x-model="selectedKomoditas" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <template x-for="komoditi in komoditas" :key="komoditi.kd_komoditas">
                            <option :value="komoditi.kd_komoditas" x-text="komoditi.nama_komoditas" :selected="komoditi.kd_komoditas == selectedKomoditas"></option>
                        </template>
                    </select>
                    <div class="flex items-center justify-between mt-2 gap-2">
                        <button type="button" @click="stepKomoditas(-1)"
                            :disabled="currentKomoditasIndex <= 0"
                            :title="prevKomoditasName"
                            class="flex-1 flex items-center justify-center gap-1 px-2 py-1.5 text-xs rounded-lg border border-gray-300 bg-gray-50 hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed transition">
                            <span class="material-symbols-rounded text-base">chevron_left</span>
                            <span class="truncate max-w-[80px]" x-text="prevKomoditasName || '-'"></span>
                        </button>
                        <button type="button" @click="stepKomoditas(1)"
                            :disabled="currentKomoditasIndex >= komoditas.length - 1"
                            :title="nextKomoditasName"
                            class="flex-1 flex items-center justify-center gap-1 px-2 py-1.5 text-xs rounded-lg border border-gray-300 bg-gray-50 hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed transition">
                            <span class="truncate max-w-[80px]" x-text="nextKomoditasName || '-'"></span>
                            <span class="material-symbols-rounded text-base">chevron_right</span>
                        </button>
                    </div>
                </div>

                <x-primary-button type="submit" class="w-full">Filter</x-primary-button>
            </div>
        </form>
    </x-slot>

    <div class="w-full md:overflow-y-auto md:h-full transition-all duration-300 p-4">
        <div class="grid grid-cols-1 md:grid-cols-10 gap-4">

            <!-- Title -->
            <div class="bg-white p-4 rounded-lg shadow-md col-span-10">
                <h2 x-text="data?.title || 'Loading...'" class="text-gray-900"></h2>
            </div>

            <!-- Line Chart -->
            <div class="bg-white p-4 rounded-lg shadow-md col-span-10">
                <h3 class="text-lg font-semibold mb-4" x-text="data?.chart_status?.line?.title || 'Tren Harga'"></h3>
                <div id="lineChart" class="chart-container w-full h-96 mx-auto"></div>
            </div>

            <!-- Summary Boxes -->
            <template x-for="priceLevel in priceLevels" :key="priceLevel">
                <div class="bg-white p-4 rounded-lg shadow-md col-span-10 md:col-span-2 border-l-8"
                    :style="`border-left-color: ${colors[priceLevel] || '#5470C6'}`">
                    <h4 class="text-md font-semibold text-gray-800" x-text="priceLevel"></h4>
                    <p class="text-gray-600">Harga: <span class="font-bold text-gray-900" x-text="formatRupiah(summaryData?.[priceLevel]?.harga)"></span></p>
                    <p class="text-gray-600">Rentang Bawah: <span class="font-bold text-gray-900" x-text="formatRupiah(summaryData?.[priceLevel]?.rentang_bawah)"></span></p>
                    <p class="text-gray-600">Rentang Atas: <span class="font-bold text-gray-900" x-text="formatRupiah(summaryData?.[priceLevel]?.rentang_atas)"></span></p>
                </div>
            </template>

            <!-- Accordions -->
            <div class="col-span-10 flex flex-col gap-4">

                <!-- 05 - Harga Produsen -->
                <div x-data="{ open: true }">
                    <button type="button" @click="open = !open"
                        class="flex items-center justify-between w-full p-4 font-bold text-white rounded-lg gap-3"
                        style="background-color: #FC8452;">
                        <span>Harga Produsen</span>
                        <span class="material-symbols-rounded shrink-0" x-text="open ? 'keyboard_arrow_up' : 'keyboard_arrow_down'"></span>
                    </button>
                    <div x-show="open" x-collapse>
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-4 pt-4">
                            <div class="bg-white p-4 rounded-lg shadow-md col-span-10">
                                <h3 class="text-lg font-semibold mb-4" x-text="data?.chart_status?.provHorizontalBar?.title || 'Harga per Provinsi'"></h3>
                                <div id="provHorizontalBarChart_05" class="chart-container w-full h-[550px] mx-auto"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 04 - Harga Produsen Desa -->
                <div x-data="{ open: true }">
                    <button type="button" @click="open = !open"
                        class="flex items-center justify-between w-full p-4 font-bold text-white rounded-lg gap-3"
                        style="background-color: #9A60B4;">
                        <span>Harga Produsen Desa</span>
                        <span class="material-symbols-rounded shrink-0" x-text="open ? 'keyboard_arrow_up' : 'keyboard_arrow_down'"></span>
                    </button>
                    <div x-show="open" x-collapse>
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-4 pt-4">
                            <div class="bg-white p-4 rounded-lg shadow-md col-span-10">
                                <h3 class="text-lg font-semibold mb-4" x-text="data?.chart_status?.provHorizontalBar?.title || 'Harga per Provinsi'"></h3>
                                <div id="provHorizontalBarChart_04" class="chart-container w-full h-[550px] mx-auto"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 03 - Harga Perdagangan Besar -->
                <div x-data="{ open: true }">
                    <button type="button" @click="open = !open"
                        class="flex items-center justify-between w-full p-4 font-bold text-white rounded-lg gap-3"
                        style="background-color: #8A9A5B;">
                        <span>Harga Perdagangan Besar</span>
                        <span class="material-symbols-rounded shrink-0" x-text="open ? 'keyboard_arrow_up' : 'keyboard_arrow_down'"></span>
                    </button>
                    <div x-show="open" x-collapse>
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-4 pt-4">
                            <div class="bg-white p-4 rounded-lg shadow-md col-span-10">
                                <h3 class="text-lg font-semibold mb-4" x-text="data?.chart_status?.provHorizontalBar?.title || 'Harga per Provinsi'"></h3>
                                <div id="provHorizontalBarChart_03" class="chart-container w-full h-[550px] mx-auto"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 01 - Harga Konsumen Kota (HK) -->
                <div x-data="{ open: true }">
                    <button type="button" @click="open = !open"
                        class="flex items-center justify-between w-full p-4 font-bold text-white rounded-lg gap-3"
                        style="background-color: #5470C6;">
                        <span>Harga Konsumen Kota</span>
                        <span class="material-symbols-rounded shrink-0" x-text="open ? 'keyboard_arrow_up' : 'keyboard_arrow_down'"></span>
                    </button>
                    <div x-show="open" x-collapse>
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-4 pt-4">
                            <div style="display:none" class="bg-white p-4 rounded-lg shadow-md col-span-10">
                                <h3 class="text-lg font-semibold mb-4" x-text="data?.chart_status?.provHorizontalBar?.title || 'Harga per Provinsi'"></h3>
                                <div id="provHorizontalBarChart_01" class="chart-container w-full h-[550px] mx-auto"></div>
                            </div>
                            <div class="bg-white p-4 rounded-lg shadow-md col-span-10">
                                <h3 class="text-lg font-semibold mb-4" x-text="data?.chart_status?.kabkotHorizontalBar?.title || 'Harga per Kabupaten/Kota'"></h3>
                                <div id="kabkotHorizontalBarChart_01" class="chart-container w-full h-[550px] mx-auto"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 02 - Harga Konsumen Desa -->
                <div x-data="{ open: true }">
                    <button type="button" @click="open = !open"
                        class="flex items-center justify-between w-full p-4 font-bold text-white rounded-lg gap-3"
                        style="background-color: #73C0DE;">
                        <span>Harga Konsumen Desa</span>
                        <span class="material-symbols-rounded shrink-0" x-text="open ? 'keyboard_arrow_up' : 'keyboard_arrow_down'"></span>
                    </button>
                    <div x-show="open" x-collapse>
                        <div class="grid grid-cols-1 md:grid-cols-10 gap-4 pt-4">
                            <div class="bg-white p-4 rounded-lg shadow-md col-span-10">
                                <h3 class="text-lg font-semibold mb-4" x-text="data?.chart_status?.provHorizontalBar?.title || 'Harga per Provinsi'"></h3>
                                <div id="provHorizontalBarChart_02" class="chart-container w-full h-[550px] mx-auto"></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Modals -->
    <x-modal name="success-modal" title="Berhasil" maxWidth="md">
        <div class="text-gray-900">
            <p x-text="modalMessage"></p>
            <div class="mt-4 flex justify-end">
                <x-primary-button type="button" x-on:click="$dispatch('close')">Tutup</x-primary-button>
            </div>
        </div>
    </x-modal>

    <x-modal name="error-modal" title="Kesalahan" maxWidth="md">
        <div class="text-gray-900">
            <p x-text="modalMessage"></p>
            <div class="mt-4 flex justify-end">
                <x-primary-button type="button" x-on:click="$dispatch('close')">Tutup</x-primary-button>
            </div>
        </div>
    </x-modal>
</x-two-panel-layout>