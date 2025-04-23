<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-primary-600 border border-primary-600 rounded-lg font-medium text-sm text-white hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 transition duration-50']) }}>
    {{ $slot }}
</button>