<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 rounded-ctl border border-line-strong bg-surface px-4 py-2.5 text-base font-semibold text-ink shadow-sm transition-colors duration-150 hover:bg-canvas focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 disabled:pointer-events-none disabled:opacity-50']) }}>
    {{ $slot }}
</button>
