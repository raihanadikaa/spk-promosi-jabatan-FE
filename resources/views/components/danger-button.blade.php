<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 rounded-ctl bg-bad px-4 py-2.5 text-base font-semibold text-white shadow-sm transition-colors duration-150 hover:bg-bad/90 focus:outline-none focus:ring-2 focus:ring-bad focus:ring-offset-2 active:translate-y-px disabled:pointer-events-none disabled:opacity-50']) }}>
    {{ $slot }}
</button>
