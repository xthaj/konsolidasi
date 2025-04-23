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
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Username</label>
                    <input type="text" x-model="newUser.username" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5" placeholder="hatta45" required>
                    <template x-if="newUser.errors.usernameLength">
                        <p class="mt-2 text-sm text-red-600">Username harus lebih dari 6 karakter.</p>
                    </template>
                    <template x-if="newUser.errors.usernameUnique">
                        <p class="mt-2 text-sm text-red-600">Username sudah digunakan.</p>
                    </template>
                </div>
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Nama Lengkap</label>
                    <input type="text" x-model="newUser.nama_lengkap" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5" placeholder="Muhammad Hatta" required>
                </div>
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Password</label>
                    <input type="password" x-model="newUser.password" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5" placeholder="••••••••" required>
                    <template x-if="newUser.errors.password">
                        <p class="mt-2 text-sm text-red-600">Password minimal sepanjang 6 karakter.</p>
                    </template>
                </div>
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Konfirmasi Password</label>
                    <input type="password" x-model="newUser.confirmPassword" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5" placeholder="••••••••" required>
                    <template x-if="newUser.errors.confirmPassword">
                        <p class="mt-2 text-sm text-red-600">Password dan konfirmasi password berbeda.</p>
                    </template>
                </div>
                <div class="my-4">
                    <div class="flex items-center">
                        <input type="checkbox" x-model="editUser.is_admin" id="edit-is-admin" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">
                        <label for="edit-is-admin" class="ms-2 text-sm font-medium text-gray-900">Admin</label>
                    </div>
                </div>
                <!-- Wilayah Selection -->
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Level Wilayah</label>
                    <select x-model="newUser.wilayah_level" @change="updateNewUserWilayah()" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="pusat">Pusat</option>
                        <option value="provinsi">Provinsi</option>
                        <option value="kabkot">Kabupaten/Kota</option>
                    </select>
                </div>
                <div class="mb-4" x-show="newUser.wilayah_level === 'provinsi' || newUser.wilayah_level === 'kabkot'">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Provinsi</label>
                    <select x-model="newUser.selected_province" @change="newUser.selected_kabkot = ''; updateNewUserWilayah()" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="">Pilih Provinsi</option>
                        <template x-for="province in provinces" :key="province.kd_wilayah">
                            <option :value="province.kd_wilayah" x-text="province.nama_wilayah"></option>
                        </template>
                    </select>
                </div>
                <div class="mb-4" x-show="newUser.wilayah_level === 'kabkot'">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Kabupaten/Kota</label>
                    <select x-model="newUser.selected_kabkot" @change="updateNewUserWilayah()" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="">Pilih Kabupaten/Kota</option>
                        <template x-for="kabkot in newUserFilteredKabkots" :key="kabkot.kd_wilayah">
                            <option :value="kabkot.kd_wilayah" x-text="kabkot.nama_wilayah"></option>
                        </template>
                    </select>
                </div>
                <template x-if="newUser.errors.kd_wilayah">
                    <p class="mt-2 text-sm text-red-600">Satuan kerja belum dipilih.</p>
                </template>
                <div class="mt-6 flex justify-end gap-3">
                    <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                    <x-primary-button type="submit">Tambah</x-primary-button>
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
                    <label class="block mb-2 text-sm font-medium text-gray-900">Level Wilayah</label>
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
    <form method="GET" action="{{ route('akun.index') }}" class="mb-4">
        <div class="flex gap-4">
            <div class="w-full">
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Cari Pengguna</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-500 " aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                            </svg>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari berdasarkan username atau nama lengkap" class="block ps-10 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Level Wilayah</label>
                    <select name="wilayah_level" x-model="wilayah_level" @change="selected_province = ''; selected_kabkot = ''" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="" {{ request('wilayah_level') ? '' : 'selected' }}>Semua Level</option>
                        <option value="pusat" {{ request('wilayah_level') === 'pusat' ? 'selected' : '' }}>Pusat</option>
                        <option value="provinsi" {{ request('wilayah_level') === 'provinsi' ? 'selected' : '' }}>Provinsi</option>
                        <option value="kabkot" {{ request('wilayah_level') === 'kabkot' ? 'selected' : '' }}>Kabupaten/Kota</option>
                    </select>
                </div>
                <div class="mb-4" x-show="wilayah_level === 'provinsi' || wilayah_level === 'kabkot'">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Provinsi</label>
                    <select name="kd_wilayah_provinsi" x-model="selected_province" @change="selected_kabkot = ''" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="" {{ request('kd_wilayah_provinsi') ? '' : 'selected' }}>Semua Provinsi</option>
                        <template x-for="province in provinces" :key="province.kd_wilayah">
                            <option :value="province.kd_wilayah" :selected="province.kd_wilayah === '{{ request('kd_wilayah_provinsi') }}'" x-text="province.nama_wilayah"></option>
                        </template>
                    </select>
                </div>
                <div class="mb-4" x-show="wilayah_level === 'kabkot'">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Kabupaten/Kota</label>
                    <select name="kd_wilayah" x-model="selected_kabkot" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="" {{ request('kd_wilayah') ? '' : 'selected' }}>Semua Kabupaten/Kota</option>
                        <template x-for="kabkot in filteredKabkots" :key="kabkot.kd_wilayah">
                            <option :value="kabkot.kd_wilayah" :selected="kabkot.kd_wilayah === '{{ request('kd_wilayah') }}'" x-text="kabkot.nama_wilayah"></option>
                        </template>
                    </select>
                </div>
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

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 ">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">No</th>
                    <th scope="col" class="px-6 py-3">Wilayah</th>
                    <th scope="col" class="px-6 py-3">Username</th>
                    <th scope="col" class="px-6 py-3">Nama Lengkap</th>
                    <th scope="col" class="px-6 py-3">Level</th>
                    <th scope="col" class="px-6 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users->items() as $index => $user)
                <tr class="bg-white border-b border-gray-200">
                    <td class="px-6 py-4">{{ $users->firstItem() + $index }}</td>
                    <td class="px-6 py-4">
                        {{ optional($user->wilayah)->nama_wilayah == 'NASIONAL' ? 'PUSAT' : ($user->wilayah->nama_wilayah ?? 'PUSAT') }}
                    </td>

                    <td class="px-6 py-4">{{ $user->username }}</td>
                    <td class="px-6 py-4">{{ $user->nama_lengkap }}</td>
                    <td class="px-6 py-4">{{ $user->is_admin ? 'Admin' : 'Operator' }}</td>
                    <td class="px-6 py-4 text-right">
                        <button @click="openEditUserModal({{$user}})" class="font-medium text-blue-600  hover:underline mr-3">Edit</button>
                        <button @click="deleteUser('{{ $user->user_id }}', '{{ $user->username }}')" class="font-medium text-red-600  hover:underline">Hapus</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center">Tidak ada pengguna ditemukan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>


    </div>

    <!-- Pagination -->
    @if ($users && $users->hasPages())
    <div class="mt-4 ">
        {{ $users->appends(request()->query())->links() }}
    </div>
    @endif


</x-one-panel-layout>