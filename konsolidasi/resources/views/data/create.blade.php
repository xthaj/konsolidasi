<x-app-layout>
    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-4 lg:px-6">
            <div class="bg-white shadow-md rounded-lg overflow-hidden dark:bg-gray-800">
                <div class="p-6">

                    <!-- Download Template -->
                    <a href="#" class="text-primary-600 hover:underline font-medium dark:text-primary-500">
                        Download Template
                    </a>

                    <!-- Periode Selection -->
                    <div class="grid grid-cols-2 gap-4 mt-4">
                        <!-- Bulan -->
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Bulan</label>
                            <select class="border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                                @foreach(['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'] as $bulan)
                                    <option>{{ $bulan }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Tahun -->
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Tahun</label>
                            <select class="border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                                @for ($year = 2020; $year <= 2025; $year++)
                                    <option>{{ $year }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <!-- File Upload -->
                    <div class="mt-4">
                        <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white" for="file_input">Upload File</label>
                        <input class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 dark:bg-gray-700 dark:border-gray-600 focus:ring-primary-500 focus:border-primary-500" id="file_input" type="file">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-300">Format: Excel (XLSX, CSV). Maks 5MB.</p>
                    </div>

                    <!-- Submit Button -->
                    <div class="mt-6 text-right">
                        <button class="w-full sm:w-auto px-5 py-2.5 text-white bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
                            Upload Data
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
