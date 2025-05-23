<x-one-panel-layout>
    @section('vite')
    @vite(['resources/css/app.css', 'resources/js/master-akun.js'])
    @endsection

    <!-- Success Modal -->
    <x-modal name="success-update-bulan-tahun" focusable title="Sukses">
        <div class="px-6 py-4">
            <p x-text="successMessage" class="text-green-600"></p>
            <div class="mt-6 flex justify-end">
                <x-primary-button x-on:click="$dispatch('close')">OK</x-primary-button>
            </div>
        </div>
    </x-modal>

    <!-- Fail Modal -->
    <x-modal name="fail-update-bulan-tahun" focusable title="Error">
        <div class="px-6 py-4">
            <p x-text="failMessage" class="text-red-600"></p>
            <template x-if="failDetails">
                <div class="mt-2 text-sm text-gray-600">
                    <p x-text="failDetails.message"></p>
                </div>
            </template>
            <div class="mt-6 flex justify-end">
                <x-primary-button x-on:click="$dispatch('close')">OK</x-primary-button>
            </div>
        </div>
    </x-modal>

    <!-- Add User Modal -->
    <x-modal name="add-user" focusable title="Tambah Pengguna">
        <div class="px-6 py-4">
            <form @submit.prevent="addUser">
                <!-- Nama Lengkap -->
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Nama Lengkap</label>
                    <input
                        type="text"
                        x-model="newUser.nama_lengkap"
                        @input="validateNewUserNamaLengkap()"
                        x-bind:class="{ 'border-red-600': newUser.errors.nama_lengkap }"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5"
                        placeholder="Muhammad Hatta"
                        required>
                    <template x-if="newUser.errors.nama_lengkap">
                        <p class="mt-2 text-sm text-red-600" x-text="newUser.errors.nama_lengkap"></p>
                    </template>
                </div>
                <!-- Username -->
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Username</label>
                    <input
                        type="text"
                        x-model.debounce.500ms="newUser.username"
                        @input="newUser.username = $event.target.value.toLowerCase(); validateNewUserUsername()"
                        x-bind:class="{ 'border-red-600': newUser.errors.username }"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5"
                        placeholder="hatta45"
                        required>
                    <template x-if="newUser.errors.username">
                        <p class="mt-2 text-sm text-red-600" x-text="newUser.errors.username"></p>
                    </template>
                </div>
                <!-- Password -->
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Password</label>
                    <input
                        x-bind:type="newUser.showPassword ? 'text' : 'password'"
                        x-model="newUser.password"
                        @input="validateNewUserPassword()"
                        x-bind:class="{ 'border-red-600': newUser.errors.password }"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5"
                        placeholder="••••••••"
                        maxlength="255"
                        required>
                    <div class="mt-2 flex items-center">
                        <input
                            type="checkbox"
                            x-model="newUser.showPassword"
                            id="show-password"
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">
                        <label for="show-password" class="ms-2 text-sm font-medium text-gray-900">Tampilkan Password</label>
                    </div>
                    <template x-if="newUser.errors.password">
                        <p class="mt-2 text-sm text-red-600" x-text="newUser.errors.password"></p>
                    </template>
                </div>
                <!-- Admin Checkbox -->
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Role</label>
                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            x-model="newUser.isAdminCheckbox"
                            @change="updateNewUserLevel()"
                            id="add-is-admin"
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">
                        <label for="add-is-admin" class="ms-2 text-sm font-medium text-gray-900">Admin</label>
                    </div>
                </div>

                <!-- Wilayah Selection -->
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Level Wilayah</label>
                    <select
                        x-model="newUser.wilayah_level"
                        @change="newUser.selected_province = ''; newUser.selected_kabkot = ''; updateNewUserWilayah(); updateNewUserLevel()"
                        x-bind:class="{ 'border-red-600': newUser.errors.kd_wilayah }"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="pusat">Pusat</option>
                        <option value="provinsi">Provinsi</option>
                        <option value="kabkot">Kabupaten/Kota</option>
                    </select>
                </div>
                <div class="mb-4" x-show="newUser.wilayah_level === 'provinsi' || newUser.wilayah_level === 'kabkot'">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Provinsi</label>
                    <select
                        x-model="newUser.selected_province"
                        @change="newUser.selected_kabkot = ''; updateNewUserWilayah()"
                        x-bind:class="{ 'border-red-600': newUser.errors.kd_wilayah && !newUser.selected_province }"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="">Pilih Provinsi</option>
                        <template x-for="province in provinces" :key="province.kd_wilayah">
                            <option :value="province.kd_wilayah" x-text="province.nama_wilayah"></option>
                        </template>
                    </select>
                </div>
                <div class="mb-4" x-show="newUser.wilayah_level === 'kabkot'">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Kabupaten/Kota</label>
                    <select
                        x-model="newUser.selected_kabkot"
                        @change="updateNewUserWilayah()"
                        x-bind:class="{ 'border-red-600': newUser.errors.kd_wilayah && !newUser.selected_kabkot }"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="">Pilih Kabupaten/Kota</option>
                        <template x-for="kabkot in newUserFilteredKabkots" :key="kabkot.kd_wilayah">
                            <option :value="kabkot.kd_wilayah" x-text="kabkot.nama_wilayah"></option>
                        </template>
                    </select>
                </div>
                <template x-if="newUser.errors.kd_wilayah">
                    <p class="mt-2 text-sm text-red-600" x-text="newUser.errors.kd_wilayah"></p>
                </template>


                <!-- Buttons -->
                <div class="mt-6 flex justify-end gap-3">
                    <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                    <x-primary-button
                        type="submit"
                        x-bind:disabled="newUserHasErrors"
                        x-bind:class="{ 'opacity-50 cursor-not-allowed': newUserHasErrors }">Tambah</x-primary-button>
                </div>
            </form>
        </div>
    </x-modal>

    <!-- Edit User Modal -->
    <x-modal name="edit-user" focusable title="Edit Pengguna">
        <div class="px-6 py-4">
            <form @submit.prevent="updateUser">
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Username</label>
                    <input type="text" x-model="editUser.username" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5" placeholder="hatta45" required>
                    <template x-if="editUser.errors.usernameLength">
                        <p class="mt-2 text-sm text-red-600">Username harus lebih dari 6 karakter.</p>
                    </template>
                    <template x-if="editUser.errors.usernameUnique">
                        <p class="mt-2 text-sm text-red-600">Username sudah digunakan.</p>
                    </template>
                </div>
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Nama Lengkap</label>
                    <input type="text" x-model="editUser.nama_lengkap" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5" placeholder="Muhammad Hatta" required>
                </div>
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Password (kosongkan jika tidak diubah)</label>
                    <input type="password" x-model="editUser.password" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5" placeholder="••••••••">
                    <template x-if="editUser.errors.password">
                        <p class="mt-2 text-sm text-red-600">Password minimal sepanjang 8 karakter.</p>
                    </template>
                </div>
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Konfirmasi Password</label>
                    <input type="password" x-model="editUser.confirmPassword" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5" placeholder="••••••••">
                    <template x-if="editUser.errors.confirmPassword">
                        <p class="mt-2 text-sm text-red-600">Password dan konfirmasi password berbeda.</p>
                    </template>
                </div>
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Level Admin</label>
                    <input type="checkbox" x-model="editUser.is_admin" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">
                </div>
                <!-- <div class="my-4">
                    <div class="flex items-center"> -->
                <!-- problem where value is 1 but it is not checked -->
                <!-- <input type="checkbox" x-model="editUser.is_admin" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">
                        <label class="ms-2 text-sm font-medium text-gray-900">Admin</label>
                    </div>
                    <span x-text="editUser.is_admin"></span>
                </div> -->
                <!-- Wilayah Selection -->

                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Wilayah Saat Ini</label>
                    <p class="text-sm text-gray-900" x-text="editUser.wilayah_nama || 'Pusat'"></p>
                </div>

                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Ganti Level Wilayah</label>
                    <select x-model="editUser.wilayah_level" @change="updateEditWilayah()" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="pusat">Pusat</option>
                        <option value="provinsi">Provinsi</option>
                        <option value="kabkot">Kabupaten/Kota</option>
                    </select>
                </div>
                <div class="mb-4" x-show="editUser.wilayah_level === 'provinsi' || editUser.wilayah_level === 'kabkot'">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Provinsi</label>
                    <select x-model="editUser.selected_province" @change="editUser.selected_kabkot = ''; updateEditWilayah()" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="">Pilih Provinsi</option>
                        <template x-for="province in provinces" :key="province.kd_wilayah">
                            <option :value="province.kd_wilayah" x-text="province.nama_wilayah"></option>
                        </template>
                    </select>
                </div>
                <div class="mb-4" x-show="editUser.wilayah_level === 'kabkot'">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Kabupaten/Kota</label>
                    <select x-model="editUser.selected_kabkot" @change="updateEditWilayah()" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="">Pilih Kabupaten/Kota</option>
                        <template x-for="kabkot in editFilteredKabkots" :key="kabkot.kd_wilayah">
                            <option :value="kabkot.kd_wilayah" x-text="kabkot.nama_wilayah"></option>
                        </template>
                    </select>
                </div>
                <template x-if="editUser.errors.kd_wilayah">
                    <p class="mt-2 text-sm text-red-600">Satuan kerja belum dipilih.</p>
                </template>
                <div class="mt-6 flex justify-end gap-3">
                    <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                    <x-primary-button type="submit">Simpan</x-primary-button>
                </div>
            </form>
        </div>
    </x-modal>

    <!-- Delete Confirmation Modal -->
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

    <!-- Filter and Search Form -->
    <form id="user-form" @submit.prevent="getWilayahUsers"" class=" mb-4">
        <div class="flex gap-4">
            <div class="w-full">
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Cari Pengguna</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                            </svg>
                        </div>
                        <input type="text" name="search" x-model="search" placeholder="Cari berdasarkan username atau nama lengkap" class="block ps-10 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                    </div>
                </div>

                <!-- Wilayah Selection -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">Level Wilayah<span class="text-red-500 ml-1">*</span></label>
                    <select name="level_wilayah" x-model="wilayahLevel" @change="updateWilayahOptions" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="semua">Semua Provinsi dan Kab/Kota</option>
                        <option value="semua-provinsi">Semua Provinsi</option>
                        <option value="semua-kabkot">Semua Kabupaten/Kota</option>
                        <option value="provinsi">Provinsi</option>
                        <option value="kabkot">Kabupaten/Kota</option>
                    </select>
                </div>
                <div x-show="wilayahLevel === 'provinsi' || wilayahLevel === 'kabkot'" class="mt-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Provinsi</label>
                    <select x-model="selectedProvince" @change="selectedKabkot = ''; updateKdWilayah()" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="" selected>Pilih Provinsi</option>
                        <template x-for="province in provinces" :key="province.kd_wilayah">
                            <option :value="province.kd_wilayah" x-text="province.nama_wilayah" :selected="province.kd_wilayah == selectedProvince"></option>
                        </template>
                    </select>
                </div>
                <div x-show="wilayahLevel === 'kabkot' " class="mt-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Kabupaten/Kota</label>
                    <select x-model="selectedKabkot" @change="updateKdWilayah()" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="" selected>Pilih Kabupaten/Kota</option>
                        <template x-for="kabkot in filteredKabkots" :key="kabkot.kd_wilayah">
                            <option :value="kabkot.kd_wilayah" x-text="kabkot.nama_wilayah" :selected="kabkot.kd_wilayah == selectedKabkot"></option>
                        </template>
                    </select>
                </div>
                <input type="hidden" name="kd_wilayah" x-model="kd_wilayah" required>

            </div>
        </div>
        <div class="flex justify-end">
            <x-primary-button
                type="submit">
                Filter
            </x-primary-button>
        </div>
    </form>

    <hr class="h-px my-8 bg-gray-200 border-0 ">

    <div class="flex justify-between items-center mb-4">
        <h1 class="text-lg font-semibold">Daftar Pengguna</h1>
        <x-primary-button
            type="button"
            @click="openAddUserModal">
            Tambah Pengguna
        </x-primary-button>
    </div>


    <!-- Display Users -->
    <div x-show="usersData.length">
        <table class="w-full text-sm text-left text-gray-500">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Nama Lengkap</th>
                    <th>Wilayah</th>
                    <th>Level</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="user in usersData" :key="user.user_id">
                    <tr>
                        <td x-text="user.username"></td>
                        <td x-text="user.nama_lengkap"></td>
                        <td x-text="user.wilayah ? user.wilayah.nama_wilayah : 'Pusat'"></td>
                        <td x-text="user.level"></td>
                        <td class="px-6 py-4 text-left">
                            <button
                                @click="openEditUserModal(user)"
                                class="font-medium text-indigo-600 dark:text-indigo-500 hover:underline">
                                Edit
                            </button>
                            <button
                                @click="deleteUser(user.user_id, user.username)"
                                class="font-medium text-red-600 hover:underline">
                                Delete
                            </button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
        <!-- Pagination Controls -->
        <div class="mt-4" x-show="usersData.length > 0">
            <button x-bind:disabled="currentPage === 1" @click="currentPage--; getWilayahUsers()">Previous</button>
            <span x-text="`Page ${currentPage} of ${lastPage}`"></span>
            <button x-bind:disabled="currentPage === lastPage" @click="currentPage++; getWilayahUsers()">Next</button>
        </div>
    </div>

</x-one-panel-layout>