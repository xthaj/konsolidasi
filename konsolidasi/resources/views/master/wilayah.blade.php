<x-one-panel-layout>
    @section('vite')
    @vite(['resources/css/app.css', 'resources/js/master/wilayah.js'])
    @endsection

    <!-- Success Modal -->
    <x-modal name="success-update-wilayah" focusable title="Sukses">
        <div class="px-6 py-4">
            <p x-text="successMessage" class="text-green-600"></p>
            <div class="mt-6 flex justify-end">
                <x-primary-button x-on:click="$dispatch('close')">OK</x-primary-button>
            </div>
        </div>
    </x-modal>

    <!-- Fail Modal -->
    <x-modal name="fail-update-wilayah" focusable title="Error">
        <div class="px-6 py-4">
            <p x-text="failMessage" class="text-red-600"></p>
            <div class="mt-6 flex justify-end">
                <x-primary-button x-on:click="$dispatch('close')">OK</x-primary-button>
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

    <!-- Wilayah Table -->
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-lg font-semibold">Master Wilayah</h1>
    </div>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">Kode Wilayah</th>
                    <th scope="col" class="px-6 py-3">Nama Wilayah</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="wilayah in wilayahData" :key="wilayah.kd_wilayah">
                    <tr class="bg-white border-b border-gray-200">
                        <td class="px-6 py-4" x-text="wilayah.kd_wilayah"></td>
                        <td class="px-6 py-4" x-text="wilayah.nama_wilayah"></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</x-one-panel-layout>