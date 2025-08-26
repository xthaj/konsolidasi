<x-one-panel-layout>

    @section('vite')
    @vite(['resources/css/app.css', 'resources/js/rekonsiliasi/laporan.js'])
    @endsection

    <div class="my-4">
        <h1 class="text-lg font-semibold">Download Laporan Rekonsiliasi</h1>
    </div>

    <form action="{{ route('data.export.rekonsiliasi') }}" method="POST"
        x-data="{ cooldown: false, cooldownTime: 60, timer: null }"
        @submit.prevent="
        if (cooldown) return;
        cooldown = true;
        $el.submit();
        timer = setInterval(() => {
          if (cooldownTime > 0) cooldownTime--;
          else {
            clearInterval(timer);
            cooldown = false;
            cooldownTime = 60;
          }
        }, 1000);
      ">
        @csrf
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-900">Bulan</label>
                <select name="bulan" x-model="bulan" disabled
                    class="cursor-not-allowed bg-gray-200 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                    <template x-for="[nama, bln] in bulanOptions" :key="bln">
                        <option :value="bln" :selected="bulan == bln" x-text="nama"></option>
                    </template>
                </select>
                <input type="hidden" name="bulan" :value="bulan">
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-900">Tahun</label>
                <select name="tahun" disabled
                    class="cursor-not-allowed bg-gray-200 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                    <template x-for="year in tahunOptions" :key="year">
                        <option :value="year" :selected="year == tahun" x-text="year"></option>
                    </template>
                </select>
                <input type="hidden" name="tahun" :value="tahun">
            </div>
        </div>

        <div class="mt-6 flex justify-end items-center gap-3">
            <x-primary-button
                type="submit"
                x-bind:disabled="cooldown"
                x-text="cooldown ? `Tunggu ${cooldownTime}s` : 'Download Data'">
            </x-primary-button>
        </div>
    </form>

</x-one-panel-layout>