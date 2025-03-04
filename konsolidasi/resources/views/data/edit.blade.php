<x-two-panel-layout >

@section('vite')
    @vite(['resources/css/app.css', 'resources/js/alpine-init.js', 'resources/js/edit-data.js', 'resources/js/alpine-start.js'])
@endsection

    <!-- Main modal -->
    <div
    id="authentication-modal"
    x-show="modalOpen"
    @click.away="closeModal()"
    class="fixed inset-0 z-50 flex justify-center items-center w-full h-full bg-black bg-opacity-50"
    >
    <div class="relative p-4 w-full max-w-md max-h-full">


    <!-- modal the component way -->
        <x-modal name="edit-harga" :show="$errors->userDeletion->isNotEmpty()" focusable>
            <form method="post" action="" class="p-6">
                @csrf
                <!-- @method('delete') -->

                <div class="p-4 md:p-5">
                    <!-- <form class="space-y-4" action="#"> -->
                    <div>Wilayah: <span x-text="item.wilayah"></span></div>
                    <div>Level Harga: <span x-text="item.levelHarga"></span></div>
                    <div>Komoditas: <span x-text="item.komoditas"></span></div>
                    <!-- <div>Periode: <span x-text="item.periode"></span></div> -->

                        <!-- Label untuk angka -->
                        <div>
                            <label for="harga" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Nilai inflasi baru</label>
                            <input type="text" name="harga" id="harga" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white" required />
                        </div>
                        <button type="submit" class="w-full text-white bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">Edit Nilai</button>
                    <!-- </form> -->
                </div>

                <div class="mt-6 flex justify-end">
                    <x-secondary-button x-on:click="$dispatch('close')">
                        {{ __('Cancel') }}
                    </x-secondary-button>

                    <x-danger-button class="ms-3">
                        {{ __('Edit Nilai Inflasi') }}
                    </x-danger-button>
                </div>
            </form>
        </x-modal>

        <!-- Modal content -->
        <div class="relative bg-white rounded-lg shadow-sm dark:bg-gray-700">
            <!-- Modal header -->
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600 border-gray-200">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Edit Harga
                </h3>
                <button
                    type="button"
                    @click="closeModal()"
                    class="end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>

            </div>
            <!-- Modal body -->
            <div class="p-4 md:p-5">
                <form class="space-y-4" action="#">
                <div>Wilayah: <span x-text="item.wilayah"></span></div>
                <div>Level Harga: <span x-text="item.levelHarga"></span></div>
                <div>Komoditas: <span x-text="item.komoditas"></span></div>
                <!-- <div>Periode: <span x-text="item.periode"></span></div> -->

                    <!-- Label untuk angka -->
                    <div>
                        <label for="harga" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Nilai inflasi baru</label>
                        <input type="text" name="harga" id="harga" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white" required />
                    </div>
                    <button type="submit" class="w-full text-white bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">Edit Nilai</button>
                </form>
            </div>
        </div>
    </div>
</div>

<x-slot name="sidebar">
        <form id="filter-form" method="GET" action="{{ route('data.edit') }}">
            <div id="vizBuilderPanel" x-data="webData" class="space-y-4 md:space-y-6 mt-4">
                <!-- Bulan & Tahun -->
                <div class="flex gap-4">
                    <div class="w-1/2">
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Bulan</label>
                        <select name="bulan" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">Pilih Bulan</option>
                            @foreach(['Januari' => '01', 'Februari' => '02', 'Maret' => '03', 'April' => '04', 'Mei' => '05', 'Juni' => '06', 'Juli' => '07', 'Agustus' => '08', 'September' => '09', 'Oktober' => '10', 'November' => '11', 'Desember' => '12'] as $nama => $bulan)
                                <option value="{{ $bulan }}" @selected(request('bulan') == $bulan)>{{ $nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-1/2">
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Tahun</label>
                        <select name="tahun" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">Pilih Tahun</option>
                            @for ($year = 2020; $year <= 2025; $year++)
                                <option value="{{ $year }}" @selected(request('tahun') == $year)>{{ $year }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <!-- Level Harga -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Level Harga</label>
                    <select name="kd_level" x-model="selectedKdLevel" @change="updateKdWilayah()" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">Pilih Level Harga</option>
                        <option value="01" @selected(request('kd_level') == '01')>Harga Konsumen Kota</option>
                        <option value="02" @selected(request('kd_level') == '02')>Harga Konsumen Desa</option>
                        <option value="03" @selected(request('kd_level') == '03')>Harga Perdagangan Besar</option>
                        <option value="04" @selected(request('kd_level') == '04')>Harga Produsen Desa</option>
                        <option value="05" @selected(request('kd_level') == '05')>Harga Produsen</option>
                    </select>
                </div>

                <!-- Wilayah -->
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Wilayah</label>
                <div class="flex items-start mb-6">
                    <div class="flex items-center h-5">
                        <input type="checkbox" id="is_pusat" x-model="isPusat" @click="togglePusat()" class="w-4 h-4 border border-gray-300 rounded-sm bg-gray-50 focus:ring-3 focus:ring-primary-300 dark:bg-gray-700 dark:border-gray-600 dark:focus:ring-primary-600 dark:ring-offset-gray-800" />
                    </div>
                    <label for="is_pusat" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Nasional</label>
                </div>

                <!-- Provinsi Dropdown -->
                <div x-show="!isPusat" class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Provinsi</label>
                    <select x-model="selectedProvince" @change="selectedKabkot = ''; updateKdWilayah()" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="" selected>Pilih Provinsi</option>
                        <template x-for="province in provinces" :key="province.kd_wilayah">
                            <option :value="province" x-text="province.nama_wilayah" :selected="province.kd_wilayah == '{{ request('kd_wilayah') }}'"></option>
                        </template>
                    </select>
                </div>

                <!-- Kabkot Dropdown -->
                <div x-show="!isPusat && selectedKdLevel === '01'" class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Kabupaten/Kota</label>
                    <select x-model="selectedKabkot" @change="updateKdWilayah()" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="" selected>Pilih Kabupaten/Kota</option>
                        <template x-for="kabkot in filteredKabkots" :key="kabkot.kd_wilayah">
                            <option :value="kabkot.kd_wilayah" x-text="kabkot.nama_wilayah" :selected="kabkot.kd_wilayah == '{{ request('kd_wilayah') }}'"></option>
                        </template>
                    </select>
                </div>

                <input type="hidden" name="kd_wilayah" :value="isPusat ? '0' : kd_wilayah" required>

                <!-- Komoditas (Not Required) -->
                <div>
                    <label for="komoditas" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Komoditas</label>
                    <select id="komoditas" name="kd_komoditas" x-model="selectedKomoditas" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">Pilih Komoditas</option>
                        <template x-for="komoditi in komoditas" :key="komoditi.kd_komoditas">
                            <option :value="komoditi.kd_komoditas" x-text="komoditi.nama_komoditas" :selected="komoditi.kd_komoditas == '{{ request('kd_komoditas') }}'"></option>
                        </template>
                    </select>
                </div>

                <!-- Buttons -->
                <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:outline-none focus:ring-primary-300 text-white font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">Tampilkan</button>
                <button type="button" class="w-full bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:outline-none focus:ring-primary-300 text-white font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">Tambah</button>
            </div>
        </form>
    </x-slot>



    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900">
                {{ __('Are you sure you want to delete your account?') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="{{ __('Password') }}" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="{{ __('Password') }}"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    {{ __('Delete Account') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3">Kode Komoditas</th>
                        <th scope="col" class="px-6 py-3">Komoditas</th>
                        <th scope="col" class="px-6 py-3">Inflasi/RH</th>
                        <th scope="col" class="px-6 py-3"><span class="sr-only">Edit</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inflasi as $item)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200">
                            <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                {{ $item->kd_komoditas }}
                            </th>
                            <td class="px-6 py-4">
                                {{ $item->komoditas->nama_komoditas }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                {{ number_format($item->harga, 2, '.', '') }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button @click="openModal('{{ $item->id }}', '{{ $item->komoditas->nama_komoditas }}', '{{ $item->harga }}', 'Nasional', 'Harga Konsumen', 'Januari 2024')" class="font-medium text-primary-600 dark:text-primary-500 hover:underline">
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

    @if($inflasi->isNotEmpty())
        <div class="mt-4">
            {{ $inflasi->appends(request()->query())->links() }}
        </div>
    @endif

    <button @click="$dispatch('open-modal', 'confirm-user-deletion')" class="w-full bg-red-600 hover:bg-red-700 focus:ring-4 focus:outline-none focus:ring-red-300 text-white font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800">Delete Account</button>

</x-two-panel-layout>
