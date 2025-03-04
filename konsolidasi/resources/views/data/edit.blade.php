<x-two-panel-layout>
    @section('vite')
        @vite(['resources/css/app.css', 'resources/js/alpine-init.js', 'resources/js/edit-data.js', 'resources/js/alpine-start.js'])
    @endsection

    <!-- Edit Harga Modal (Blade Component) -->
    <x-modal name="edit-harga" focusable title="{{ __('Edit Harga') }}">
        <form id="edit-harga-form" method="POST" action="" class="px-6 py-4">
            @csrf
            @method('PATCH')

            <!-- Hidden Input for inflasi_id -->
            <input type="hidden" name="inflasi_id" x-bind:value="item.id">

            <!-- Tidy Text Presentation -->
            <div class="mt-4 grid grid-cols-1 gap-y-2 sm:grid-cols-3 sm:gap-x-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        {{ __('Wilayah') }}
                    </label>
                    <p class="mt-1 text-sm text-gray-900" x-text="item.wilayah"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        {{ __('Level Harga') }}
                    </label>
                    <p class="mt-1 text-sm text-gray-900" x-text="item.levelHarga"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        {{ __('Komoditas') }}
                    </label>
                    <p class="mt-1 text-sm text-gray-900" x-text="item.komoditas"></p>
                </div>
            </div>

            <!-- Input for New Harga -->
            <div class="mt-6">
                <x-input-label for="harga" :value="__('Nilai Inflasi Baru')" />
                <x-text-input
                    id="harga"
                    name="harga"
                    type="text"
                    class="mt-1 block w-full"
                    x-bind:value="item.harga"
                    required
                />
                <x-input-error :messages="$errors->get('harga')" class="mt-2" />
            </div>

            <!-- Form Actions -->
            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-primary-button type="submit">
                    {{ __('Edit Nilai Inflasi') }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>

    <!-- Tambah Harga Modal (Blade Component) -->
    <x-modal name="tambah-harga" focusable title="{{ __('Tambah Harga') }}">
        <!-- <form method="post" action="{{ route('data.store') }}" class="p-6">
            @csrf

            <div class="mt-4">
                <x-input-label for="bulan" :value="__('Bulan')" />
                <select id="bulan" name="bulan" class="mt-1 block w-full p-2.5 border-gray-300 rounded-lg" required>
                    <option value="">Pilih Bulan</option>
                    @foreach(['Januari' => '01', 'Februari' => '02', 'Maret' => '03', 'April' => '04', 'Mei' => '05', 'Juni' => '06', 'Juli' => '07', 'Agustus' => '08', 'September' => '09', 'Oktober' => '10', 'November' => '11', 'Desember' => '12'] as $nama => $bulan)
                        <option value="{{ $bulan }}">{{ $nama }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('bulan')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="tahun" :value="__('Tahun')" />
                <select id="tahun" name="tahun" class="mt-1 block w-full p-2.5 border-gray-300 rounded-lg" required>
                    <option value="">Pilih Tahun</option>
                    @for ($year = 2020; $year <= 2025; $year++)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endfor
                </select>
                <x-input-error :messages="$errors->get('tahun')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="kd_level" :value="__('Level Harga')" />
                <select id="kd_level" name="kd_level" class="mt-1 block w-full p-2.5 border-gray-300 rounded-lg" required>
                    <option value="">Pilih Level Harga</option>
                    <option value="01">Harga Konsumen Kota</option>
                    <option value="02">Harga Konsumen Desa</option>
                    <option value="03">Harga Perdagangan Besar</option>
                    <option value="04">Harga Produsen Desa</option>
                    <option value="05">Harga Produsen</option>
                </select>
                <x-input-error :messages="$errors->get('kd_level')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="kd_wilayah" :value="__('Wilayah')" />
                <select id="kd_wilayah" name="kd_wilayah" class="mt-1 block w-full p-2.5 border-gray-300 rounded-lg" required>
                    <option value="">Pilih Wilayah</option>
                    <option value="0">Nasional</option>
                    <template x-for="province in provinces" :key="province.kd_wilayah">
                        <option :value="province.kd_wilayah" x-text="province.nama_wilayah"></option>
                    </template>
                </select>
                <x-input-error :messages="$errors->get('kd_wilayah')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="kd_komoditas" :value="__('Komoditas')" />
                <select id="kd_komoditas" name="kd_komoditas" class="mt-1 block w-full p-2.5 border-gray-300 rounded-lg" required>
                    <option value="">Pilih Komoditas</option>
                    <template x-for="komoditi in komoditas" :key="komoditi.kd_komoditas">
                        <option :value="komoditi.kd_komoditas" x-text="komoditi.nama_komoditas"></option>
                    </template>
                </select>
                <x-input-error :messages="$errors->get('kd_komoditas')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="harga" :value="__('Nilai Inflasi')" />
                <x-text-input
                    id="harga"
                    name="harga"
                    type="text"
                    class="mt-1 block w-full"
                    required
                />
                <x-input-error :messages="$errors->get('harga')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-primary-button class="ms-3">
                    {{ __('Tambah Harga') }}
                </x-primary-button>
            </div>
        </form> -->
    </x-modal>

    <x-slot name="sidebar">
        <form id="filter-form" method="GET" action="{{ route('data.edit') }}">
            <div id="vizBuilderPanel" x-data="webData" x-init="init()" class="space-y-4 md:space-y-6 mt-4">
                <!-- Bulan & Tahun -->
                <div class="flex gap-4">
                    <div class="w-1/2">
                        <label class="block mb-2 text-sm font-medium text-gray-900">Bulan</label>
                        <select name="bulan" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                            <option value="">Pilih Bulan</option>
                            @foreach(['Januari' => '01', 'Februari' => '02', 'Maret' => '03', 'April' => '04', 'Mei' => '05', 'Juni' => '06', 'Juli' => '07', 'Agustus' => '08', 'September' => '09', 'Oktober' => '10', 'November' => '11', 'Desember' => '12'] as $nama => $bulan)
                                <option value="{{ $bulan }}" @selected(request('bulan') == $bulan)>{{ $nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-1/2">
                        <label class="block mb-2 text-sm font-medium text-gray-900">Tahun</label>
                        <select name="tahun" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                            <option value="">Pilih Tahun</option>
                            @for ($year = 2020; $year <= 2025; $year++)
                                <option value="{{ $year }}" @selected(request('tahun') == $year)>{{ $year }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <!-- Level Harga -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">Level Harga</label>
                    <select name="kd_level" x-model="selectedKdLevel" @change="updateKdWilayah()" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="">Pilih Level Harga</option>
                        <option value="01" @selected(request('kd_level') == '01')>Harga Konsumen Kota</option>
                        <option value="02" @selected(request('kd_level') == '02')>Harga Konsumen Desa</option>
                        <option value="03" @selected(request('kd_level') == '03')>Harga Perdagangan Besar</option>
                        <option value="04" @selected(request('kd_level') == '04')>Harga Produsen Desa</option>
                        <option value="05" @selected(request('kd_level') == '05')>Harga Produsen</option>
                    </select>
                </div>

                <!-- Wilayah -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">Wilayah</label>
                    <div class="flex items-start mb-6">
                        <div class="flex items-center h-5">
                            <input type="checkbox" id="is_pusat" x-model="isPusat" @click="togglePusat()" class="w-4 h-4 border border-gray-300 rounded-sm bg-gray-50 focus:ring-3 focus:ring-primary-300" />
                        </div>
                        <label for="is_pusat" class="ms-2 text-sm font-medium text-gray-900">Nasional</label>
                    </div>

                    <!-- Provinsi Dropdown -->
                <div x-show="!isPusat" class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Provinsi</label>
                    <select x-model="selectedProvince" @change="selectedKabkot = ''; updateKdWilayah()" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="" selected>Pilih Provinsi</option>
                        <template x-for="province in provinces" :key="province.kd_wilayah">
                            <option :value="province.kd_wilayah" x-text="province.nama_wilayah" :selected="province.kd_wilayah == '{{ request('kd_wilayah') }}'"></option>
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

                <!-- Buttons -->
                <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:outline-none focus:ring-primary-300 text-white font-medium rounded-lg text-sm px-5 py-2.5 text-center">Tampilkan</button>
                <!-- <button type="button" x-on:click="$dispatch('open-modal', 'tambah-harga')" class="w-full bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:outline-none focus:ring-primary-300 text-white font-medium rounded-lg text-sm px-5 py-2.5 text-center">Tambah</button> -->
            </div>
        </form>
    </x-slot>

    @if($inflasi->isNotEmpty())
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3">Kode Komoditas</th>
                            <th scope="col" class="px-6 py-3">Komoditas</th>
                            <th scope="col" class="px-6 py-3">Inflasi/RH</th>
                            @if ($inflasi->first()->kd_wilayah == 0)
                                <th scope="col" class="px-6 py-3">Andil</th>
                            @endif
                            <th scope="col" class="px-6 py-3"><span class="sr-only">Edit</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($inflasi as $item)
                            <tr class="bg-white border-b border-gray-200">
                                <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                    {{ $item->kd_komoditas }}
                                </th>
                                <td class="px-6 py-4">
                                    {{ $item->komoditas->nama_komoditas }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    {{ number_format($item->harga, 2, '.', '') }}
                                </td>
                                @if ($item->kd_wilayah == 0)
                                    <td class="px-6 py-4 text-right">
                                        {{ number_format($item->inflasi, 2, '.', '') }}
                                    </td>
                                @endif
                                <td class="px-6 py-4 text-right">
                                    <button @click="setItem('{{ $item->inflasi_id }}', '{{ $item->komoditas->nama_komoditas }}', '{{ $item->harga }}', '{{ $item->kd_wilayah }}', '{{ $item->kd_level }}'); $dispatch('open-modal', 'edit-harga')" class="font-medium text-primary-600 hover:underline">
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                    Silakan pilih filter di sidebar untuk menampilkan data.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $inflasi->appends(request()->query())->links() }}
        </div>
    @endif
</x-two-panel-layout>
