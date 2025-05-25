<x-one-panel-layout>
    @section('vite')
    @vite(['resources/css/app.css', 'resources/js/master/wilayah.js'])
    @endsection

    <!-- Success Modal -->
    <x-modal name="success-modal" title="Berhasil" maxWidth="md">
        <div class="text-gray-900 dark:text-white">
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

    <!-- Error Modal -->
    <x-modal name="error-modal" title="Gagal" maxWidth="md">
        <div class="text-gray-900 dark:text-white">
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

    <!-- Modal for Adding Wilayah -->
    <x-modal name="add-wilayah" focusable title="Tambah Wilayah">
        <div class="px-6 py-4">
            <div class="mb-4">
                <label class="block mb-2 text-sm font-medium text-gray-900">Nama Wilayah</label>
                <input type="text" x-model="newWilayah.nama_wilayah" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5" required>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                <x-primary-button @click="addWilayah">Tambah</x-primary-button>
            </div>
        </div>
    </x-modal>

    <!-- Modal for Editing Wilayah -->
    <x-modal name="edit-wilayah" focusable title="Edit Wilayah">
        <div class="px-6 py-4">
            <div class="mb-4">
                <label class="block mb-2 text-sm font-medium text-gray-900">Kode Wilayah (tidak bisa diubah)</label>
                <input type="text" x-model="editWilayah.kd_wilayah" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5" disabled>
            </div>
            <div class="mb-4">
                <label class="block mb-2 text-sm font-medium text-gray-900">Nama Wilayah</label>
                <input type="text" x-model="editWilayah.nama_wilayah" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5" required>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                <x-primary-button @click="updateWilayah">Simpan</x-primary-button>
            </div>
        </div>
    </x-modal>

    <!-- Modal for Confirming Deletion -->
    <x-modal name="confirm-action" focusable title="Konfirmasi Penghapusan">
        <div class="px-6 py-4">
            <div class="mb-4">
                <p class="text-sm text-gray-900" x-text="confirmMessage"></p>
                <p class="text-sm text-gray-600 mt-2" x-text="confirmDetails"></p>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                <x-primary-button @click="confirmAction(); $dispatch('close')">Hapus</x-primary-button>
            </div>
        </div>
    </x-modal>

    <!-- Wilayah Table -->
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-lg font-semibold">Master Wilayah</h1>
        <button class="bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg text-sm px-5 py-2.5" @click="openAddWilayahModal">Tambah Wilayah</button>
    </div>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">Kode Wilayah</th>
                    <th scope="col" class="px-6 py-3">Nama Wilayah</th>
                    <th scope="col" class="px-6 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="wilayah in wilayahData" :key="wilayah.kd_wilayah">
                    <tr class="bg-white border-b border-gray-200">
                        <td class="px-6 py-4" x-text="wilayah.kd_wilayah"></td>
                        <td class="px-6 py-4" x-text="wilayah.nama_wilayah"></td>
                        <td class="px-6 py-4 text-right">
                            <button @click="openEditWilayahModal(wilayah)" class="font-medium text-blue-600 dark:text-blue-500 hover:underline mr-4">Edit</button>
                            <button @click="deleteWilayah(wilayah.kd_wilayah, wilayah.nama_wilayah)" class="font-medium text-red-600 dark:text-red-500 hover:underline">Hapus</button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</x-one-panel-layout>