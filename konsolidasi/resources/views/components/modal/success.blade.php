<x-modal name="success-modal" title="Berhasil" maxWidth="md">
    <div class="text-gray-900 ">
        {{ $slot }}
        <div class="mt-4 flex justify-end">
            <x-primary-button
                type="button"
                x-on:click="$dispatch('close-modal', 'success-modal')">
                Tutup
            </x-primary-button>
        </div>
    </div>
</x-modal>