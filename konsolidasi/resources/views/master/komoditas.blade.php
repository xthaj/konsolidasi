<x-one-panel-layout>

    @section('vite')
    @vite(['resources/css/app.css', 'resources/js/master/komoditas.js'])
    @endsection

    <!-- Komoditas Table -->
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-lg font-semibold">Daftar Komoditas</h1>
        <button class="bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg text-sm px-5 py-2.5" @click="openAddKomoditasModal">Tambah Komoditas</button>
    </div>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50  ">
                <tr>
                    <th scope="col" class="px-6 py-3">Kode Komoditas</th>
                    <th scope="col" class="px-6 py-3">Nama Komoditas</th>
                    <th scope="col" class="px-6 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="komoditas in komoditasData" :key="komoditas.kd_komoditas">
                    <tr class="bg-white border-b border-gray-200 hover:bg-gray-50">
                        <td class="px-6 py-4" x-text="komoditas.kd_komoditas"></td>
                        <td class="px-6 py-4" x-text="komoditas.nama_komoditas"></td>
                        <td class="px-6 py-4">
                            <button @click="openEditKomoditasModal(komoditas)" class="font-medium text-blue-600 hover:underline mr-3">Edit</button>
                            <button @click="deleteKomoditas(komoditas.kd_komoditas)" class="font-medium text-red-600  hover:underline">Hapus</button>
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

    <!-- Modal for Adding Komoditas -->
    <x-modal name="add-komoditas" focusable title="Konfirmasi Penambahan Komoditas">
        <div class="px-6 py-4">
            <div class="mb-4">
                <label class="block mb-2 text-sm font-medium text-gray-900">Nama Komoditas</label>
                <input maxlength="200" type="text" x-model="newKomoditas.nama_komoditas" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5" required>
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
                <input type="text" x-model="editKomoditas.kd_komoditas" class="bg-gray-200 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 cursor-not-allowed" disabled>
            </div>
            <div class="mb-4">
                <label class="block mb-2 text-sm font-medium text-gray-900">Nama Komoditas</label>
                <input maxlength="200" type="text" x-model="editKomoditas.nama_komoditas" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5" required>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                <x-primary-button @click="updateKomoditas">Simpan</x-primary-button>
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