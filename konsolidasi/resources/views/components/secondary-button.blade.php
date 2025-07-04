<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-primary-700 bg-white border border-primary-700 rounded-lg  transition-colors duration-200 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-primary-300 sm:w-auto']) }}>
    {{ $slot }}
</button>