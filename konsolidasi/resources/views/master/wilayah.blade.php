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