<x-two-panel-layout>

    @section('vite')
    @vite(['resources/css/app.css', 'resources/js/alpine-init.js', 'resources/js/progres.js', 'resources/js/alpine-start.js'])
    @endsection

    <!-- Main modal -->
    <x-modal name="edit-rekonsiliasi" focusable title="Edit Rekonsiliasi" x-cloak>
        <div class="px-6 py-4">
            <form class="space-y-4" @submit.prevent="submitEditRekon()">
                <!-- Level Harga -->
                <div>
                    <span>Level Harga: </span>
                    <span x-text="modalData.kd_level === '01' ? 'Harga Konsumen Kota' : (modalData.kd_level === '02' ? 'Harga Desa' : 'Harga Perdagangan Besar')"></span>
                    <!-- Shows "Harga Konsumen Kota" for kd_level "01" -->
                </div>

                <!-- Komoditas -->
                <div>
                    <span>Komoditas: </span>
                    <span x-text="modalData.komoditas"></span>
                    <!-- Shows "Umum" -->
                </div>

                <!-- Periode -->
                <div>
                    <span>Periode: </span>
                    <span x-text="`${activeBulan}/${activeTahun}`"></span>
                </div>

                <!-- Dropdown menu for Alasan -->
                <div>
                    <button
                        id="dropdownCheckboxButton"
                        data-dropdown-toggle="dropdownDefaultCheckbox"
                        class="w-full text-white bg-primary-900 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
                        type="button">
                        Alasan
                        <svg class="w-2.5 h-2.5 ms-3 ml-auto" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4" />
                        </svg>
                    </button>

                    <div
                        id="dropdownDefaultCheckbox"
                        class="z-10 hidden bg-white divide-y divide-gray-100 rounded-lg shadow-sm w-80 dark:bg-gray-700 dark:divide-gray-600">
                        <ul class="p-3 space-y-1 text-sm text-gray-700 dark:text-gray-200 max-h-48 overflow-y-auto" aria-labelledby="dropdownCheckboxButton">
                            <template x-for="(alasan, index) in alasanList" :key="index">
                                <li>
                                    <div class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                                        <input
                                            type="checkbox"
                                            :id="'alasan-' + index"
                                            x-model="selectedAlasan"
                                            :value="alasan"
                                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                                        <label
                                            :for="'alasan-' + index"
                                            class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300 w-full"
                                            x-text="alasan"></label>
                                    </div>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>

                <!-- Detail -->
                <div>
                    <label for="alasan" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Detail</label>
                    <textarea
                        id="alasan"
                        rows="6"
                        x-model="detail"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        placeholder="Kenaikan harga karena permintaan yang mulai meningkat menjelang akhir tahun. Sebelumnya ..."
                        required
                        maxlength="500"></textarea>
                    <p id="helper-text-explanation" class="mt-2 text-sm text-gray-500 dark:text-gray-400">Maksimal 500 karakter</p>
                </div>

                <!-- Link Terkait -->
                <div>
                    <label for="link_terkait" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Link media</label>
                    <input
                        type="text"
                        id="link_terkait"
                        x-model="linkTerkait"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                    <!-- Shows empty string "" -->
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <x-secondary-button x-on:click="$dispatch('close-modal', 'edit-rekonsiliasi')">Batal</x-secondary-button>
                    <x-primary-button type="submit">Edit Nilai</x-primary-button>
                </div>
            </form>
        </div>
    </x-modal>

    <x-modal name="delete-rekonsiliasi" focusable title="Konfirmasi Hapus Rekonsiliasi " x-cloak>
        <div class="px-6 py-4">
            <p x-text="'Hapus rekonsiliasi berikut?'"></p>

            <span x-text="
                modalData.kd_level === '01' ? 'Harga Konsumen Kota' :
                modalData.kd_level === '02' ? 'Harga Konsumen Desa' :
                modalData.kd_level === '03' ? 'Harga Perdagangan Besar' :
                modalData.kd_level === '04' ? 'Harga Produsen Desa' : 'Harga Produsen'">
            </span>
            <div>
                <span>Komoditas: </span>
                <span x-text="modalData.komoditas"></span>
            </div>
            <div>
                <span>Wilayah: </span>
                <span x-text="modalData.nama_wilayah"></span>
            </div>
            <div>
                <span>Periode: </span>
                <span x-text="`${activeBulan} ${activeTahun}`"></span>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close-modal', 'delete-rekonsiliasi')">Batal</x-secondary-button>
                <x-primary-button
                    @click="confirmDelete(modalData.id)">
                    Hapus
                </x-primary-button>
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
                            x-model="selectedProvince"
                            @change="selectedKabkot = ''; updateKdWilayah()"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                            <option value="">Semua Provinsi</option>
                            <template x-for="province in provinces" :key="province.kd_wilayah">
                                <option
                                    :value="province.kd_wilayah"
                                    x-text="province.nama_wilayah" :selected="selectedProvince === province.kd_wilayah"></option>
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

                    <input type="hidden" name="kd_wilayah" x-model="kd_wilayah" required>
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
        <h1 class="text-lg font-semibold">{{ $title ?? 'Rekonsiliasi' }}</h1>
    </div>

    <div class="bg-white md:overflow-hidden shadow-sm sm:rounded-lg">
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg md:max-h-[90vh] overflow-y-auto">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3">No</th>
                        <th scope="col" class="px-6 py-3">Kode Wilayah</th>
                        <th scope="col" class="px-6 py-3">Wilayah</th>
                        <th scope="col" class="px-6 py-3">Kode Komoditas</th>
                        <th scope="col" class="px-6 py-3">Komoditas</th>
                        <th scope="col" class="px-6 py-3">Level Harga</th>
                        <th scope="col" class="px-6 py-3">
                            {{ $filters['kdLevel'] === '01' ? 'Inflasi Kota' : ($filters['kdLevel'] === '02' ? 'Inflasi Desa' : 'Inflasi') }}
                        </th>
                        @if ($filters['kdLevel'] === '01' || $filters['kdLevel'] === '02')
                        <th scope="col" class="px-6 py-3">
                            {{ $filters['kdLevel'] === '01' ? 'Inflasi Desa' : 'Inflasi Kota' }}
                        </th>
                        @endif
                        <th scope="col" class="px-6 py-3 min-w-[175px]">Alasan</th>
                        <th scope="col" class="px-6 py-3">Detail</th>
                        <th scope="col" class="px-6 py-3">Sumber</th>
                        <th scope="col" class="px-6 py-3">Terakhir Diedit Oleh</th>
                        <th scope="col" class="px-6 py-3" x-show="isEditable"><span class="sr-only">Edit</span></th>
                    </tr>
                </thead>
                <tbody>
                    @if ($rekonsiliasi)
                    @forelse ($rekonsiliasi as $index => $item)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4">{{ $rekonsiliasi->firstItem() + $index }}</td>
                        <td class="px-6 py-4">{{ $item->inflasi->kd_wilayah }}</td>
                        <td class="px-6 py-4">{{ $item->inflasi->wilayah ? ucwords(strtolower($item->inflasi->wilayah->nama_wilayah)) : 'Tidak Dikenal' }}</td>
                        <td class="px-6 py-4">{{ $item->inflasi->kd_komoditas }}</td>
                        <td class="px-6 py-4">{{ $item->inflasi->komoditas->nama_komoditas ?? 'N/A' }}</td>
                        <td class="px-6 py-4">
                            {{ $item->inflasi->kd_level === '01' ? 'Harga Konsumen Kota' : ($item->inflasi->kd_level === '02' ? 'Harga Konsumen Desa' : ($item->inflasi->kd_level === '03' ? 'Harga Perdagangan Besar' : ($item->inflasi->kd_level === '04' ? 'Harga Produsen Desa' : 'Harga Produsen'))) }}
                        </td>
                        <td class="px-6 py-4">
                            @if ($filters['kdLevel'] === '01' && is_numeric($item->inflasi->inflasi))
                            {{ number_format($item->inflasi->inflasi, 2) . '%' }}
                            @elseif ($filters['kdLevel'] === '02')
                            {{ ucfirst($item->inflasi->inflasi ?? '-') }}
                            @elseif (is_numeric($item->inflasi->inflasi))
                            {{ number_format($item->inflasi->inflasi, 2) . '%' }}
                            @else
                            {{ ucfirst($item->inflasi->inflasi ?? '-') }}
                            @endif

                        </td>
                        @if ($filters['kdLevel'] === '01' || $filters['kdLevel'] === '02')
                        <td class="px-6 py-4">
                            @if ($filters['kdLevel'] === '01' && $item->inflasi->inflasi_opposite !== null)
                            {{ is_numeric($item->inflasi->inflasi_opposite) ? number_format($item->inflasi->inflasi_opposite, 2) . '%' : Str::ucfirst($item->inflasi->inflasi_opposite) }}
                            @elseif ($filters['kdLevel'] === '02' && $item->inflasi->inflasi_opposite !== null)
                            {{ is_numeric($item->inflasi->inflasi_opposite) ? number_format($item->inflasi->inflasi_opposite, 2) . '%' : Str::ucfirst($item->inflasi->inflasi_opposite) }}
                            @else
                            {{ Str::ucfirst($item->inflasi->inflasi_opposite ?? '-') }}
                            @endif
                        </td>
                        @endif
                        <td class="px-6 py-4">
                            @if ($item->alasan)
                            <ul class="list-disc list-inside">
                                @foreach (explode(',', $item->alasan) as $alasan)
                                <li>{{ trim($alasan) }}</li>
                                @endforeach
                            </ul>
                            @else
                            -
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
                        <td class="px-6 py-4">
                            @if ($item->user && $item->user->nama_lengkap)
                            <span class="inline-flex items-center px-2 py-1 me-2 my-1 text-sm font-medium text-blue-800 bg-blue-100 rounded-sm dark:bg-blue-900 dark:text-blue-300">
                                {{ $item->user->nama_lengkap }}
                            </span>
                            @else
                            -
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right" x-show="isActivePeriod">
                            <button
                                @click="openEditRekonModal('{{ $item->rekonsiliasi_id }}', '{{ $item->inflasi->komoditas->nama_komoditas }}', '{{ $item->inflasi->kd_level }}', '{{ $item->alasan ?? '' }}', '{{ $item->detail ?? '' }}', '{{ $item->media ?? '' }}')"
                                class="font-medium text-indigo-600 dark:text-indigo-500 hover:underline">
                                Edit
                            </button>
                            <button
                                @click="openDeleteModal('{{ $item->rekonsiliasi_id }}', '{{ $item->inflasi->komoditas->nama_komoditas }}', '{{ $item->inflasi->wilayah->nama_wilayah }}', '{{ $item->inflasi->kd_level }}', '')"
                                class="font-medium text-red-600 hover:underline">
                                Delete
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr class="bg-white dark:bg-gray-800">
                        <td colspan="{{ $filters['kdLevel'] === '01' || $filters['kdLevel'] === '02' ? 12 : 11 }}" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            {{ $message }}
                        </td>
                    </tr>
                    @endforelse
                    @else
                    <tr class="bg-white dark:bg-gray-800">
                        <td colspan="{{ $filters['kdLevel'] === '01' || $filters['kdLevel'] === '02' ? 12 : 11 }}" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            {{ $message }}
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    @if ($rekonsiliasi && $rekonsiliasi->hasPages())
    <div class="mt-4 flex justify-center">
        {{ $rekonsiliasi->appends(request()->query())->links() }}
    </div>
    @endif

    <script>
        console.log(<?php echo json_encode($rekonsiliasi); ?>);
    </script>

</x-two-panel-layout>