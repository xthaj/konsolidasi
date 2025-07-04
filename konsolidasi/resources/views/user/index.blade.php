<x-one-panel-layout>
    @section('vite')
    @vite(['resources/css/app.css', 'resources/js/administrasi/user.js'])
    @endsection

    <x-modal name="add-user" focusable title="Tambah Akun">
        <div class="px-6 py-4">
            <form @submit.prevent="addUser">
                <!-- SSO vs Non-SSO Radio Buttons -->
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Tipe Akun</label>
                    <div class="flex items-center gap-4">
                        <div class="flex items-center">
                            <input
                                type="radio"
                                x-model="newUser.isSSO"
                                value="false"
                                id="non-sso"
                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300"
                                @change="newUser.isSSO = false; newUser.username = ''; newUser.nama_lengkap = ''; newUser.password = ''; newUser.searchSSOUsername = ''; newUser.ssoSearchResults = []; newUser.errors = { username: false, nama_lengkap: false, password: false, kd_wilayah: false, level: false }"
                                checked>
                            <label for="non-sso" class="ms-2 text-sm font-medium text-gray-900">Non-SSO</label>
                        </div>
                        <div class="flex items-center">
                            <input
                                type="radio"
                                x-model="newUser.isSSO"
                                value="true"
                                id="sso"
                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300"
                                @change="newUser.isSSO = true; newUser.username = ''; newUser.nama_lengkap = ''; newUser.password = ''; newUser.searchSSOUsername = ''; newUser.ssoSearchResults = []; newUser.errors = { username: false, nama_lengkap: false, password: false, kd_wilayah: false, level: false }">
                            <label for="sso" class="ms-2 text-sm font-medium text-gray-900">SSO</label>
                        </div>
                    </div>
                </div>

                <!-- Non-SSO Form Fields -->
                <div x-show="!newUser.isSSO">
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-900">Username</label>
                        <input
                            type="text"
                            x-model.debounce.750ms="newUser.username"
                            @input="newUser.username = $event.target.value.toLowerCase(); validateNewUserUsername();"
                            x-bind:class="{ 'border-red-600': newUser.errors.username }"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5"
                            placeholder="hatta45"
                            maxlength="20"
                            x-bind:required="!newUser.isSSO">
                        <template x-if="newUser.errors.username">
                            <p class="mt-2 text-sm text-red-600" x-text="newUser.errors.username"></p>
                        </template>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-900">Nama Lengkap</label>
                        <input
                            type="text"
                            x-model="newUser.nama_lengkap"
                            @input="validateNewUserNamaLengkap()"
                            x-bind:class="{ 'border-red-600': newUser.errors.nama_lengkap }"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5"
                            placeholder="Muhammad Hatta"
                            maxlength="200"
                            x-bind:required="!newUser.isSSO">
                        <template x-if="newUser.errors.nama_lengkap">
                            <p class="mt-2 text-sm text-red-600" x-text="newUser.errors.nama_lengkap"></p>
                        </template>
                    </div>

                    <div class="mb-4">
                        <label class="mb-2 text-sm font-medium text-gray-900">Password</label>
                        <input
                            x-bind:type="newUser.showPassword ? 'text' : 'password'"
                            x-model="newUser.password"
                            @input="validateNewUserPassword()"
                            x-bind:class="{ 'border-red-600': newUser.errors.password }"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5"
                            placeholder="••••••••"
                            maxlength="255"
                            x-bind:required="!newUser.isSSO">
                        <div class="mt-2 flex items-center">
                            <input
                                type="checkbox"
                                x-model="newUser.showPassword"
                                id="show-password"
                                class="rounded border-gray-300">
                            <label for="show-password" class="ms-2 text-sm font-medium text-gray-900">Tampilkan Password</label>
                        </div>
                        <template x-if="newUser.errors.password">
                            <p class="mt-2 text-sm text-red-600" x-text="newUser.errors.password"></p>
                        </template>
                    </div>
                </div>

                <!-- SSO Form Fields -->
                <div x-show="newUser.isSSO">
                    <div class="mb-4">
                        <label for="sso-search" class="block mb-2 text-sm font-medium text-gray-900">Cari User SSO</label>
                        <div class="relative">
                            <div class="text-gray-500 absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                <span class="material-symbols-rounded">search</span>
                            </div>
                            <input
                                type="text"
                                id="sso-search"
                                x-model="newUser.searchSSOUsername"
                                class="block w-full p-3 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Username">
                            <button
                                type="button"
                                @click="searchSSOUser()"
                                class="text-white absolute end-2 bottom-1 bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span x-show="!newUser.isSearching">Search</span>
                                <span x-show="newUser.isSearching">Loading...</span>
                            </button>
                        </div>
                        <template x-if="newUser.errors.username">
                            <p class="mt-2 text-sm text-red-600" x-text="newUser.errors.username"></p>
                        </template>
                    </div>
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-900">Username</label>
                        <input
                            type="text"
                            x-model="newUser.username"
                            class="cursor-not-allowed bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-3"
                            x-bind:required="newUser.isSSO"
                            disabled>
                    </div>
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-900">Nama Lengkap</label>
                        <input
                            type="text"
                            x-model="newUser.nama_lengkap"
                            class="cursor-not-allowed bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-3"
                            x-bind:required="newUser.isSSO"
                            disabled>
                        <template x-if="newUser.errors.nama_lengkap">
                            <p class="mt-2 text-sm text-red-600" x-text="newUser.errors.nama_lengkap"></p>
                        </template>
                    </div>
                </div>

                <!-- Common Fields for Both SSO and Non-SSO -->
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Role</label>
                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            x-model="newUser.isAdminCheckbox"
                            @change="updateNewUserLevel()"
                            id="add-is-admin"
                            class="rounded border-gray-300">
                        <label for="add-is-admin" class="ms-2 text-sm font-medium text-gray-900">Admin</label>
                    </div>
                </div>

                <!-- Role-based Wilayah Filtering -->
                @if (auth()->user()->isPusat())
                <!-- Level Wilayah for Pusat -->
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
                <!-- Province Select -->
                <div class="mb-4" x-show="newUser.wilayah_level === 'provinsi' || newUser.wilayah_level === 'kabkot'">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Provinsi<span class="text-red-500 ml-1">*</span></label>
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
                <!-- City Select -->
                <div class="mb-4" x-show="newUser.wilayah_level === 'kabkot'">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Kabupaten/Kota<span class="text-red-500 ml-1">*</span></label>
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
                @elseif (auth()->user()->isProvinsi())
                <!-- Level Wilayah for Provinsi -->
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Level Wilayah<span class="text-red-500 ml-1">*</span></label>
                    <select
                        x-model="newUser.wilayah_level"
                        @change="newUser.selected_kabkot = ''; updateNewUserWilayah(); updateNewUserLevel()"
                        x-bind:class="{ 'border-red-600': newUser.errors.kd_wilayah }"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="provinsi">Provinsi</option>
                        <option value="kabkot">Kabupaten/Kota</option>
                    </select>
                </div>
                <!-- Province Select (Disabled) -->
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Provinsi<span class="text-red-500 ml-1">*</span></label>
                    <select
                        x-model="newUser.selected_province"
                        class="cursor-not-allowed bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5"
                        disabled>
                        <option :value="selectedProvince" x-text="provinces.find(p => p.kd_wilayah === selectedProvince)?.nama_wilayah || 'Pilih Provinsi'" selected></option>
                    </select>
                </div>
                <!-- City Select -->
                <div class="mb-4" x-show="newUser.wilayah_level === 'kabkot'">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Kabupaten/Kota<span class="text-red-500 ml-1">*</span></label>
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
                @else
                <!-- Level Wilayah (Disabled) -->
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Level Wilayah<span class="text-red-500 ml-1">*</span></label>
                    <select
                        x-model="newUser.wilayah_level"
                        class="cursor-not-allowed bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5"
                        disabled>
                        <option value="kabkot" selected>Kabupaten/Kota</option>
                    </select>
                </div>
                <!-- Province Select (Disabled) -->
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Provinsi<span class="text-red-500 ml-1">*</span></label>
                    <select
                        x-model="newUser.selected_province"
                        class="cursor-not-allowed bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5"
                        disabled>
                        <option :value="selectedProvince" x-text="provinces.find(p => p.kd_wilayah === selectedProvince)?.nama_wilayah || 'Pilih Provinsi'" selected></option>
                    </select>
                </div>
                <!-- City Select (Disabled) -->
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Kabupaten/Kota<span class="text-red-500 ml-1">*</span></label>
                    <select
                        x-model="newUser.selected_kabkot"
                        class="cursor-not-allowed bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5"
                        disabled>
                        <option :value="selectedKabkot" x-text="kabkots.find(k => k.kd_wilayah === selectedKabkot)?.nama_wilayah || 'Pilih Kabupaten/Kota'" selected></option>
                    </select>
                </div>
                @endif

                <template x-if="newUser.errors.kd_wilayah">
                    <p class="mt-2 text-sm text-red-600" x-text="newUser.errors.kd_wilayah"></p>
                </template>
                <!-- Buttons -->
                <div class="mt-6 flex justify-end gap-3">
                    <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                    <x-primary-button type="submit">Tambah</x-primary-button>
                </div>
            </form>
        </div>
    </x-modal>

    <x-modal name="edit-user" focusable title="Edit Akun">
        <div class="px-6 py-4">
            <!-- Username Form -->
            <form @submit.prevent="updateUserAttribute('username')">
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Username</label>
                    <input
                        x-bind:disabled="editUser.user_sso === 1"
                        type="text"
                        x-model="editUser.username"
                        x-bind:class="{ 'border-red-600': editUser.errors.usernameLength || editUser.errors.usernameUnique }"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5"
                        placeholder="hatta45"
                        maxlength="20"
                        required>
                    <template x-if="editUser.errors.usernameLength">
                        <p class="mt-2 text-sm text-red-600" x-text="editUser.errors.usernameLength"></p>
                    </template>
                    <template x-if="editUser.errors.usernameUnique">
                        <p class="mt-2 text-sm text-red-600" x-text="editUser.errors.usernameUnique"></p>
                    </template>
                    <div class="mt-2 flex justify-end" x-show="editUser.user_sso !== 1">
                        <x-primary-button type="submit">Update Username</x-primary-button>
                    </div>
                </div>
            </form>

            <!-- Nama Lengkap Form -->
            <form @submit.prevent="updateUserAttribute('nama_lengkap')">
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Nama Lengkap</label>
                    <input
                        x-bind:disabled="editUser.user_sso === 1"
                        type="text"
                        x-model="editUser.nama_lengkap"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5"
                        placeholder="Muhammad Hatta"
                        maxlength="200"
                        required>
                    <div class="mt-2 flex justify-end" x-show="editUser.user_sso !== 1">
                        <x-primary-button type="submit">Update Nama Lengkap</x-primary-button>
                    </div>
                </div>
            </form>

            <!-- Password Form -->
            <form @submit.prevent="updateUserAttribute('password')" x-show="editUser.user_sso !== 1">
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Password</label>
                    <input
                        type="password"
                        x-model="editUser.password"
                        x-bind:class="{ 'border-red-600': editUser.errors.password }"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5"
                        placeholder="••••••••"
                        maxlength="200">
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
                                class="rounded border-gray-300">
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
                    <label class="block mb-2 text-sm font-medium text-gray-900">Cari Akun</label>
                    <div class="relative">
                        <div class="text-gray-500 absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                            <span class="material-symbols-rounded">search</span>
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
                    <!-- Add conditional logic for user roles -->
                    @if (auth()->user()->isPusat())
                    <!-- Level Wilayah -->
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-900">Level Wilayah</label>
                        <select
                            name="level_wilayah"
                            x-model="wilayahLevel"
                            @change="selectedProvince = ''; selectedKabkot = ''; updateWilayahOptions()"
                            required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                            <!-- <option value="">Pilih Level Wilayah</option> -->
                            <option value="pusat">Pusat</option>
                            <option value="provinsi">Provinsi</option>
                            <option value="kabkot">Kabupaten/Kota</option>
                        </select>
                    </div>
                    <!-- Province Select -->
                    <div x-show="wilayahLevel === 'provinsi' || wilayahLevel === 'kabkot'" class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-900">Provinsi<span class="text-red-500 ml-1">*</span></label>
                        <select
                            x-model="selectedProvince"
                            @change="selectedKabkot = ''; updateWilayahOptions()"
                            x-bind:class="{ 'border-red-600': errors.kd_wilayah && !selectedProvince }"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                            <option value="">Pilih Provinsi</option>
                            <template x-for="province in provinces" :key="province.kd_wilayah">
                                <option :value="province.kd_wilayah" x-text="province.nama_wilayah"></option>
                            </template>
                        </select>
                    </div>
                    <!-- City Select -->
                    <div x-show="wilayahLevel === 'kabkot'" class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-900">Kabupaten/Kota<span class="text-red-500 ml-1">*</span></label>
                        <select
                            x-model="selectedKabkot"
                            @change="updateWilayahOptions()"
                            x-bind:class="{ 'border-red-600': errors.kd_wilayah && !selectedKabkot }"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                            <option value="">Pilih Kabupaten/Kota</option>
                            <template x-for="kabkot in filteredKabkots" :key="kabkot.kd_wilayah">
                                <option :value="kabkot.kd_wilayah" x-text="kabkot.nama_wilayah"></option>
                            </template>
                        </select>
                    </div>
                    @elseif (auth()->user()->isProvinsi())
                    <!-- Level Wilayah -->
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-900">Level Wilayah<span class="text-red-500 ml-1">*</span></label>
                        <select
                            name="level_wilayah"
                            x-model="wilayahLevel"
                            @change="selectedKabkot = ''; updateWilayahOptions()"
                            required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                            <option value="provinsi">Provinsi</option>
                            <option value="kabkot">Kabupaten/Kota</option>
                        </select>
                    </div>
                    <!-- Province Select -->
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-900">Provinsi<span class="text-red-500 ml-1">*</span></label>
                        <select
                            x-model="selectedProvince"
                            class="cursor-not-allowed bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5"
                            disabled>
                            <option :value="selectedProvince" x-text="provinces.find(p => p.kd_wilayah === selectedProvince)?.nama_wilayah || 'Pilih Provinsi'" selected></option>
                        </select>
                    </div>
                    <!-- City Select -->
                    <div x-show="wilayahLevel === 'kabkot'" class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-900">Kabupaten/Kota<span class="text-red-500 ml-1">*</span></label>
                        <select
                            x-model="selectedKabkot"
                            @change="updateWilayahOptions()"
                            x-bind:class="{ 'border-red-600': errors.kd_wilayah && !selectedKabkot }"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                            <option value="">Pilih Kabupaten/Kota</option>
                            <template x-for="kabkot in filteredKabkots" :key="kabkot.kd_wilayah">
                                <option :value="kabkot.kd_wilayah" x-text="kabkot.nama_wilayah"></option>
                            </template>
                        </select>
                    </div>
                    @else
                    <!-- Level Wilayah -->
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-900">Level Wilayah<span class="text-red-500 ml-1">*</span></label>
                        <select
                            name="level_wilayah"
                            x-model="wilayahLevel"
                            class="cursor-not-allowed bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5"
                            disabled>
                            <option value="kabkot" selected>Kabupaten/Kota</option>
                        </select>
                    </div>
                    <!-- Province Select -->
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-900">Provinsi<span class="text-red-500 ml-1">*</span></label>
                        <select
                            x-model="selectedProvince"
                            class="cursor-not-allowed bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5"
                            disabled>
                            <option :value="selectedProvince" x-text="provinces.find(p => p.kd_wilayah === selectedProvince)?.nama_wilayah || 'Pilih Provinsi'" selected></option>
                        </select>
                    </div>
                    <!-- City Select -->
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-900">Kabupaten/Kota<span class="text-red-500 ml-1">*</span></label>
                        <select
                            x-model="selectedKabkot"
                            class="cursor-not-allowed bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5"
                            disabled>
                            <option :value="selectedKabkot" x-text="kabkots.find(k => k.kd_wilayah === selectedKabkot)?.nama_wilayah || 'Pilih Kabupaten/Kota'" selected></option>
                        </select>
                    </div>
                    @endif

                    <template x-if="errors.kd_wilayah">
                        <p class="mt-2 text-sm text-red-600" x-text="errors.kd_wilayah"></p>
                    </template>
                    <input type="hidden" name="kd_wilayah" x-model="kd_wilayah">
                </div>
            </div>
        </div>
        <div class="flex justify-end mt-4">
            <x-primary-button type="submit">Filter</x-primary-button>
        </div>
    </form>

    <hr class="h-px my-8 bg-gray-200 border-0">

    <div class="flex justify-between items-center mb-4">
        <h1 class="text-lg font-semibold">Daftar Akun</h1>
        <x-primary-button type="button" @click="openAddUserModal">Tambah Akun</x-primary-button>
    </div>

    <!-- Display Users -->
    <div x-show="!usersData?.length" class="bg-white px-6 py-4 rounded-lg shadow-sm text-center text-gray-500">
        <div class="mb-1">
            <h2 class="text-lg font-semibold mb-2" x-text="message"></h2>
        </div>
        <span>Klik "filter" untuk menampilkan data</span>
    </div>

    <div x-show="usersData?.length">
        <div class="mb-4 text-sm text-gray-700">
            <span x-text="`Akun ditemukan: ${totalData}`"></span>
        </div>

        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <table class="w-full text-sm text-left text-gray-500 ">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 ">
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
                        <tr class="bg-white border-b border-gray-200 hover:bg-gray-50">
                            <td class="px-6 py-4" x-text="user.username"></td>
                            <td class="px-6 py-4" x-text="user.nama_lengkap"></td>
                            <td class="px-6 py-4" x-text="user.nama_wilayah"></td>
                            <td class="px-6 py-4" x-text="user.level"></td>
                            <td class="px-6 py-4 text-left">
                                <button
                                    @click="openEditUserModal(user)"
                                    class="font-medium text-indigo-600  hover:underline">
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
            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed">
            Sebelumnya
        </button>
        <span x-text="`Halaman ${currentPage} dari ${lastPage}`"></span>
        <button
            x-bind:disabled="currentPage === lastPage"
            @click="currentPage++; getWilayahUsers()"
            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed">
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