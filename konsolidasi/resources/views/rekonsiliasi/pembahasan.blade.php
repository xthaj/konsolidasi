<x-two-panel-layout>
    @section('vite')
    @vite(['resources/css/app.css', 'resources/js/rekonsiliasi/pembahasan.js'])
    @endsection

    <x-slot name="sidebar">
        <form id="filter-form" x-data="filterForm()" x-ref="filterForm" method="GET" action="{{ route('rekon.pembahasan') }}">
            <div class="space-y-4 md:space-y-6 mt-4">
                <!-- Bulan & Tahun -->
                <div>
                    <div class="flex gap-4">
                        <div class="w-1/2">
                            <label class="block mb-2 text-sm font-medium text-gray-900">Bulan<span class="text-red-500 ml-1">*</span></label>
                            <select name="bulan" x-model="bulan" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 focus:ring-primary-500 focus:border-primary-500">
                                @foreach(['Januari' => '01', 'Februari' => '02', 'Maret' => '03', 'April' => '04', 'Mei' => '05', 'Juni' => '06', 'Juli' => '07', 'Agustus' => '08', 'September' => '09', 'Oktober' => '10', 'November' => '11', 'Desember' => '12'] as $nama => $bln)
                                <option value="{{ $bln }}" @selected($data['filters']['bulan']==$bln)>{{ $nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-1/2">
                            <label class="block mb-2 text-sm font-medium text-gray-900">Tahun<span class="text-red-500 ml-1">*</span></label>
                            <select name="tahun" x-model="tahun" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 focus:ring-primary-500 focus:border-primary-500">
                                <template x-for="year in tahunOptions" :key="year">
                                    <option :value="year" :selected="year == '{{ $data['filters']['tahun'] }}'" x-text="year"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                    <p id="helper-text-explanation" class="text-sm text-gray-500" x-show="isActivePeriod">Periode aktif</p>
                </div>

                <!-- Level Harga -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">Level Harga<span class="text-red-500 ml-1">*</span></label>
                    <select name="kd_level" x-model="selectedKdLevel" @change="updateKdWilayah()" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="01" @selected($data['filters']['kdLevel']=='01' )>Harga Konsumen Kota</option>
                        <option value="02" @selected($data['filters']['kdLevel']=='02' )>Harga Konsumen Desa</option>
                        <option value="03" @selected($data['filters']['kdLevel']=='03' )>Harga Perdagangan Besar</option>
                        <option value="04" @selected($data['filters']['kdLevel']=='04' )>Harga Produsen Desa</option>
                        <option value="05" @selected($data['filters']['kdLevel']=='05' )>Harga Produsen</option>
                    </select>
                </div>

                <!-- Wilayah -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">Wilayah<span class="text-red-500 ml-1">*</span></label>
                    <!-- Provinsi Dropdown -->
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-900">Provinsi</label>
                        <select x-model="selectedProvince" @change="selectedKabkot = ''; updateKdWilayah()" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                            <option value="">Semua Provinsi</option>
                            @foreach (\App\Models\Wilayah::whereRaw('LENGTH(kd_wilayah) = 2')->get() as $province)
                            <option value="{{ $province->kd_wilayah }}" @selected($province->kd_wilayah == substr($data['filters']['kdWilayah'], 0, 2))>{{ $province->nama_wilayah }}</option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Kabkot Dropdown (shown for kd_level = '01') -->
                    <div x-show="selectedKdLevel === '01'" class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-900">Kabupaten/Kota</label>
                        <select x-model="selectedKabkot" @change="updateKdWilayah()" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                            <option value="">Semua Kabupaten/Kota</option>
                            <template x-for="kabkot in filteredKabkots" :key="kabkot.kd_wilayah">
                                <option :value="kabkot.kd_wilayah" x-text="kabkot.nama_wilayah" :selected="kabkot.kd_wilayah == '{{ $data['filters']['kdWilayah'] }}'"></option>
                            </template>
                        </select>
                    </div>
                    <input type="hidden" name="kd_wilayah" x-model="kd_wilayah" required>
                </div>

                <!-- Status -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">Status<span class="text-red-500 ml-1">*</span></label>
                    <select name="status" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="all" @selected($data['filters']['status']=='all' )>Semua Status</option>
                        <option value="01" @selected($data['filters']['status']=='01' )>Belum diisi</option>
                        <option value="02" @selected($data['filters']['status']=='02' )>Sudah diisi</option>
                    </select>
                </div>

                <!-- Komoditas -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">Komoditas<span class="text-red-500 ml-1">*</span></label>
                    <select name="kd_komoditas" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="all" @selected($data['filters']['kdKomoditas']=='all' )>Semua Komoditas</option>
                        @foreach (\App\Models\Komoditas::orderBy('nama_komoditas')->get() as $komoditas)
                        <option value="{{ $komoditas->kd_komoditas }}" @selected($data['filters']['kdKomoditas']==$komoditas->kd_komoditas)>{{ $komoditas->nama_komoditas }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:outline-none focus:ring-primary-300 text-white font-medium rounded-lg text-sm px-5 py-2.5 text-center">Filter</button>
            </div>
        </form>
    </x-slot>

    <!-- Rekon table -->
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-lg font-semibold">{{ $data['title'] ?? 'Rekonsiliasi' }}</h1>
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
                            {{ $data['filters']['kdLevel'] === '01' ? 'Inflasi Kota' : ($data['filters']['kdLevel'] === '02' ? 'Inflasi Desa' : 'Inflasi') }}
                        </th>
                        @if ($data['filters']['kdLevel'] === '01' || $data['filters']['kdLevel'] === '02')
                        <th scope="col" class="px-6 py-3">
                            {{ $data['filters']['kdLevel'] === '01' ? 'Inflasi Desa' : 'Inflasi Kota' }}
                        </th>
                        @endif
                        <th scope="col" class="px-6 py-3 min-w-[175px]">Alasan</th>
                        <th scope="col" class="px-6 py-3">Detail</th>
                        <th scope="col" class="px-6 py-3">Sumber</th>
                        <th scope="col" class="px-6 py-3">Pembahasan</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($data['rekonsiliasi'])
                    @forelse ($data['rekonsiliasi'] as $index => $item)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td class="px-6 py-4">{{ $data['rekonsiliasi']->firstItem() + $index }}</td>
                        <td class="px-6 py-4">{{ $item->inflasi->kd_wilayah }}</td>
                        <td class="px-6 py-4">{{ $item->inflasi->wilayah ? ucwords(strtolower($item->inflasi->wilayah->nama_wilayah)) : 'Tidak Dikenal' }}</td>
                        <td class="px-6 py-4">{{ $item->inflasi->kd_komoditas }}</td>
                        <td class="px-6 py-4">{{ $item->inflasi->komoditas->nama_komoditas ?? 'N/A' }}</td>
                        <td class="px-6 py-4">
                            {{ $item->inflasi->kd_level === '01' ? 'Harga Konsumen Kota' : ($item->inflasi->kd_level === '02' ? 'Harga Konsumen Desa' : ($item->inflasi->kd_level === '03' ? 'Harga Perdagangan Besar' : ($item->inflasi->kd_level === '04' ? 'Harga Produsen Desa' : 'Harga Produsen'))) }}
                        </td>
                        <td class="px-6 py-4">
                            @if ($data['filters']['kdLevel'] === '01' && is_numeric($item->inflasi->inflasi))
                            {{ number_format($item->inflasi->inflasi, 2) . '%' }}
                            @elseif ($data['filters']['kdLevel'] === '02')
                            {{ ucfirst($item->inflasi->inflasi ?? '-') }}
                            @elseif (is_numeric($item->inflasi->inflasi))
                            {{ number_format($item->inflasi->inflasi, 2) . '%' }}
                            @else
                            {{ ucfirst($item->inflasi->inflasi ?? '-') }}
                            @endif
                        </td>
                        @if ($data['filters']['kdLevel'] === '01' || $data['filters']['kdLevel'] === '02')
                        <td class="px-6 py-4">
                            @if ($data['filters']['kdLevel'] === '01' && $item->inflasi->inflasi_opposite !== null)
                            {{ is_numeric($item->inflasi->inflasi_opposite) ? number_format($item->inflasi->inflasi_opposite, 2) . '%' : Str::ucfirst($item->inflasi->inflasi_opposite) }}
                            @elseif ($data['filters']['kdLevel'] === '02' && $item->inflasi->inflasi_opposite !== null)
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
                        <td class="px-6 py-4" x-data="{ detail: '{{ addslashes($item->detail ?? '-') }}', showFull: false }">
                            <span x-text="showFull ? detail : (detail.slice(0, 50) + (detail.length > 50 ? '...' : ''))"></span>
                            <button
                                x-show="detail !== '-' && detail.length > 50"
                                @click="showFull = !showFull"
                                class="text-blue-500 underline ml-2">
                                <span x-text="showFull ? 'Sembunyikan' : 'Selengkapnya'"></span>
                            </button>
                        </td>
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
                            <input
                                type="checkbox"
                                class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500"
                                :checked="{{ $item->pembahasan == 1 ? 'true' : 'false' }}"
                                @change="$dispatch('toggle-pembahasan', { id: '{{ $item->rekonsiliasi_id }}', checked: $event.target.checked })">
                        </td>
                    </tr>
                    @empty
                    <tr class="bg-white dark:bg-gray-800">
                        <td colspan="{{ $data['filters']['kdLevel'] === '01' || $data['filters']['kdLevel'] === '02' ? 12 : 11 }}" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            {{ $message }}
                        </td>
                    </tr>
                    @endforelse
                    @else
                    <tr class="bg-white dark:bg-gray-800">
                        <td colspan="{{ $data['filters']['kdLevel'] === '01' || $data['filters']['kdLevel'] === '02' ? 12 : 11 }}" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            {{ $message }}
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    @if ($data['rekonsiliasi'] && $data['rekonsiliasi']->hasPages())
    <div class="mt-4 flex justify-center">
        {{ $data['rekonsiliasi']->appends(request()->query())->links() }}
    </div>
    @endif

    <script>
        console.log(<?php echo json_encode($data['rekonsiliasi']); ?>);
    </script>
</x-two-panel-layout>