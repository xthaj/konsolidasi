<x-one-panel-layout>
    @section('vite')
    @vite(['resources/css/app.css', 'resources/js/master-akun.js'])
    @endsection


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

    <x-modal name="edit-user" focusable title="Edit Pengguna">
        <div class="px-6 py-4">
            <!-- Username Form -->
            <form @submit.prevent="updateUserAttribute('username')">
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Username</label>
                    <input
                        type="text"
                        x-model="editUser.username"
                        x-bind:class="{ 'border-red-600': editUser.errors.usernameLength || editUser.errors.usernameUnique }"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5"
                        placeholder="hatta45"
                        required>
                    <template x-if="editUser.errors.usernameLength">
                        <p class="mt-2 text-sm text-red-600">Username harus lebih dari 6 karakter.</p>
                    </template>
                    <template x-if="editUser.errors.usernameUnique">
                        <p class="mt-2 text-sm text-red-600" x-text="editUser.errors.usernameUnique"></p>
                    </template>
                    <div class="mt-2 flex justify-end">
                        <x-primary-button type="submit">Update Username</x-primary-button>
                    </div>
                </div>
            </form>

            <!-- Nama Lengkap Form -->
            <form @submit.prevent="updateUserAttribute('nama_lengkap')">
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Nama Lengkap</label>
                    <input
                        type="text"
                        x-model="editUser.nama_lengkap"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5"
                        placeholder="Muhammad Hatta"
                        required>
                    <div class="mt-2 flex justify-end">
                        <x-primary-button type="submit">Update Nama Lengkap</x-primary-button>
                    </div>
                </div>
            </form>

            <!-- Password Form -->
            <form @submit.prevent="updateUserAttribute('password')">
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Password</label>
                    <input
                        type="password"
                        x-model="editUser.password"
                        x-bind:class="{ 'border-red-600': editUser.errors.password }"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5"
                        placeholder="••••••••">
                    <template x-if="editUser.errors.password">
                        <p class="mt-2 text-sm text-red-600">Password minimal sepanjang 6 karakter.</p>
                    </template>
                </div>
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Konfirmasi Password</label>
                    <input
                        type="password"
                        x-model="editUser.confirmPassword"
                        x-bind:class="{ 'border-red-600': editUser.errors.confirmPassword }"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5"
                        placeholder="••••••••">
                    <template x-if="editUser.errors.confirmPassword">
                        <p class="mt-2 text-sm text-red-600">Password dan konfirmasi password berbeda.</p>
                    </template>
                    <div class="mt-2 flex justify-end">
                        <x-primary-button type="submit">Update Password</x-primary-button>
                    </div>
                </div>
            </form>

            <!-- Role Form -->
            <form @submit.prevent="updateUserAttribute('role')">
                <div class="mb-4">
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-900">Level Saat Ini</label>
                        <p class="text-sm text-gray-900" x-text="editUser.level"></p>
                    </div>
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-900">
                            <input
                                type="checkbox"
                                x-model="editUser.role_toggle"
                                id="edit-role-toggle"
                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">
                            <span x-text="editUser.initial_role_label" class="ms-2 text-sm font-medium text-gray-900"></span>
                        </label>
                    </div>
                    <div class="mt-2 flex justify-end">
                        <x-primary-button type="submit">Update Role</x-primary-button>
                    </div>
                </div>
            </form>

            <!-- Wilayah Form -->
            <form @submit.prevent="updateUserAttribute('wilayah')">
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Wilayah Saat Ini</label>
                    <p class="text-sm text-gray-900" x-text="editUser.nama_wilayah"></p>
                </div>
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Ganti Level Wilayah</label>
                    <select
                        x-model="editUser.wilayah_level"
                        @change="editUser.selected_province = ''; editUser.selected_kabkot = ''; updateEditWilayah()"
                        x-bind:class="{ 'border-red-600': editUser.errors.kd_wilayah }"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="pusat">Pusat</option>
                        <option value="provinsi">Provinsi</option>
                        <option value="kabkot">Kabupaten/Kota</option>
                    </select>
                </div>
                <div class="mb-4" x-show="editUser.wilayah_level === 'provinsi' || editUser.wilayah_level === 'kabkot'">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Provinsi</label>
                    <select
                        x-model="editUser.selected_province"
                        @change="editUser.selected_kabkot = ''; updateEditWilayah()"
                        x-bind:class="{ 'border-red-600': editUser.errors.kd_wilayah && !editUser.selected_province }"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="">Pilih Provinsi</option>
                        <template x-for="province in provinces" :key="province.kd_wilayah">
                            <option :value="province.kd_wilayah" x-text="province.nama_wilayah"></option>
                        </template>
                    </select>
                </div>
                <div class="mb-4" x-show="editUser.wilayah_level === 'kabkot'">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Kabupaten/Kota</label>
                    <select
                        x-model="editUser.selected_kabkot"
                        @change="updateEditWilayah()"
                        x-bind:class="{ 'border-red-600': editUser.errors.kd_wilayah && !editUser.selected_kabkot }"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="">Pilih Kabupaten/Kota</option>
                        <template x-for="kabkot in editFilteredKabkots" :key="kabkot.kd_wilayah">
                            <option :value="kabkot.kd_wilayah" x-text="kabkot.nama_wilayah"></option>
                        </template>
                    </select>
                </div>
                <template x-if="editUser.errors.kd_wilayah">
                    <p class="mt-2 text-sm text-red-600">Satuan kerja belum dipilih.</p>
                </template>
                <div class="mt-2 flex justify-end">
                    <x-primary-button type="submit">Update Wilayah</x-primary-button>
                </div>
            </form>

            <!-- Close Button -->
            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">Tutup</x-secondary-button>
            </div>
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
    <form id="user-form" @submit.prevent="getWilayahUsers(true)" class="mb-4">
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
                        <input
                            type="text"
                            name="search"
                            x-model="search"
                            placeholder="Cari berdasarkan username atau nama lengkap"
                            class="block ps-10 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                    </div>
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">Level Wilayah<span class="text-red-500 ml-1">*</span></label>
                    <select
                        name="level_wilayah"
                        x-model="wilayahLevel"
                        @change="updateWilayahOptions"
                        required
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="pusat">Pusat</option>
                        <option value="semua">Semua Provinsi dan Kab/Kota</option>
                        <option value="semua-provinsi">Semua Provinsi</option>
                        <option value="semua-kabkot">Semua Kabupaten/Kota</option>
                        <option value="provinsi">Provinsi</option>
                        <option value="kabkot">Kabupaten/Kota</option>
                    </select>
                </div>
                <div x-show="wilayahLevel === 'provinsi' || wilayahLevel === 'kabkot'" class="mt-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Provinsi</label>
                    <select
                        x-model="selectedProvince"
                        @change="selectedKabkot = ''; updateKdWilayah()"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="">Pilih Provinsi</option>
                        <template x-for="province in provinces" :key="province.kd_wilayah">
                            <option :value="province.kd_wilayah" x-text="province.nama_wilayah" :selected="province.kd_wilayah == selectedProvince"></option>
                        </template>
                    </select>
                </div>
                <div x-show="wilayahLevel === 'kabkot'" class="mt-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Kabupaten/Kota</label>
                    <select
                        x-model="selectedKabkot"
                        @change="updateKdWilayah()"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="">Pilih Kabupaten/Kota</option>
                        <template x-for="kabkot in filteredKabkots" :key="kabkot.kd_wilayah">
                            <option :value="kabkot.kd_wilayah" x-text="kabkot.nama_wilayah" :selected="kabkot.kd_wilayah == selectedKabkot"></option>
                        </template>
                    </select>
                </div>
                <input type="hidden" name="kd_wilayah" x-model="kd_wilayah">
            </div>
        </div>
        <div class="flex justify-end mt-4">
            <x-primary-button type="submit">Filter</x-primary-button>
        </div>
    </form>

    <hr class="h-px my-8 bg-gray-200 border-0">

    <div class="flex justify-between items-center mb-4">
        <h1 class="text-lg font-semibold">Daftar Pengguna</h1>
        <x-primary-button type="button" @click="openAddUserModal">Tambah Pengguna</x-primary-button>
    </div>

    <!-- Display Users -->
    <div x-show="usersData.length === 0" class="bg-white px-6 py-4 rounded-lg shadow-sm text-center text-gray-500">
        <div class="mb-1">
            <h2 class="text-lg font-semibold mb-2">Isi filter untuk menampilkan data</h2>
        </div>
        <span x-text="message"></span>
    </div>
    <div x-show="usersData.length > 0">
        <div class="mb-4 text-sm text-gray-700">
            <span x-text="`Pengguna ditemukan: ${totalData}`"></span>
        </div>

        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3">Username</th>
                        <th scope="col" class="px-6 py-3">Nama Lengkap</th>
                        <th scope="col" class="px-6 py-3">Wilayah</th>
                        <th scope="col" class="px-6 py-3">Level</th>
                        <th scope="col" class="px-6 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="user in usersData" :key="user.user_id">
                        <tr class="bg-white border-b border-gray-200">
                            <td class="px-6 py-4" x-text="user.username"></td>
                            <td class="px-6 py-4" x-text="user.nama_lengkap"></td>
                            <td class="px-6 py-4" x-text="user.nama_wilayah"></td>
                            <td class="px-6 py-4" x-text="user.level"></td>
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
        </div>
    </div>
    <!-- Pagination Controls -->
    <div class="mt-4 flex justify-between items-center" x-show="usersData.length > 0">
        <button
            x-bind:disabled="currentPage === 1"
            @click="currentPage--; getWilayahUsers()"
            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg disabled:opacity-50">
            Sebelumnya
        </button>
        <span x-text="`Halaman ${currentPage} dari ${lastPage}`"></span>
        <button
            x-bind:disabled="currentPage === lastPage"
            @click="currentPage++; getWilayahUsers()"
            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg disabled:opacity-50">
            Selanjutnya
        </button>
    </div>

    <x-modal name="success-modal" title="Berhasil" maxWidth="md">
        <div class="text-gray-900 ">
            <p x-text="modalMessage"></p>
            <div class="mt-4 flex justify-end">
                <x-primary-button type="button" x-on:click="$dispatch('close')">Tutup</x-primary-button>
            </div>
        </div>
    </x-modal>

    <x-modal name="error-modal" title="Kesalahan" maxWidth="md">
        <div class="text-gray-900 ">
            <p x-text="modalMessage"></p>
            <div class="mt-4 flex justify-end">
                <x-primary-button type="button" x-on:click="$dispatch('close')">Tutup</x-primary-button>
            </div>
        </div>
    </x-modal>

</x-one-panel-layout>