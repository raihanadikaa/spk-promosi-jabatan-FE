@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'field-input disabled:cursor-not-allowed disabled:bg-canvas disabled:text-ink-faint']) }}>
