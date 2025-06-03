<x-one-panel-layout>
    @section('vite')
    @vite(['resources/css/app.css', 'resources/js/master/alasan.js'])
    @endsection

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

    <!-- Modal for Adding Alasan -->
    <x-modal name="add-alasan" focusable title="Konfirmasi Penambahan Alasan">
        <div class="px-6 py-4">
            <div class="mb-4">
                <label class="block mb-2 text-sm font-medium text-gray-900">Alasan</label>
                <input maxlength="200" type="text" x-model="newAlasan.keterangan" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5" required>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                <x-primary-button @click="addAlasan">Tambah</x-primary-button>
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
        <div class="text-gray-900 ">
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

    <!-- Alasan Table -->
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-lg font-semibold">Master Alasan</h1>
        <button class="bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg text-sm px-5 py-2.5" @click="openAddAlasanModal">Tambah Alasan</button>
    </div>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 ">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50  ">
                <tr>
                    <th scope="col" class="px-6 py-3">No</th>
                    <th scope="col" class="px-6 py-3">Alasan</th>
                    <th scope="col" class="px-6 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(alasan, index) in alasanData" :key="alasan.alasan_id">
                    <tr class="bg-white border-b border-gray-200">
                        <td class="px-6 py-4" x-text="index + 1"></td>
                        <td class="px-6 py-4" x-text="alasan.keterangan"></td>
                        <td class="px-6 py-4">
                            <button @click="deleteAlasan(alasan.alasan_id)" class="font-medium text-red-600  hover:underline">Hapus</button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</x-one-panel-layout>