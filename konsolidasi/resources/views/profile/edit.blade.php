<x-one-panel-layout>
    @section('vite')
    @vite(['resources/css/app.css', 'resources/js/edit-akun.js'])
    @endsection

    <!-- Success Modal -->
    <x-modal name="success-update-profile" focusable title="Sukses">
        <div class="px-6 py-4">
            <p x-text="successMessage" class="text-green-600"></p>
            <div class="mt-6 flex justify-end">
                <x-primary-button x-on:click="$dispatch('close')">OK</x-primary-button>
            </div>
        </div>
    </x-modal>

    <!-- Fail Modal -->
    <x-modal name="fail-update-profile" focusable title="Error">
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

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-6 py-4">
                    <h1 class="text-lg font-semibold mb-4">Edit Profil</h1>
                    <form @submit.prevent="updateProfile" class="max-w-xl">
                        <div class="mb-4">
                            <label class="block mb-2 text-sm font-medium text-gray-900">Username</label>
                            <input type="text" x-model="profile.username" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5" placeholder="hatta45" required>
                            <template x-if="profile.errors.usernameLength">
                                <p class="mt-2 text-sm text-red-600">Username harus lebih dari 6 karakter.</p>
                            </template>
                            <template x-if="profile.errors.usernameUnique">
                                <p class="mt-2 text-sm text-red-600">Username sudah digunakan.</p>
                            </template>
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 text-sm font-medium text-gray-900">Nama Lengkap</label>
                            <input type="text" x-model="profile.nama_lengkap" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5" placeholder="Muhammad Hatta" required>
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 text-sm font-medium text-gray-900">Password (kosongkan jika tidak diubah)</label>
                            <input type="password" x-model="profile.password" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5" placeholder="••••••••">
                            <template x-if="profile.errors.password">
                                <p class="mt-2 text-sm text-red-600">Password minimal sepanjang 6 karakter.</p>
                            </template>
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 text-sm font-medium text-gray-900">Konfirmasi Password</label>
                            <input type="password" x-model="profile.confirmPassword" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5" placeholder="••••••••">
                            <template x-if="profile.errors.confirmPassword">
                                <p class="mt-2 text-sm text-red-600">Password dan konfirmasi password berbeda.</p>
                            </template>
                        </div>
                        <!-- Wilayah Selection (optional, include if user can edit their wilayah) -->
                        @if (auth()->user()->wilayah_level != 'pusat')
                        <div class="mb-4">
                            <label class="block mb-2 text-sm font-medium text-gray-900">Level Wilayah</label>
                            <select x-model="profile.wilayah_level" @change="updateWilayah()" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                                <option value="pusat">Pusat</option>
                                <option value="provinsi">Provinsi</option>
                                <option value="kabkot">Kabupaten/Kota</option>
                            </select>
                        </div>
                        <div class="mb-4" x-show="profile.wilayah_level === 'provinsi' || profile.wilayah_level === 'kabkot'">
                            <label class="block mb-2 text-sm font-medium text-gray-900">Provinsi</label>
                            <select x-model="profile.selected_province" @change="profile.selected_kabkot = ''; updateWilayah()" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5" disabled>
                                <option value="">Pilih Provinsi</option>
                                <template x-for="province in provinces" :key="province.kd_wilayah">
                                    <option :value="province.kd_wilayah" x-text="province.nama_wilayah"></option>
                                </template>
                            </select>
                        </div>
                        <div class="mb-4" x-show="profile.wilayah_level === 'kabkot'">
                            <label class="block mb-2 text-sm font-medium text-gray-900">Kabupaten/Kota</label>
                            <select x-model="profile.selected_kabkot" @change="updateWilayah()" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5" disabled>
                                <option value="">Pilih Kabupaten/Kota</option>
                                <template x-for="kabkot in filteredKabkots" :key="kabkot.kd_wilayah">
                                    <option :value="kabkot.kd_wilayah" x-text="kabkot.nama_wilayah"></option>
                                </template>
                            </select>
                        </div>
                        <template x-if="profile.errors.kd_wilayah">
                            <p class="mt-2 text-sm text-red-600">Satuan kerja belum dipilih.</p>
                        </template>
                        @endif
                        <div class="mt-6 flex justify-end gap-3">
                            <x-secondary-button x-on:click="resetForm">Batal</x-secondary-button>
                            <x-primary-button type="submit">Simpan</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-one-panel-layout>