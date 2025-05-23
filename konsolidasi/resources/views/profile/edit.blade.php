<x-one-panel-layout>
    @section('vite')
    @vite(['resources/css/app.css', 'resources/js/edit-akun.js'])
    @endsection

    <!-- Success Modal -->
    <x-modal name="success-modal" title="Berhasil" maxWidth="md">
        <div class="text-gray-900 ">
            <p x-text="modalMessage"></p>
            <div class="mt-4 flex justify-end">
                <x-primary-button type="button" x-on:click="$dispatch('close')">Tutup</x-primary-button>
            </div>
        </div>
    </x-modal>

    <!-- Error Modal -->
    <x-modal name="error-modal" title="Kesalahan" maxWidth="md">
        <div class="text-gray-900 ">
            <p x-text="modalMessage"></p>
            <div class="mt-4 flex justify-end">
                <x-primary-button type="button" x-on:click="$dispatch('close')">Tutup</x-primary-button>
            </div>
        </div>
    </x-modal>

    <div class="px-6 py-4">
        <h1 class="text-lg font-semibold mb-4">Profil</h1>
        <!-- Profile Information Table -->
        <div class="mb-8 space-y-4">
            <div class="flex items-center justify-between border-b border-gray-200 pb-3">
                <span class="text-sm font-medium text-gray-600">Username</span>
                <span class="text-sm text-gray-900">{{ $username }}</span>
            </div>
            <div class="flex items-center justify-between border-b border-gray-200 pb-3">
                <span class="text-sm font-medium text-gray-600">Nama Lengkap</span>
                <span class="text-sm text-gray-900">{{ $nama_lengkap }}</span>
            </div>
            <div class="flex items-center justify-between border-b border-gray-200 pb-3">
                <span class="text-sm font-medium text-gray-600">Wilayah</span>
                <span class="text-sm text-gray-900">{{ $nama_wilayah === 'NASIONAL' ? 'Pusat' : $nama_wilayah }}</span>
            </div>
        </div>

        <hr class="h-px my-8 bg-gray-200 border-0">

        <!-- Change PW Section -->
        <div class="my-4">
            <h1 class="text-lg font-semibold">Ganti Password</h1>
        </div>

        <!-- Password Update Form -->
        <form id="password-form" @submit.prevent="updatePassword">
            <div class="mb-4">
                <label for="password" class="block mb-2 text-sm font-medium text-gray-900">Password Baru</label>
                <input
                    id="password"
                    type="password"
                    x-model="password"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5"
                    placeholder="••••••••">
                <template x-if="errors.password">
                    <p class="mt-2 text-sm text-red-600">Password minimal sepanjang 6 karakter.</p>
                </template>
            </div>
            <div class="mb-4">
                <label for="confirmPassword" class="block mb-2 text-sm font-medium text-gray-900">Konfirmasi Password</label>
                <input
                    id="confirmPassword"
                    type="password"
                    x-model="confirmPassword"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5"
                    placeholder="••••••••">
                <template x-if="errors.confirmPassword">
                    <p class="mt-2 text-sm text-red-600">Password dan konfirmasi password berbeda.</p>
                </template>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <x-primary-button type="submit">Ubah Password</x-primary-button>
            </div>
        </form>
    </div>
</x-one-panel-layout>