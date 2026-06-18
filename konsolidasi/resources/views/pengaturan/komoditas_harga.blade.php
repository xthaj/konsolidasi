<x-one-panel-layout>

    @section('vite')
    @vite(['resources/css/app.css', 'resources/js/pengaturan/komoditas_harga.js'])
    @endsection

    <!-- Komoditas Table -->
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-lg font-semibold">Komoditas Harga</h1>
    </div>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50  ">
                <tr>
                    <th scope="col" class="px-6 py-3">Kode</th>
                    <th scope="col" class="px-6 py-3">Nama Komoditas</th>
                    <th scope="col" class="px-6 py-3">Tampil di Menu Harga</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="komoditas in komoditasData" :key="komoditas.kd_komoditas">
                    <tr class="bg-white border-b border-gray-200 hover:bg-gray-50">
                        <td class="px-6 py-4" x-text="komoditas.kd_komoditas"></td>
                        <td class="px-6 py-4" x-text="komoditas.nama_komoditas"></td>
                        <td class="px-6 py-4">
                            <input type="checkbox"
                                :checked="komoditas.is_harga"
                                @change="toggleHarga(komoditas.kd_komoditas, $event.target.checked)"
                                class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500 cursor-pointer">
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <x-modal name="success-modal" title="Berhasil" maxWidth="md">
        <div class="text-gray-900">
            <p x-text="modalMessage"></p>
            <div class="mt-4 flex justify-end">
                <x-primary-button type="button" x-on:click="$dispatch('close')">Tutup</x-primary-button>
            </div>
        </div>
    </x-modal>

    <x-modal name="error-modal" title="Kesalahan" maxWidth="md">
        <div class="text-gray-900">
            <p x-text="modalMessage"></p>
            <div class="mt-4 flex justify-end">
                <x-primary-button type="button" x-on:click="$dispatch('close')">Tutup</x-primary-button>
            </div>
        </div>
    </x-modal>

</x-one-panel-layout>