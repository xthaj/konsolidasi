<x-two-panel-layout>
    @section('vite')
    @vite(['resources/css/app.css', 'resources/js/alpine-init.js', 'resources/js/edit-data.js', 'resources/js/alpine-start.js'])
    @endsection

    <!-- modal is for table methods -->
    <x-modal name="confirm-delete" focusable title="Konfirmasi Hapus Inflasi " x-cloak>
        <div class="px-6 py-4">
            <p x-text="'Hapus inflasi komoditas ' + modalData.komoditas + '?'"></p>
            <div class="mt-4">
                <label class="flex items-center">
                    <input
                        type="checkbox"
                        x-model="deleteRekonsiliasi"
                        class="rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500">
                    <span class="ml-2 text-sm text-gray-600">Hapus juga rekonsiliasi berkaitan (wajib)</span>
                </label>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close-modal', 'confirm-delete')">Batal</x-secondary-button>
                <x-primary-button
                    @click="confirmDelete()"
                    x-bind:disabled="!deleteRekonsiliasi"
                    x-bind:class="{ 'opacity-50 cursor-not-allowed': !deleteRekonsiliasi }">
                    Hapus
                </x-primary-button>
            </div>
        </div>
    </x-modal>

    <x-slot name="sidebar">
        <form id="filter-form" x-ref="filterForm" method="GET" action="{{ route('data.edit') }}">
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

                <!-- Level Harga -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">Level Harga<span class="text-red-500 ml-1">*</span></label>
                    <select name="kd_level" x-model="selectedKdLevel" @change="updateKdWilayah()" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="05" @selected(request('kd_level')=='05' )>Harga Produsen</option>
                        <option value="01" @selected(request('kd_level')=='01' )>Harga Konsumen Kota</option>
                        <option value="02" @selected(request('kd_level')=='02' )>Harga Konsumen Desa</option>
                        <option value="03" @selected(request('kd_level')=='03' )>Harga Perdagangan Besar</option>
                        <option value="04" @selected(request('kd_level')=='04' )>Harga Produsen Desa</option>
                        <option value="all" @selected(request('kd_level')=='all' )>Semua Level Harga</option>
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

                <!-- Sorting -->
                <div x-show="selectedKdLevel !== 'all'" class="flex gap-4">
                    <div class="w-1/2">
                        <label class="block mb-2 text-sm font-medium text-gray-900">Urut Berdasarkan</label>
                        <select name="sort" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                            <option value="kd_komoditas" {{ $sort === 'kd_komoditas' ? 'selected' : '' }}>Kode Komoditas</option>
                            <option value="inflasi" {{ $sort === 'inflasi' ? 'selected' : '' }}>Nilai Inflasi</option>
                        </select>
                    </div>
                    <div class="w-1/2">
                        <label class="block mb-2 text-sm font-medium text-gray-900">Pengurutan</label>
                        <select name="direction" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                            <option value="asc" {{ $direction === 'asc' ? 'selected' : '' }}>Naik</option>
                            <option value="desc" {{ $direction === 'desc' ? 'selected' : '' }}>Turun</option>
                        </select>
                    </div>
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
            </div>
        </form>
    </x-slot>

    @if($status === 'no_filters')
    <div class="bg-white px-6 py-4 rounded-lg shadow-sm text-center text-gray-500">
        {{ $message }}
    </div>
    @elseif($status === 'no_data')
    <div class="bg-white px-6 py-4 rounded-lg shadow-sm text-center text-gray-500">
        {{ $message }}
    </div>
    @elseif($status === 'success')

    <div class="mb-1">
        <h2 class="text-l font-semibold mb-2">{{ $title }}</h2>
    </div>

    <div class="bg-white md:overflow-hidden shadow-sm sm:rounded-lg">
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg md:max-h-[90vh] overflow-y-auto">
            @if($kd_level === 'all')
            <!-- Table for "Semua Level Harga" -->
            <table class="w-full text-sm text-left rtl:text-right text-gray-500">
                <colgroup>
                    <col span="2">
                </colgroup>
                <colgroup class="bg-gray-50">
                    <col span="2">
                </colgroup>
                <colgroup>
                    <col span="2">
                </colgroup>
                <colgroup class="bg-gray-50">
                    <col span="2">
                </colgroup>
                <colgroup>
                    <col span="2">
                </colgroup>
                <colgroup class="bg-gray-50">
                    <col span="2">
                </colgroup>
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 sticky top-0 z-10 shadow-sm">
                    <tr>
                        <th scope="col" class="px-6 py-3">Kode Komoditas</th>
                        <th scope="col" class="px-6 py-3">Komoditas</th>
                        <th scope="col" class="px-6 py-3 bg-gray-50" colspan="2">Harga Produsen</th>
                        <th scope="col" class="px-6 py-3" colspan="2">Harga Produsen Desa</th>
                        <th scope="col" class="px-6 py-3 bg-gray-50" colspan="2">Harga Perdagangan Besar</th>
                        <th scope="col" class="px-6 py-3" colspan="2">Harga Konsumen Desa</th>
                        <th scope="col" class="px-6 py-3 bg-gray-50" colspan="2">Harga Konsumen Kota</th>
                    </tr>
                    <tr>
                        <th scope="col" class="px-6 py-3"></th>
                        <th scope="col" class="px-6 py-3"></th>
                        <th scope="col" class="px-6 py-3 bg-gray-50">Inflasi</th>
                        <th scope="col" class="px-6 py-3 bg-gray-50">Andil</th>
                        <th scope="col" class="px-6 py-3">Inflasi</th>
                        <th scope="col" class="px-6 py-3">Andil</th>
                        <th scope="col" class="px-6 py-3 bg-gray-50">Inflasi</th>
                        <th scope="col" class="px-6 py-3 bg-gray-50">Andil</th>
                        <th scope="col" class="px-6 py-3">Inflasi</th>
                        <th scope="col" class="px-6 py-3">Andil</th>
                        <th scope="col" class="px-6 py-3 bg-gray-50">Inflasi</th>
                        <th scope="col" class="px-6 py-3 bg-gray-50">Andil</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($inflasi as $item)
                    <tr class="bg-white border-b border-gray-200">
                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                            {{ $item->kd_komoditas }}
                        </th>
                        <td class="px-6 py-4">
                            {{ $item->komoditas->nama_komoditas }}
                        </td>
                        <td class="px-6 py-4 text-right bg-gray-50">
                            {{ $item->inflasi_05 !== null ? number_format($item->inflasi_05, 2, '.', '') : '-' }}
                        </td>
                        <td class="px-6 py-4 text-right bg-gray-50">
                            {{ $item->andil_05 !== null ? number_format($item->andil_05, 2, '.', '') : '-' }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            {{ $item->inflasi_04 !== null ? number_format($item->inflasi_04, 2, '.', '') : '-' }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            {{ $item->andil_04 !== null ? number_format($item->andil_04, 2, '.', '') : '-' }}
                        </td>
                        <td class="px-6 py-4 text-right bg-gray-50">
                            {{ $item->inflasi_03 !== null ? number_format($item->inflasi_03, 2, '.', '') : '-' }}
                        </td>
                        <td class="px-6 py-4 text-right bg-gray-50">
                            {{ $item->andil_03 !== null ? number_format($item->andil_03, 2, '.', '') : '-' }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            {{ $item->inflasi_02 !== null ? number_format($item->inflasi_02, 2, '.', '') : '-' }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            {{ $item->andil_02 !== null ? number_format($item->andil_02, 2, '.', '') : '-' }}
                        </td>
                        <td class="px-6 py-4 text-right bg-gray-50">
                            {{ $item->inflasi_01 !== null ? number_format($item->inflasi_01, 2, '.', '') : '-' }}
                        </td>
                        <td class="px-6 py-4 text-right bg-gray-50">
                            {{ $item->andil_01 !== null ? number_format($item->andil_01, 2, '.', '') : '-' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <!-- Original Table for Specific Level -->
            <table class="w-full text-sm text-left rtl:text-right text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 sticky top-0 z-10 shadow-sm">
                    <tr>
                        <th scope="col" class="px-6 py-3">Kode Komoditas</th>
                        <th scope="col" class="px-6 py-3">Komoditas</th>
                        <th scope="col" class="px-6 py-3">Inflasi</th>
                        <!-- TODO: if inflasi hped then make it like naik/stabil/turun -->
                        @if ($inflasi->first() && $inflasi->first()->kd_wilayah == 0)
                        <th scope="col" class="px-6 py-3">Andil</th>
                        @endif
                        <th scope="col" class="px-6 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($inflasi as $item)
                    <tr class="bg-white border-b border-gray-200">
                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                            {{ $item->kd_komoditas }}
                        </th>
                        <td class="px-6 py-4">
                            {{ $item->komoditas->nama_komoditas }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            {{ $item->inflasi !== null ? number_format($item->inflasi, 2, '.', '') : '-' }}
                        </td>
                        @if ($item->kd_wilayah == 0)
                        <td class="px-6 py-4 text-right">
                            {{ $item->andil !== null ? number_format($item->andil, 2, '.', '') : '-' }}
                        </td>
                        @endif
                        <td class="px-6 py-4 text-right">
                            <button
                                @click="openDeleteModal('{{ $item->inflasi_id }}', '{{ $item->komoditas->nama_komoditas }}')"
                                class="font-medium text-red-600 hover:underline">
                                Delete
                            </button>
                        </td>
                        @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>

    @if($inflasi->hasPages())
    <div class="mt-4">
        {{ $inflasi->appends(request()->query())->links() }}
    </div>
    @endif
    @endif

    </div>
</x-two-panel-layout>