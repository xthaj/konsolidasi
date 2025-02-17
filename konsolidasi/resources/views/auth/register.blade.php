<x-guest-layout>
    <h1 class="text-center text-xl font-bold leading-tight tracking-tight text-gray-900 md:text-2xl dark:text-white">
        Buat Akun
    </h1>

    <form x-data="registerData" method="POST" action="{{ route('register') }}" class="space-y-4 md:space-y-6">
        @csrf

        <!-- Full Name -->
        <div>
            <label for="nama_lengkap" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Nama lengkap</label>
            <input type="text" id="nama_lengkap" name="nama_lengkap" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600  placeholder-gray-400 dark:placeholder-gray-900 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Muhammad Hatta" required />
        </div>


        <!-- Username for like unique or not -->
        <div>
            <label for="username" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Username</label>
            <input type="text" id="username" name="username" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-400 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="hatta45" required />
        </div>

        {{--        <div class="mb-6">--}}
        {{--            <label for="success" class="block mb-2 text-sm font-medium text-green-700 dark:text-green-500">Username</label>--}}
        {{--            <input type="text" id="success" class="bg-green-50 border border-green-500 text-green-900 dark:text-green-400 placeholder-green-700 dark:placeholder-green-500 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5 dark:bg-gray-700 dark:border-green-500" placeholder="Success input">--}}
        {{--            <p class="mt-2 text-sm text-green-600 dark:text-green-500"><span class="font-medium">Well done!</span> Some success message.</p>--}}
        {{--        </div>--}}
        {{--        <div>--}}
        {{--            <label for="error" class="block mb-2 text-sm font-medium text-red-700 dark:text-red-500">Your name</label>--}}
        {{--            <input type="text" id="error" class="bg-red-50 border border-red-500 text-red-900 placeholder-red-700 text-sm rounded-lg focus:ring-red-500 dark:bg-gray-700 focus:border-red-500 block w-full p-2.5 dark:text-red-500 dark:placeholder-red-500 dark:border-red-500" placeholder="Error input">--}}
        {{--            <p class="mt-2 text-sm text-red-600 dark:text-red-500"><span class="font-medium">Oh, snapp!</span> Some error message.</p>--}}
        {{--        </div>--}}



        <!-- Is Pusat Checkbox -->

        <div x-data="{ isPusat: false }">
            <!-- Checkbox -->
            <div class="flex items-start mb-6">
                <div class="flex items-center h-5">
                    <input type="hidden" name="is_pusat" value="0"> <input type="checkbox" name="is_pusat" id="is_pusat" value="1" x-model="isPusat" @click="togglePusat()" class="w-4 h-4 border border-gray-300 rounded-sm bg-gray-50 focus:ring-3 focus:ring-primary-300 dark:bg-gray-700 dark:border-gray-600 dark:focus:ring-primary-600 dark:ring-offset-gray-800" />
                </div>
                <label for="is_pusat" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Satuan Kerja Pusat</label>
            </div>

            <!-- Debugging: Display the value of isPusat -->
{{--            <p class="text-sm text-gray-500">isPusat: <span x-text="isPusat"></span></p>--}}

            <!-- Wilayah Section (Only Show If isPusat is False) -->
            <div :class="{ 'hidden': isPusat }">
                <div class="flex relative flex-col">
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Satuan Kerja</label>

                    <div class="flex">
                        <button @click="toggleDropdown('province')" id="provinsi-button" class="shrink-0 z-10 inline-flex items-center py-2.5 px-4 text-sm font-medium text-center text-gray-500 bg-gray-100 border border-gray-300 rounded-s-lg hover:bg-gray-200 focus:ring-4 focus:outline-none focus:ring-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 dark:focus:ring-gray-700 dark:text-white dark:border-gray-600" type="button">
                            <span x-text="selectedProvince.nama_wilayah || 'Pilih Provinsi'"></span>
                        </button>

                        <!-- Province Dropdown -->
                        <div x-show="dropdowns.province" x-transition @click.away="closeDropdown('province')" class="z-10 bg-white divide-y divide-gray-100 rounded-lg shadow-sm w-44 dark:bg-gray-700 absolute mt-2 max-h-60 overflow-y-auto">
                            <ul class="py-2 text-sm text-gray-700 dark:text-gray-200 ">
                                <template x-for="province in provinces" :key="province.kd_wilayah">
                                    <li>
                                        <button @click="selectProvince(province)" type="button" class="inline-flex text-left w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
                                            <span x-text="province.nama_wilayah"></span>
                                        </button>
                                    </li>
                                </template>
                            </ul>
                        </div>

                        <!-- Kabupaten Dropdown -->
                        <label for="kabkot" class="sr-only">Pilih Kabupaten</label>
                        <select id="kabkot" x-model="selectedKabkot" @change="updateKdWilayah()" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-e-lg border-s-gray-100 dark:border-s-gray-700 border-s-2 focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-400 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                            <option value="" selected>Pilih Kabupaten</option>
                            <template x-for="kabkot in filteredKabkots" :key="kabkot.kd_wilayah">
                                <option :value="kabkot.kd_wilayah" x-text="kabkot.nama_wilayah"></option>
                            </template>
                        </select>

                        <input type="hidden" name="kd_wilayah" x-model="kd_wilayah">
                    </div>
                </div>
            </div>
        </div>


        <!-- Wilayah (Optional) -->
        {{--        ini untuk yang BUKAN pusat, kalo klik checkbox nanti disabled. terus yg country ganti provinsi (flag =2) terus yg kanan kab dengan parent dr prov itu--}}


        <!-- Password & confirm -->
        <div>
            <label for="password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Password</label>
            <input type="password" name="password" id="password" placeholder="••••••••" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-400 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" required="">
        </div>
        <div>
            <label for="confirm-password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Konfirmasi password</label>
            <input type="password"  id="confirm-password" placeholder="••••••••" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-400 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" required="">
        </div>


        <button type="submit" class="w-full text-white bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">Buat Akun</button>
        <p class="text-sm font-light text-gray-500 dark:text-gray-400">
            Sudah memiliki akun? <a href="#" class="font-medium text-primary-600 hover:underline dark:text-primary-500">Login di sini</a>
        </p>
    </form>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('registerData', () => ({
                provinces: @json($wilayah->where('flag', 2)->values()), // Load all provinces
                kabkots: @json($wilayah->where('flag', 3)->values()), // Load all kab/kot
                selectedProvince: {},
                selectedKabkot: '',
                dropdowns: { province: false },

                isPusat: false,
                kd_wilayah:'',

                // Computed property: Filter kabupaten based on selected province
                get filteredKabkots() {
                    if (!this.selectedProvince.kd_wilayah) return [];
                    return this.kabkots.filter(k => k.parent_kd == this.selectedProvince.kd_wilayah);
                },

                // Select a province
                selectProvince(province) {
                    this.selectedProvince = province;
                    this.selectedKabkot = ''; // Reset kabkot
                    this.closeDropdown('province');
                    this.updateKdWilayah(); // Call updateKdWilayah here!
                },

                // Dropdown handlers
                toggleDropdown(menu) {
                    this.dropdowns[menu] = !this.dropdowns[menu];
                },
                closeDropdown(menu) {
                    this.dropdowns[menu] = false;
                },
                // Update kd_wilayah when kabkot is selected
                updateKdWilayah() {
                    if (this.isPusat) {
                        this.kd_wilayah = '1'; // Pusat
                    } else if (this.selectedKabkot) {
                        this.kd_wilayah = this.selectedKabkot; // Kabupaten/Kota
                    } else if (this.selectedProvince.kd_wilayah) {
                        this.kd_wilayah = this.selectedProvince.kd_wilayah; // Province
                    } else {
                        this.kd_wilayah = ''; // Default empty
                    }
                },

                // Watch changes in isPusat
                togglePusat() {
                    this.updateKdWilayah(); // Call updateKdWilayah
                },

            }));
        });
    </script>

</x-guest-layout>
