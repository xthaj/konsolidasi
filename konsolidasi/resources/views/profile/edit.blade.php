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

    <div class="px-6 py-4">
        <h1 class="text-lg font-semibold mb-4">Profil</h1>

        <!-- Profile Information Table -->
        <div class="mb-6">
            <h2 class="text-md font-medium text-gray-900 mb-2">Informasi Profil</h2>
            <table class="w-full text-sm text-left text-gray-900 border border-gray-300 rounded-lg">
                <tbody>
                    <tr class="bg-gray-50">
                        <th class="px-4 py-2 font-medium text-gray-900 border-r border-gray-300">Username</th>
                        <td class="px-4 py-2">{{ $user->username }}</td>
                    </tr>
                    <tr class="bg-white">
                        <th class="px-4 py-2 font-medium text-gray-900 border-r border-gray-300">Nama Lengkap</th>
                        <td class="px-4 py-2">{{ $user->nama_lengkap }}</td>
                    </tr>
                    @if ($user->wilayah_level != 'pusat')
                    <tr class="bg-gray-50">
                        <th class="px-4 py-2 font-medium text-gray-900 border-r border-gray-300">Wilayah</th>
                        <td class="px-4 py-2">{{ $nama_wilayah === 'NASIONAL' ? 'Pusat' : $nama_wilayah }}</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <hr class="h-px my-8 bg-gray-200 border-0">

        <!-- Change PW Section -->
        <div class="my-4">
            <h1 class="text-lg font-semibold">Ganti Password</h1>
        </div>

        <!-- Password Update Form -->
        <form @submit.prevent="updateProfile">
            <div class="mb-4">
                <label class="block mb-2 text-sm font-medium text-gray-900">Password baru</label>
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
            <div class="mt-6 flex justify-end gap-3">
                <x-primary-button type="submit">Ubah Password</x-primary-button>
            </div>
        </form>
    </div>
</x-one-panel-layout>