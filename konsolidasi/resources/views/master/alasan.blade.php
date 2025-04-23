<x-one-panel-layout>
    @section('vite')
    @vite(['resources/css/app.css', 'resources/js/master/alasan.js'])
    @endsection

    <!-- Success Modal (kept for potential future use) -->
    <x-modal name="success-update-alasan" focusable title="Sukses">
        <div class="px-6 py-4">
            <p x-text="successMessage" class="text-green-600"></p>
            <div class="mt-6 flex justify-end">
                <x-primary-button x-on:click="$dispatch('close')">OK</x-primary-button>
            </div>
        </div>
    </x-modal>

    <!-- Fail Modal (kept for potential future use) -->
    <x-modal name="fail-update-alasan" focusable title="Error">
        <div class="px-6 py-4">
            <p x-text="failMessage" class="text-red-600"></p>
            <div class="mt-6 flex justify-end">
                <x-primary-button x-on:click="$dispatch('close')">OK</x-primary-button>
            </div>
        </div>
    </x-modal>

    <!-- Alasan Table -->
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-lg font-semibold">Master Alasan</h1>
    </div>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">No</th>
                    <th scope="col" class="px-6 py-3">Nama Alasan</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(alasan, index) in alasanList" :key="index">
                    <tr class="bg-white border-b border-gray-200">
                        <td class="px-6 py-4" x-text="index + 1"></td>
                        <td class="px-6 py-4" x-text="alasan"></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</x-one-panel-layout>