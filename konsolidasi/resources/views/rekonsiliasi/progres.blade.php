<x-two-panel-layout>

    @section('vite')
    @vite(['resources/css/app.css', 'resources/js/alpine-init.js', 'resources/js/progres.js', 'resources/js/alpine-start.js'])
    @endsection

    <!-- Main modal -->
    <div id="authentication-modal" x-show="modalOpen" x-cloak @click.away="closeModal()" class="fixed inset-0 z-50 flex justify-center items-center w-full h-full bg-black bg-opacity-50">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <!-- Modal content -->
            <div class="relative bg-white rounded-lg shadow-sm dark:bg-gray-700">
                <!-- Modal header -->
                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600 border-gray-200">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Input Rekonsiliasi</h3>
                    <button type="button" @click="closeModal()" class="end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                        </svg>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <!-- Modal body -->
                <div class="p-4 md:p-5 max-h-[calc(100vh-10rem)] overflow-y-auto">
                    <form class="space-y-4" action="#">
                        <span>Level Harga: Harga Konsumen Kota </span><br>
                        <span>Komoditas: Beras</span><br>
                        <span>Periode: Februari 2024</span><br>

                        <!-- Dropdown menu -->
                        <button id="dropdownCheckboxButton" data-dropdown-toggle="dropdownDefaultCheckbox" class="w-full text-white bg-primary-900 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button">
                            Alasan
                            <svg class="ml-auto w-2.5 h-2.5 ms-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4" />
                            </svg>
                        </button>

                        <!-- Dropdown menu -->
                        <div id="dropdownDefaultCheckbox" class="z-10 hidden w-80 bg-white divide-y divide-gray-100 rounded-lg shadow-sm dark:bg-gray-700 dark:divide-gray-600">
                            <ul class="h-48 px-3 pb-3 overflow-y-auto text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownCheckboxButton">
                                <template x-for="(alasan, index) in alasanList" :key="index">
                                    <li>
                                        <div class="flex items-center p-2 rounded-sm hover:bg-gray-100 dark:hover:bg-gray-600">
                                            <input type="checkbox" :id="'alasan-' + index" x-model="selectedAlasan" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded-sm focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                                            <label :for="'alasan-' + index" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300" x-text="alasan"></label>
                                        </div>
                                    </li>
                                </template>
                            </ul>
                        </div>

                        <!-- alasan -->
                        <div>
                            <label for="alasan" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Detail</label>
                            <textarea id="alasan" rows="6" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Kenaikan harga karena permintaan yang mulai meningkat menjelang akhir tahun. Sebelumnya ..." required></textarea>
                            <p id="helper-text-explanation" class="mt-2 text-sm text-gray-500 dark:text-gray-400">Maksimal 500 karakter</p>
                        </div>

                        <!-- link terkait -->
                        <div>
                            <label for="link_terkait" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Link media</label>
                            <input type="text" id="link_terkait" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                        </div>

                        <button type="submit" class="w-full text-white bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">Edit Nilai</button>

                    </form>
                </div>
            </div>
        </div>
    </div>

    <x-modal name="confirm-add" focusable title="Konfirmasi Penambahan Data">
        <div class="px-6 py-4">
            <p x-show="modalContent.success" x-text="`Tambah ${modalContent.items.length} item?`"></p>
            <div x-show="!modalContent.success">
                <p class="text-red-600">Beberapa data tidak ditemukan:</p>
                <ul class="list-disc pl-5 mt-2">
                    <template x-for="missing in modalContent.missingItems" :key="missing">
                        <li x-text="missing"></li>
                    </template>
                </ul>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                <x-primary-button x-show="modalContent.success" @click="confirmAddToTable">Tambah</x-primary-button>
            </div>
        </div>
    </x-modal>

    @if (auth()->user()->isPusat())
    <x-slot name="sidebar">
        <form id="filter-form" x-ref="filterForm" method="GET" action="{{ route('rekon.progres') }}">
            <f class="space-y-4 md:space-y-6 mt-4">
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
                        <!-- table structure makes it hard to show all, settle with this -->
                        <option value="01" @selected(request('kd_level')=='01' )>Harga Konsumen Kota</option>
                        <option value="02" @selected(request('kd_level')=='02' )>Harga Konsumen Desa</option>
                        <option value="03" @selected(request('kd_level')=='03' )>Harga Perdagangan Besar</option>
                        <option value="04" @selected(request('kd_level')=='04' )>Harga Produsen Desa</option>
                        <option value="05" @selected(request('kd_level')=='05' )>Harga Produsen</option>
                    </select>
                </div>

                <!-- Wilayah -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">Wilayah<span class="text-red-500 ml-1">*</span></label>

                    <!-- Provinsi Dropdown -->
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-900">Provinsi</label>
                        <select
                            @change="selectedKabkot = ''; updateKdWilayah()"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                            <option value="" selected>Semua Provinsi</option>
                            <template x-for="province in provinces" :key="province.kd_wilayah">
                                <option
                                    :value="province.kd_wilayah"
                                    x-text="province.nama_wilayah"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Kabkot Dropdown -->
                    <div x-show="selectedKdLevel === ('01'||'all')" class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-900">Kabupaten/Kota</label>
                        <select x-model="selectedKabkot" @change="updateKdWilayah()" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                            <option value="" selected>Semua Kabupaten/Kota</option>
                            <template x-for="kabkot in filteredKabkots" :key="kabkot.kd_wilayah">
                                <option :value="kabkot.kd_wilayah" x-text="kabkot.nama_wilayah" :selected="kabkot.kd_wilayah == '{{ request('kd_wilayah') }}'"></option>
                            </template>
                        </select>
                    </div>

                    <input type="hidden" name="kd_wilayah" required>
                </div>

                <!-- Status -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">Status<span class="text-red-500 ml-1">*</span></label>
                    <select name="status" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="all" @selected(request('status')=='all' )>Semua Status</option>
                        <option value="01" @selected(request('status')=='01' )>Belum diisi</option>
                        <option value="02" @selected(request('status')=='02' )>Sudah diisi</option>
                    </select>
                </div>

                <button class="w-full bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:outline-none focus:ring-primary-300 text-white font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">Filter</button>
        </form>
    </x-slot>
    @else
    whatver for now
    @endif

    <!-- rekon table -->
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-lg font-semibold">Rekonsiliasi</h1>
    </div>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">No</th>
                    <th scope="col" class="px-6 py-3">Kode Wilayah</th>
                    <th scope="col" class="px-6 py-3">Wilayah</th>
                    <th scope="col" class="px-6 py-3">Kode Komoditas</th>
                    <th scope="col" class="px-6 py-3">Komoditas</th>
                    <th scope="col" class="px-6 py-3">Level Harga</th>
                    @if ($filters['kdLevel'] === '01' || $filters['kdLevel'] === '02')
                    <th scope="col" class="px-6 py-3">Inflasi Kota</th>
                    <th scope="col" class="px-6 py-3">Inflasi Desa</th>
                    @else
                    <th scope="col" class="px-6 py-3">Inflasi</th>
                    @endif
                    <th scope="col" class="px-6 py-3 w-1/4">Alasan</th>
                    <th scope="col" class="px-6 py-3">Detail</th>
                    <th scope="col" class="px-6 py-3">Media</th>
                    <th scope="col" class="px-6 py-3" x-show="isActivePeriod"><span class="sr-only">Edit</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rekonsiliasi as $index => $item)
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-6 py-4">{{ $rekonsiliasi->firstItem() + $index }}</td>
                    <td class="px-6 py-4">{{ $item->inflasi->kd_wilayah }}</td>
                    <td class="px-6 py-4">{{ $item->inflasi->wilayah ? ucwords(strtolower($item->inflasi->wilayah->nama_wilayah)) : 'Tidak Dikenal' }}</td>
                    <td class="px-6 py-4">{{ $item->inflasi->kd_komoditas }}</td>
                    <td class="px-6 py-4">{{ $item->inflasi->komoditas->nama_komoditas ?? 'N/A' }}</td>
                    <td class="px-6 py-4">
                        {{ $item->inflasi->kd_level === '01' ? 'Harga Konsumen Kota' : ($item->inflasi->kd_level === '02' ? 'Harga Desa' : 'Harga Perdagangan Besar') }}
                    </td>
                    @if ($filters['kdLevel'] === '01' || $filters['kdLevel'] === '02')
                    <td class="px-6 py-4">
                        {{ $item->inflasi->inflasi ? number_format($item->inflasi->inflasi, 2) . '%' : '-' }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $inflasi_opposite->get($item->inflasi->kd_komoditas)?->inflasi ? number_format($inflasi_opposite->get($item->inflasi->kd_komoditas)->inflasi, 2) . '%' : '-' }}
                    </td>
                    @else
                    <td class="px-6 py-4">
                        {{ $item->inflasi->inflasi ? number_format($item->inflasi->inflasi, 2) . '%' : '-' }}
                    </td>
                    @endif
                    <td class="px-6 py-4">
                        @if ($item->alasan)
                        <span class="inline-flex items-center px-2 py-1 me-2 my-1 text-sm font-medium text-blue-800 bg-blue-100 rounded-sm dark:bg-blue-900 dark:text-blue-300">
                            {{ $item->alasan }}
                        </span>
                        @endif
                    </td>
                    <td class="px-6 py-4">{{ $item->detail ?? '-' }}</td>
                    <td class="px-6 py-4">
                        @if ($item->media)
                        <a href="{{ $item->media }}" class="text-blue-600 hover:underline" target="_blank">
                            {{ parse_url($item->media, PHP_URL_HOST) }}
                        </a>
                        @else
                        -
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right" x-show="isActivePeriod">
                        <button @click="$dispatch('open-modal', { id: {{ $item->rekonsiliasi_id }} })" class="font-medium text-indigo-600 dark:text-indigo-500 hover:underline">Edit</button>
                    </td>
                </tr>
                @empty
                <tr class="bg-white dark:bg-gray-800">
                    <td colspan="{{ $filters['kdLevel'] === '01' || $filters['kdLevel'] === '02' ? 12 : 11 }}" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                        {{ $message }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if ($rekonsiliasi && $rekonsiliasi->hasPages())
    <div class="mt-4 flex justify-center">
        {{ $rekonsiliasi->appends(request()->query())->links() }}
    </div>
    @endif

</x-two-panel-layout>