<x-one-panel-layout>

    @section('vite')
    @vite(['resources/css/app.css', 'resources/js/alpine-init.js', 'resources/js/pengaturan.js', 'resources/js/alpine-start.js'])
    @endsection

    <!-- New Success Modal -->
    <x-modal name="success-update-bulan-tahun" focusable title="Success">
        <div class="px-6 py-4">
            <p x-text="successMessage" class="text-green-600"></p>
            <div class="mt-6 flex justify-end">
                <x-primary-button x-on:click="$dispatch('close')">OK</x-primary-button>
            </div>
        </div>
    </x-modal>

    <!-- New Fail Modal -->
    <x-modal name="fail-update-bulan-tahun" focusable title="Error">
        <div class="px-6 py-4">
            <p x-text="failMessage" class="text-red-600"></p>
            <template x-if="failDetails">
                <div class="mt-2 text-sm text-gray-600">
                    <p><strong>Requested:</strong> <span x-text="failDetails.requested_bulan"></span> - <span x-text="failDetails.requested_tahun"></span></p>
                    <p><strong>Current Active:</strong> <span x-text="failDetails.current_active_bulan"></span> - <span x-text="failDetails.current_active_tahun"></span></p>
                    <p><strong>Hint:</strong> <span x-text="failDetails.hint"></span></p>
                </div>
            </template>
            <div class="mt-6 flex justify-end">
                <x-primary-button x-on:click="$dispatch('close')">OK</x-primary-button>
            </div>
        </div>
    </x-modal>

    <!-- Modal for Adding Komoditas -->
    <x-modal name="add-komoditas" focusable title="Konfirmasi Penambahan Komoditas">
        <div class="px-6 py-4">
            <div class="mb-4">
                <label class="block mb-2 text-sm font-medium text-gray-900">Nama Komoditas</label>
                <input type="text" x-model="newKomoditas.nama_komoditas" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5" required>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                <x-primary-button @click="addKomoditas">Tambah</x-primary-button>
            </div>
        </div>
    </x-modal>

    <!-- Modal for Editing Komoditas -->
    <x-modal name="edit-komoditas" focusable title="Edit Komoditas">
        <div class="px-6 py-4">
            <div class="mb-4">
                <label class="block mb-2 text-sm font-medium text-gray-900">Kode Komoditas</label>
                <input type="text" x-model="editKomoditas.kd_komoditas" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5" disabled>
            </div>
            <div class="mb-4">
                <label class="block mb-2 text-sm font-medium text-gray-900">Nama Komoditas</label>
                <input type="text" x-model="editKomoditas.nama_komoditas" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5" read>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                <x-primary-button @click="updateKomoditas">Simpan</x-primary-button>
            </div>
        </div>
    </x-modal>

    <!-- Bulan Tahun Aktif Section -->
    <div class="mb-4">
        <h1 class="text-lg font-semibold">Bulan dan Tahun Aktif</h1>

        <div class="flex gap-4">
            <!-- Bulan -->
            <div class="w-1/2">
                <label class="block mb-2 text-sm font-medium text-gray-900">Bulan</label>
                <select name="bulan" x-model="bulan" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                    @foreach(['Januari' => '01', 'Februari' => '02', 'Maret' => '03', 'April' => '04', 'Mei' => '05', 'Juni' => '06', 'Juli' => '07', 'Agustus' => '08', 'September' => '09', 'Oktober' => '10', 'November' => '11', 'Desember' => '12'] as $nama => $bln)
                    <option value="{{ $bln }}" @selected(request('bulan')==$bln)>{{ $nama }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Tahun -->
            <div class="w-1/2">
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Tahun</label>
                <select name="tahun" x-model="tahun" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                    <option value="">Pilih Tahun</option>
                    <template x-for="year in tahunOptions" :key="year">
                        <option :value="year" :selected="year === tahun" x-text="year"></option>
                    </template>
                </select>
            </div>
        </div>

        <p id="helper-text-explanation" class="mt-2 text-sm text-gray-500" x-show="isActivePeriod">Periode aktif</p>

        <!-- Ganti Bulan Tahun Aktif Button -->
        <button class="mt-4 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg text-sm px-5 py-2.5" @click="updateBulanTahun">Ganti Bulan Tahun Aktif</button>
    </div>

    <hr class="h-px my-8 bg-gray-200 border-0 dark:bg-gray-700">

    <!-- Komoditas Table -->
    <!-- <div class="mb-6"> -->
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-lg font-semibold">Daftar Komoditas</h1>
        <button class="bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg text-sm px-5 py-2.5" @click="openAddKomoditasModal">Tambah Komoditas</button>
    </div>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">Kode Komoditas</th>
                    <th scope="col" class="px-6 py-3">Nama Komoditas</th>
                    <th scope="col" class="px-6 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="komoditas in komoditasData" :key="komoditas.kd_komoditas">
                    <tr class="bg-white border-b border-gray-200">
                        <td class="px-6 py-4" x-text="komoditas.kd_komoditas"></td>
                        <td class="px-6 py-4" x-text="komoditas.nama_komoditas"></td>
                        <td class="px-6 py-4 text-right">
                            <button @click="openEditKomoditasModal(komoditas)" class="font-medium text-blue-600 dark:text-blue-500 hover:underline mr-3">Edit</button>
                            <button @click="deleteKomoditas(komoditas.kd_komoditas)" class="font-medium text-red-600 dark:text-red-500 hover:underline">Hapus</button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</x-one-panel-layout>