<x-app-layout title="Ganti password" subtitle="Gunakan minimal 8 karakter berisi huruf dan angka. Sesi di perangkat lain akan dikeluarkan.">
    <x-panel class="max-w-xl">
        <form method="POST" action="{{ route('account.password.update') }}" class="space-y-5" novalidate>
            @csrf
            @method('PUT')

            @foreach ([
                ['current_password', 'Password saat ini', 'current-password'],
                ['password', 'Password baru', 'new-password'],
                ['password_confirmation', 'Ulangi password baru', 'new-password'],
            ] as [$field, $label, $autocomplete])
                <div>
                    <label for="{{ $field }}" class="field-label">{{ $label }}</label>
                    <input type="password" id="{{ $field }}" name="{{ $field }}" autocomplete="{{ $autocomplete }}" required
                           @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror
                           class="field-input @error($field) !border-bad @enderror">
                    @error($field)
                        <p id="{{ $field }}-error" class="field-error">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            <div class="flex justify-end">
                <x-btn type="submit">Ganti password</x-btn>
            </div>
        </form>
    </x-panel>
</x-app-layout>
