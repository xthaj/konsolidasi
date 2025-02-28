<x-guest-layout>
    <h1 class="text-center text-xl font-bold leading-tight tracking-tight text-gray-900 md:text-2xl dark:text-white">
        Buat Akun
    </h1>

    <div x-data="registerData">
    <form  x-init="init()" class="space-y-4">
        @csrf

        <!-- Full Name -->
        <div>
            <label for="nama_lengkap" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Nama lengkap</label>
            <input type="text" id="nama_lengkap" name="nama_lengkap" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600  placeholder-gray-400 dark:placeholder-gray-900 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Muhammad Hatta" required />
        </div>


        <!-- Username -->
        <div>
            <label for="username" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Username</label>
            <input type="text" id="username" name="username" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-400 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="hatta45" required />
        </div>

        <div x-data="{ isPusat: false }">
            <!-- Checkbox -->
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Satuan Kerja</label>

            <div class="flex items-start my-2">
                <div class="flex items-center h-5">
                    <input type="hidden" name="is_pusat" value="0"> <input type="checkbox" name="is_pusat" id="is_pusat" value="1" x-model="isPusat" @click="togglePusat()" class="w-4 h-4 border border-gray-300 rounded-sm bg-gray-50 focus:ring-3 focus:ring-primary-300 dark:bg-gray-700 dark:border-gray-600 dark:focus:ring-primary-600 dark:ring-offset-gray-800" />
                </div>
                <label for="is_pusat" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Pusat</label>
            </div>


            <div :class="{ 'hidden': isPusat }">
                <div class="flex relative flex-col">
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
                    <p id="helper-text-explanation" class="mt-2 text-sm text-gray-500 dark:text-gray-400">Pilih hanya provinsi atau provinsi kemudian Kab/Kota</p>
                </div>
            </div>
        </div>


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
    </div>

    <script>
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM fully loaded');
    document.addEventListener('alpine:init', () => {
        console.log('alpine:init event fired');
        Alpine.data('registerData', () => ({
            provinces: [],
            kabkots: [],
            selectedProvince: {},
            selectedKabkot: '',
            dropdowns: { province: false },
            isPusat: false,
            kd_wilayah: '',

            async init() {
                try {
                    let response = await fetch('/api/wilayah');
                    let data = await response.json();
                    this.provinces = data.provinces;
                    this.kabkots = data.kabkots;
                } catch (error) {
                    console.error("Failed to load wilayah data:", error);
                }
            },

            get filteredKabkots() {
                if (!this.selectedProvince.kd_wilayah) return [];
                return this.kabkots.filter(k => k.parent_kd == this.selectedProvince.kd_wilayah);
            },

            selectProvince(province) {
                this.selectedProvince = province;
                this.selectedKabkot = '';
                this.closeDropdown('province');
                this.updateKdWilayah();
            },

            toggleDropdown(menu) {
                this.dropdowns[menu] = !this.dropdowns[menu];
            },

            closeDropdown(menu) {
                this.dropdowns[menu] = false;
            },

            updateKdWilayah() {
                if (this.isPusat) {
                    this.kd_wilayah = '1';
                } else if (this.selectedKabkot) {
                    this.kd_wilayah = this.selectedKabkot;
                } else if (this.selectedProvince.kd_wilayah) {
                    this.kd_wilayah = this.selectedProvince.kd_wilayah;
                } else {
                    this.kd_wilayah = '';
                }
            },

            togglePusat() {
                this.updateKdWilayah();
            },
        }));
    });
});

    </script>

</x-guest-layout>
