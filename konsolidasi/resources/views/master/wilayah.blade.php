<x-one-panel-layout>
    @section('vite')
    @vite(['resources/css/app.css', 'resources/js/master/wilayah.js'])
    @endsection

    <!-- Wilayah Table -->
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-lg font-semibold">Master Wilayah</h1>
    </div>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 ">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50  ">
                <tr>
                    <th scope="col" class="px-6 py-3">Kode Wilayah</th>
                    <th scope="col" class="px-6 py-3">Nama Wilayah</th>
                    <th scope="col" class="px-6 py-3">Wilayah Inflasi</th>
                    <th scope="col" class="px-6 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="wilayah in wilayahData" :key="wilayah.kd_wilayah">
                    <tr class="bg-white border-b border-gray-200">
                        <td class="px-6 py-4" x-text="wilayah.kd_wilayah"></td>
                        <td class="px-6 py-4" x-text="wilayah.nama_wilayah"></td>
                        <td class="px-6 py-4 text-center">
                            <input type="checkbox" class="rounded border-gray-300" :checked="!!wilayah.inflasi_tracked" disabled>
                        </td>
                        <td class="px-6 py-4">
                            <button @click="openEditWilayahModal(wilayah)" class="font-medium text-blue-600 hover:underline mr-3">Edit</button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- Success Modal -->
    <x-modal name="success-modal" title="Berhasil" maxWidth="md">
        <div class="text-gray-900 ">
            <p x-text="modalMessage"></p>
            <div class="mt-4 flex justify-end">
                <x-primary-button
                    type="button"
                    x-on:click="$dispatch('close-modal', 'success-modal')">
                    Tutup
                </x-primary-button>
            </div>
        </div>
    </x-modal>

    <!-- Modal for Editing Komoditas -->
    <x-modal name="edit-wilayah" focusable title="Edit Wilayah">
        <div class="px-6 py-4">
            <div class="mb-4">
                <label class="block mb-2 text-sm font-medium text-gray-900">Kode Wilayah</label>
                <input type="text" x-model="editWilayah.kd_wilayah" class="bg-gray-200 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 cursor-not-allowed" disabled>
            </div>
            <div class="mb-4">
                <label class="block mb-2 text-sm font-medium text-gray-900">Nama Wilayah</label>
                <input maxlength="200" type="text" x-model="editWilayah.nama_wilayah" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5" required>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                <x-primary-button @click="updateWilayah">Simpan</x-primary-button>
            </div>
        </div>
    </x-modal>

    <!-- Error Modal -->
    <x-modal name="error-modal" title="Gagal" maxWidth="md">
        <div class="text-gray-900">
            <p x-text="modalMessage"></p>
            <div class="mt-4 flex justify-end">
                <x-primary-button
                    type="button"
                    x-on:click="$dispatch('close-modal', 'error-modal')">
                    Tutup
                </x-primary-button>
            </div>
        </div>
    </x-modal>
</x-one-panel-layout>