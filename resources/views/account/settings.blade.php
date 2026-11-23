@php
    use App\Support\Format;

    $roleLabel = __('role:' . $user->roleName());
    $tabs = [
        'profil' => ['user', __('Profil')],
        'keamanan' => ['shield', __('Keamanan')],
        'tampilan' => ['palette', __('Tampilan')],
        'akun' => ['settings', __('Akun')],
    ];
    $hasPhoto = (bool) $user->avatar_path;
    // Avatar saat ini: 'foto', '' (inisial), atau kunci avatar siap pakai.
    $currentAvatar = $hasPhoto ? 'foto' : (string) $user->avatar_preset;
    $variants = ['' => (clone $user)->forceFill(['avatar_path' => null, 'avatar_preset' => null])];
    foreach ($presets as $key => $p) {
        $variants[$key] = (clone $user)->forceFill(['avatar_path' => null, 'avatar_preset' => $key]);
    }
@endphp

<x-app-layout title="Profil & pengaturan" subtitle="Atur foto, data diri, keamanan login, dan tampilan aplikasi untuk akun Anda.">
    <div x-data="{ tab: @js($tab) }"
         x-init="$watch('tab', t => { const u = new URL(location.href); u.searchParams.set('tab', t); history.replaceState(null, '', u) })">

        {{-- Tab --}}
        <div class="-mx-4 mb-6 overflow-x-auto px-4 sm:mx-0 sm:px-0">
            <div class="inline-flex min-w-full gap-1 rounded-panel border border-line bg-surface p-1 shadow-sm sm:min-w-0" role="tablist" aria-label="{{ __('Bagian pengaturan') }}">
                @foreach ($tabs as $key => [$icon, $label])
                    <a href="{{ route('account.settings', ['tab' => $key]) }}" role="tab"
                       @click.prevent="tab = '{{ $key }}'"
                       :aria-selected="(tab === '{{ $key }}').toString()"
                       :class="tab === '{{ $key }}' ? 'bg-brand text-white shadow-sm' : 'text-ink-muted hover:bg-canvas hover:text-ink'"
                       class="flex flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-ctl px-4 py-2 text-sm font-semibold transition-colors sm:flex-none">
                        <x-icon :name="$icon" class="size-4" /> {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- ================= PROFIL ================= --}}
        <div x-show="tab === 'profil'" @if ($tab !== 'profil') x-cloak @endif x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 translate-y-2" class="grid gap-6 xl:grid-cols-[minmax(0,26rem)_minmax(0,1fr)]">
            <x-panel title="Foto profil" description="Tampil di menu akun, sidebar, dan daftar pengguna.">
                <div x-data="{ avatarPick: null, current: @js($currentAvatar) }">
                <div x-data="photoEditor" class="flex flex-col items-center text-center">
                    {{-- Area foto: klik atau seret file ke sini --}}
                    <div class="group relative"
                         @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="dragging = false; pick($event.dataTransfer.files[0])">
                        {{-- Pratinjau: avatar sekarang, atau avatar yang sedang dipilih (belum tersimpan) --}}
                        <span class="relative block size-28 transition-transform duration-300 group-hover:scale-[1.03]">
                            <x-avatar :user="$user" size="size-28" text="text-2xl" class="shadow-md transition-all duration-300 ease-out"
                                      x-bind:class="avatarPick !== null ? 'scale-90 opacity-0' : ''" />
                            @foreach ($variants as $key => $variant)
                                <x-avatar :user="$variant" size="size-28" text="text-2xl" class="!absolute inset-0 shadow-md"
                                          x-show="avatarPick === '{{ $key }}'" x-cloak
                                          x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 scale-90"
                                          x-transition:leave="transition duration-200 ease-in" x-transition:leave-end="opacity-0 scale-105" />
                            @endforeach
                        </span>
                        <button type="button" @click="$refs.file.click()"
                                class="absolute inset-0 grid place-items-center rounded-full bg-ink/0 text-white opacity-0 transition-all duration-200 hover:bg-[rgb(0_0_0/0.45)] hover:opacity-100 focus-visible:bg-[rgb(0_0_0/0.45)] focus-visible:opacity-100"
                                aria-label="{{ __('Unggah foto baru') }}">
                            <x-icon name="camera" class="size-7" />
                        </button>
                        <div x-show="dragging" x-cloak class="absolute -inset-2 grid place-items-center rounded-full border-2 border-dashed border-brand bg-brand-soft/90 text-sm font-semibold text-brand">{{ __('Lepas di sini') }}</div>
                        <span class="absolute bottom-0 right-0 grid size-8 place-items-center rounded-full border-2 border-surface bg-brand text-white shadow-md" aria-hidden="true"><x-icon name="camera" class="size-3.5" /></span>
                    </div>
                    <p class="mt-4 font-bold text-ink">{{ $user->name }}</p>
                    <p class="text-sm text-ink-muted">{{ $roleLabel }}</p>

                    <input x-ref="file" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" tabindex="-1" @change="pick($event.target.files[0]); $event.target.value = ''">
                    <p x-show="error" x-cloak x-text="error" class="field-error mt-3" role="alert"></p>
                    @error('photo')<p class="field-error mt-3" role="alert">{{ $message }}</p>@enderror

                    <div class="mt-5 flex flex-wrap justify-center gap-2">
                        <x-btn icon="upload" @click="$refs.file.click()">{{ __('Unggah foto') }}</x-btn>
                        @if ($hasPhoto)
                            <form method="POST" action="{{ route('account.photo.delete') }}" x-data @submit="if (!confirm(@js(__('Hapus foto profil?')))) $event.preventDefault()">
                                @csrf @method('DELETE')
                                <x-btn type="submit" variant="secondary" icon="trash">{{ __('Hapus foto') }}</x-btn>
                            </form>
                        @endif
                    </div>
                    <p class="mt-3 text-xs text-ink-faint">{{ __('JPG, PNG, atau WebP, maks. 5 MB. Bisa juga diseret ke foto di atas.') }}</p>

                    {{-- Form tersembunyi: hasil crop + filter dikirim lewat sini (unggahan biasa, bukan AJAX) --}}
                    <form x-ref="upload" method="POST" action="{{ route('account.photo.upload') }}" enctype="multipart/form-data" class="hidden">
                        @csrf
                        <input x-ref="output" type="file" name="photo">
                    </form>

                    {{-- ===== Editor foto ===== --}}
                    <template x-teleport="body">
                        <div x-show="open" x-cloak class="fixed inset-0 z-[70] grid place-items-center overflow-y-auto bg-ink/40 p-3 backdrop-blur-sm sm:p-6"
                             x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0"
                             x-transition:leave="transition duration-150 ease-in" x-transition:leave-end="opacity-0"
                             @keydown.escape.window="open && close()" role="dialog" aria-modal="true" aria-labelledby="editor-title">
                            <div class="w-full max-w-4xl overflow-hidden rounded-panel border border-line bg-surface shadow-2xl"
                                 x-show="open" x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 translate-y-4 scale-[.97]">
                                <div class="flex items-center justify-between border-b border-line px-5 py-3.5">
                                    <div>
                                        <h2 id="editor-title" class="text-md font-bold text-ink">{{ __('Atur foto profil') }}</h2>
                                        <p class="text-sm text-ink-muted">{{ __('Geser & perbesar untuk memotong, lalu pilih filter.') }}</p>
                                    </div>
                                    <button type="button" class="grid size-9 place-items-center rounded-ctl text-ink-muted hover:bg-canvas" @click="close()" aria-label="{{ __('Tutup editor') }}"><x-icon name="x" /></button>
                                </div>

                                <div class="grid gap-0 lg:grid-cols-[minmax(0,1fr)_18rem]">
                                    {{-- Kanvas crop --}}
                                    <div class="relative bg-[#0B111D] p-3">
                                        <div class="tr-cropper relative mx-auto aspect-square w-full max-w-[30rem]" :style="`--tr-filter: ${css}`">
                                            <img x-ref="image" alt="{{ __('Foto yang sedang diatur') }}" class="block max-w-full opacity-0">
                                        </div>
                                        <div class="mt-3 flex flex-wrap items-center justify-center gap-1.5">
                                            <button type="button" class="tr-tool" @click="zoom(-0.1)" title="{{ __('Perkecil') }}" aria-label="{{ __('Perkecil') }}"><x-icon name="zoom-out" class="size-4" /></button>
                                            <input type="range" min="0" max="100" step="1" x-model.number="zoomLevel" @input="setZoom()" class="w-32 accent-brand" aria-label="{{ __('Perbesar') }}">
                                            <button type="button" class="tr-tool" @click="zoom(0.1)" title="{{ __('Perbesar') }}" aria-label="{{ __('Perbesar') }}"><x-icon name="zoom-in" class="size-4" /></button>
                                            <span class="mx-1 h-6 w-px bg-white/15" aria-hidden="true"></span>
                                            <button type="button" class="tr-tool" @click="rotate(-90)" title="{{ __('Putar kiri') }}" aria-label="{{ __('Putar kiri') }}"><x-icon name="rotate-ccw" class="size-4" /></button>
                                            <button type="button" class="tr-tool" @click="rotate(90)" title="{{ __('Putar kanan') }}" aria-label="{{ __('Putar kanan') }}"><x-icon name="rotate-cw" class="size-4" /></button>
                                            <button type="button" class="tr-tool" @click="flip()" title="{{ __('Balik cermin') }}" aria-label="{{ __('Balik cermin') }}"><x-icon name="flip" class="size-4" /></button>
                                            <button type="button" class="tr-tool !w-auto px-3 text-xs font-semibold" @click="reset()">{{ __('Atur ulang') }}</button>
                                        </div>
                                    </div>

                                    {{-- Pratinjau + filter --}}
                                    <div class="flex flex-col gap-5 border-t border-line p-5 lg:border-l lg:border-t-0">
                                        <div>
                                            <p class="field-label">{{ __('Pratinjau') }}</p>
                                            <div class="flex items-end gap-4">
                                                <div class="tr-preview size-24 overflow-hidden rounded-full bg-canvas ring-4 ring-line" :style="`filter: ${css}`"></div>
                                                <div class="tr-preview size-12 overflow-hidden rounded-full bg-canvas ring-2 ring-line" :style="`filter: ${css}`"></div>
                                                <div class="tr-preview size-8 overflow-hidden rounded-full bg-canvas ring-2 ring-line" :style="`filter: ${css}`"></div>
                                            </div>
                                        </div>

                                        <div>
                                            <p class="field-label">{{ __('Filter') }}</p>
                                            <div class="grid grid-cols-4 gap-2">
                                                <template x-for="f in filters" :key="f.key">
                                                    <button type="button" @click="filter = f.key"
                                                            class="group flex flex-col items-center gap-1 rounded-ctl p-1 text-[11px] font-semibold transition-colors"
                                                            :class="filter === f.key ? 'bg-brand-soft text-brand' : 'text-ink-muted hover:bg-canvas'"
                                                            :aria-pressed="(filter === f.key).toString()">
                                                        <span class="block size-12 overflow-hidden rounded-ctl ring-2 transition-all"
                                                              :class="filter === f.key ? 'ring-brand' : 'ring-transparent group-hover:ring-line-strong'">
                                                            <img :src="thumb" alt="" class="size-full object-cover" :style="`filter: ${cssOf(f.key)}`">
                                                        </span>
                                                        <span x-text="f.label"></span>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>

                                        <div class="space-y-3">
                                            <label class="block">
                                                <span class="flex justify-between text-sm font-semibold text-ink">{{ __('Kecerahan') }} <span class="num text-ink-muted" x-text="(brightness > 0 ? '+' : '') + brightness"></span></span>
                                                <input type="range" min="-30" max="30" x-model.number="brightness" class="mt-1 w-full accent-brand">
                                            </label>
                                            <label class="block">
                                                <span class="flex justify-between text-sm font-semibold text-ink">{{ __('Kontras') }} <span class="num text-ink-muted" x-text="(contrast > 0 ? '+' : '') + contrast"></span></span>
                                                <input type="range" min="-30" max="30" x-model.number="contrast" class="mt-1 w-full accent-brand">
                                            </label>
                                        </div>

                                        <div class="mt-auto flex gap-2 pt-2">
                                            <x-btn variant="secondary" class="flex-1" @click="close()">{{ __('Batal') }}</x-btn>
                                            <button type="button" @click="save()" :disabled="saving"
                                                    class="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-ctl bg-brand px-4 font-semibold text-white shadow-sm transition-colors hover:bg-brand-hover disabled:opacity-70">
                                                <span x-show="saving" x-cloak class="tr-spinner" aria-hidden="true"></span>
                                                <span x-text="saving ? @js(__('Menyimpan…')) : @js(__('Simpan foto'))">{{ __('Simpan foto') }}</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Avatar siap pakai: pilih → pratinjau di atas → Simpan / Batal --}}
                <div class="mt-6 border-t border-line pt-5">
                    <p class="field-label">{{ __('Atau pakai avatar') }}</p>
                    <p class="mb-4 text-sm text-ink-muted">{{ $hasPhoto ? __('Memilih avatar akan menggantikan foto Anda.') : __('Pilih salah satu, atau kembali ke inisial nama.') }}</p>
                    <div class="flex flex-wrap gap-3.5" role="radiogroup" aria-label="{{ __('Atau pakai avatar') }}">
                        @foreach ($variants as $key => $variant)
                            @php $label = $key === '' ? __('Inisial nama') : __('Avatar :nama', ['nama' => __($presets[$key]['label'])]); @endphp
                            <button type="button" role="radio" title="{{ $key === '' ? __('Inisial nama') : __($presets[$key]['label']) }}"
                                    class="tr-pick" :class="(avatarPick ?? current) === '{{ $key }}' && 'tr-pick-on'"
                                    :aria-checked="((avatarPick ?? current) === '{{ $key }}').toString()"
                                    @click="avatarPick = (current === '{{ $key }}' ? null : '{{ $key }}')">
                                <x-avatar :user="$variant" size="size-11" text="text-xs" />
                                <span class="sr-only">{{ $label }}</span>
                            </button>
                        @endforeach
                    </div>

                    <form method="POST" action="{{ route('account.avatar.preset') }}" x-show="avatarPick !== null" x-cloak
                          x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0 -translate-y-1"
                          class="mt-5 flex flex-wrap items-center justify-end gap-2 rounded-ctl bg-canvas px-3 py-2.5"
                          @submit="if (current === 'foto' && !confirm(@js(__('Ganti foto dengan avatar ini? Foto lama akan dihapus.')))) $event.preventDefault()">
                        @csrf @method('PUT')
                        <input type="hidden" name="preset" :value="avatarPick">
                        <p class="mr-auto text-sm text-ink-muted">{{ __('Belum disimpan') }}</p>
                        <x-btn variant="secondary" size="sm" @click="avatarPick = null">{{ __('Batal') }}</x-btn>
                        <x-btn type="submit" size="sm" icon="check">{{ __('Simpan') }}</x-btn>
                    </form>
                </div>
                </div>
            </x-panel>

            <div class="space-y-6">
                <x-panel title="Data diri" description="Nama tampil di laporan dan log aktivitas.">
                    <form method="POST" action="{{ route('account.profile.update') }}" class="grid gap-5 sm:grid-cols-2" novalidate>
                        @csrf @method('PUT')
                        <div class="sm:col-span-2">
                            <label for="name" class="field-label">{{ __('Nama lengkap') }}</label>
                            <input id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="120" autocomplete="name"
                                   @error('name') aria-invalid="true" aria-describedby="name-error" @enderror class="field-input @error('name') !border-bad @enderror">
                            @error('name')<p id="name-error" class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="email" class="field-label">{{ __('Email') }}</label>
                            <input id="email" value="{{ $user->email }}" disabled class="field-input cursor-not-allowed bg-canvas text-ink-muted">
                            <p class="field-help">{{ __('Email login diatur oleh admin.') }}</p>
                        </div>
                        <div>
                            <label for="phone" class="field-label">{{ __('Nomor HP') }} <span class="font-normal text-ink-faint">({{ __('opsional') }})</span></label>
                            <input id="phone" name="phone" value="{{ old('phone', $user->phone) }}" inputmode="tel" maxlength="20" autocomplete="tel" placeholder="08xx xxxx xxxx"
                                   @error('phone') aria-invalid="true" aria-describedby="phone-error" @enderror class="field-input @error('phone') !border-bad @enderror">
                            @error('phone')<p id="phone-error" class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="flex justify-end sm:col-span-2">
                            <x-btn type="submit" icon="check">{{ __('Simpan profil') }}</x-btn>
                        </div>
                    </form>
                </x-panel>

                <x-panel title="{{ __('Ringkasan akun') }}">
                    <dl class="grid gap-4 text-sm sm:grid-cols-3">
                        <div class="rounded-ctl bg-canvas px-4 py-3"><dt class="text-ink-muted">{{ __('Role') }}</dt><dd class="mt-0.5 font-semibold text-ink">{{ $roleLabel }}</dd></div>
                        <div class="rounded-ctl bg-canvas px-4 py-3"><dt class="text-ink-muted">{{ __('Bergabung') }}</dt><dd class="mt-0.5 font-semibold text-ink">{{ $user->created_at ? Format::date($user->created_at->toDateString()) : '–' }}</dd></div>
                        <div class="rounded-ctl bg-canvas px-4 py-3"><dt class="text-ink-muted">{{ __('Login terakhir') }}</dt><dd class="mt-0.5 font-semibold text-ink">{{ $user->last_login_at ? Format::dateTime($user->last_login_at->format('Y-m-d H:i')) : '–' }}</dd></div>
                    </dl>
                </x-panel>
            </div>
        </div>

        {{-- ================= KEAMANAN ================= --}}
        <div x-show="tab === 'keamanan'" @if ($tab !== 'keamanan') x-cloak @endif x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 translate-y-2" class="grid gap-6 xl:grid-cols-2">
            <x-panel title="{{ __('Ganti password') }}" description="Minimal 8 karakter berisi huruf dan angka. Sesi di perangkat lain akan dikeluarkan.">
                <form method="POST" action="{{ route('account.password.update') }}" class="space-y-5" novalidate>
                    @csrf @method('PUT')
                    @foreach ([['current_password', __('Password saat ini'), 'current-password'], ['password', __('Password baru'), 'new-password'], ['password_confirmation', __('Ulangi password baru'), 'new-password']] as [$field, $label, $ac])
                        <div x-data="{ show: false }">
                            <label for="pw-{{ $field }}" class="field-label">{{ $label }}</label>
                            <div class="relative">
                                <input :type="show ? 'text' : 'password'" type="password" id="pw-{{ $field }}" name="{{ $field }}" autocomplete="{{ $ac }}" required
                                       @error($field) aria-invalid="true" aria-describedby="pw-{{ $field }}-error" @enderror class="field-input pr-11 @error($field) !border-bad @enderror">
                                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 grid w-11 place-items-center text-ink-faint hover:text-ink" :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'">
                                    <x-icon name="eye" class="size-4" x-show="!show" /><x-icon name="eye-off" class="size-4" x-show="show" x-cloak />
                                </button>
                            </div>
                            @error($field)<p id="pw-{{ $field }}-error" class="field-error">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                    <div class="flex justify-end"><x-btn type="submit" icon="lock">{{ __('Ganti password') }}</x-btn></div>
                </form>
            </x-panel>

            <x-panel title="Sesi login aktif" description="Perangkat yang sedang masuk dengan akun Anda." flush>
                <ul class="divide-y divide-line" x-data="{ all: false }">
                    @foreach ($sessions as $s)
                        <li class="flex items-center gap-4 px-5 py-4" @if ($loop->index >= 4) x-show="all" x-cloak @endif>
                            <span @class(['grid size-11 shrink-0 place-items-center rounded-ctl', 'bg-ok-bg text-ok' => $s['current'], 'bg-info-bg text-info' => ! $s['current']]) aria-hidden="true">
                                <x-icon :name="$s['mobile'] ? 'smartphone' : 'monitor'" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-ink">{{ __(':browser di :os', ['browser' => $s['browser'], 'os' => __($s['os'])]) }}</p>
                                <p class="text-sm text-ink-muted">
                                    <span class="num">{{ $s['ip'] ?? __('IP tidak diketahui') }}</span> ·
                                    @if ($s['current'])
                                        <span class="font-semibold text-ok">{{ __('Perangkat ini') }}</span>
                                    @else
                                        {{ __('aktif :waktu', ['waktu' => $s['last']->diffForHumans()]) }}
                                    @endif
                                </p>
                            </div>
                            @unless ($s['current'])
                                <form method="POST" action="{{ route('account.sessions.destroy', $s['id']) }}" x-data @submit="if (!confirm(@js(__('Keluarkan sesi di perangkat ini?')))) $event.preventDefault()">
                                    @csrf @method('DELETE')
                                    <x-btn type="submit" size="sm" variant="secondary">{{ __('Keluarkan') }}</x-btn>
                                </form>
                            @endunless
                        </li>
                    @endforeach
                    @if (count($sessions) > 4)
                        <li class="px-5 py-2.5 text-center">
                            <button type="button" class="link text-sm" @click="all = !all"
                                    x-text="all ? @js(__('Sembunyikan')) : @js(__('Tampilkan semua :n sesi', ['n' => count($sessions)]))">{{ __('Tampilkan semua :n sesi', ['n' => count($sessions)]) }}</button>
                        </li>
                    @endif
                </ul>
                @if (count($sessions) > 1)
                    <form method="POST" action="{{ route('account.sessions.others') }}" class="flex flex-col gap-3 border-t border-line bg-canvas px-5 py-4 sm:flex-row sm:items-end" novalidate>
                        @csrf
                        <div class="flex-1">
                            <label for="sess-password" class="field-label">{{ __('Keluar dari semua perangkat lain') }}</label>
                            <input type="password" id="sess-password" name="password" autocomplete="current-password" placeholder="{{ __('Masukkan password Anda') }}" required
                                   @error('password', 'sessions') aria-invalid="true" @enderror class="field-input @error('password', 'sessions') !border-bad @enderror">
                            @error('password', 'sessions')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <x-btn type="submit" variant="danger" icon="log-out">{{ __('Keluarkan semua') }}</x-btn>
                    </form>
                @else
                    <p class="border-t border-line px-5 py-3 text-sm text-ink-muted">{{ __('Hanya perangkat ini yang sedang masuk.') }}</p>
                @endif
            </x-panel>
        </div>

        {{-- ================= TAMPILAN ================= --}}
        <div x-show="tab === 'tampilan'" @if ($tab !== 'tampilan') x-cloak @endif x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 translate-y-2" class="max-w-3xl space-y-6">
            <x-panel title="{{ __('Tema') }}" description="Tersimpan di akun Anda. 'Ikuti sistem' menyesuaikan setelan terang/gelap HP atau laptop Anda.">
                <div x-data="themePicker" class="grid gap-3 sm:grid-cols-3" role="radiogroup" aria-label="{{ __('Tema') }}">
                    <p class="text-sm font-semibold text-ok sm:col-span-3 sm:order-last" x-show="saved" x-cloak x-transition.opacity role="status">{{ __('Tersimpan') }}</p>
                    @foreach ([['light', __('Terang'), 'sun'], ['dark', __('Gelap'), 'moon'], ['system', __('Ikuti sistem'), 'monitor']] as [$value, $label, $icon])
                        <button type="button" role="radio" @click="set('{{ $value }}')" :aria-checked="(choice === '{{ $value }}').toString()"
                                class="group rounded-panel border-2 p-2 text-left transition-all"
                                :class="choice === '{{ $value }}' ? 'border-brand shadow-md' : 'border-line hover:border-line-strong'">
                            {{-- Miniatur aplikasi --}}
                            <span @class(['tr-theme-mini block overflow-hidden rounded-ctl border', 'tr-mini-light' => $value === 'light', 'tr-mini-dark' => $value === 'dark', 'tr-mini-system' => $value === 'system'])>
                                <span class="tr-mini-half"><span class="tr-mini-side"></span><span class="tr-mini-body"><span class="tr-mini-bar"></span><span class="tr-mini-card"></span><span class="tr-mini-card w-2/3"></span></span></span>
                                @if ($value === 'system')
                                    <span class="tr-mini-half tr-mini-dark-half"><span class="tr-mini-side"></span><span class="tr-mini-body"><span class="tr-mini-bar"></span><span class="tr-mini-card"></span><span class="tr-mini-card w-2/3"></span></span></span>
                                @endif
                            </span>
                            <span class="mt-2.5 flex items-center gap-2 px-1 pb-1 text-sm font-semibold text-ink">
                                <x-icon :name="$icon" class="size-4 text-ink-muted" /> {{ $label }}
                                <span class="ml-auto grid size-5 place-items-center rounded-full border-2 transition-colors" :class="choice === '{{ $value }}' ? 'border-brand bg-brand' : 'border-line-strong'">
                                    <span class="size-1.5 rounded-full bg-white" x-show="choice === '{{ $value }}'"></span>
                                </span>
                            </span>
                        </button>
                    @endforeach
                </div>
            </x-panel>

            <x-panel title="Ukuran teks" description="Mengubah ukuran teks dan elemen di seluruh aplikasi. Tersimpan di akun Anda.">
                <div x-data="textPicker" class="grid gap-3 sm:grid-cols-3" role="radiogroup" aria-label="{{ __('Ukuran teks') }}">
                    @foreach ([['sm', __('Kecil'), 'text-sm'], ['md', __('Sedang'), 'text-lg'], ['lg', __('Besar'), 'text-2xl']] as [$value, $label, $sample])
                        <button type="button" role="radio" @click="set('{{ $value }}')" :aria-checked="(size === '{{ $value }}').toString()"
                                class="flex items-center gap-3 rounded-panel border-2 px-4 py-3 text-left transition-all"
                                :class="size === '{{ $value }}' ? 'border-brand bg-brand-soft/50 shadow-md' : 'border-line hover:border-line-strong'">
                            <span class="grid size-11 shrink-0 place-items-center rounded-ctl bg-canvas font-bold text-ink {{ $sample }}" aria-hidden="true">Aa</span>
                            <span class="flex-1 text-sm font-semibold text-ink">{{ $label }}</span>
                            <span class="grid size-5 place-items-center rounded-full border-2 transition-colors" :class="size === '{{ $value }}' ? 'border-brand bg-brand' : 'border-line-strong'">
                                <span class="size-1.5 rounded-full bg-white" x-show="size === '{{ $value }}'"></span>
                            </span>
                        </button>
                    @endforeach
                    <p class="text-sm font-semibold text-ok sm:col-span-3" x-show="saved" x-cloak x-transition.opacity role="status">{{ __('Tersimpan') }}</p>
                </div>
            </x-panel>

            <x-panel title="Bahasa tampilan" description="Pilihan bahasa tersimpan di akun Anda dan dipakai di semua perangkat.">
                <form method="POST" action="{{ route('locale.update') }}" class="grid gap-3 sm:grid-cols-2" role="radiogroup" aria-label="{{ __('Bahasa') }}">
                    @csrf
                    @foreach (\App\Http\Middleware\SetLocale::SUPPORTED as $code => $name)
                        @php $on = app()->getLocale() === $code; @endphp
                        <button type="submit" name="locale" value="{{ $code }}" role="radio" aria-checked="{{ $on ? 'true' : 'false' }}"
                                @class(['flex items-center gap-3 rounded-panel border-2 px-4 py-3 text-left transition-all', 'border-brand bg-brand-soft/50 shadow-md' => $on, 'border-line hover:border-line-strong' => ! $on])>
                            <span class="grid size-11 shrink-0 place-items-center rounded-ctl bg-canvas text-sm font-bold uppercase text-ink" aria-hidden="true">{{ $code }}</span>
                            <span class="flex-1 text-sm font-semibold text-ink">{{ $name }}</span>
                            <span @class(['grid size-5 place-items-center rounded-full border-2', 'border-brand bg-brand' => $on, 'border-line-strong' => ! $on])>
                                @if ($on)<span class="size-1.5 rounded-full bg-white"></span>@endif
                            </span>
                        </button>
                    @endforeach
                </form>
            </x-panel>
        </div>

        {{-- ================= AKUN ================= --}}
        <div x-show="tab === 'akun'" @if ($tab !== 'akun') x-cloak @endif x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 translate-y-2" class="max-w-3xl space-y-6">
            <x-panel title="{{ __('Informasi akun') }}">
                <dl class="grid gap-x-8 gap-y-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-ink-muted">{{ __('Email login') }}</dt><dd class="font-semibold text-ink">{{ $user->email }}</dd></div>
                    <div><dt class="text-ink-muted">{{ __('Role') }}</dt><dd class="font-semibold text-ink">{{ $roleLabel }}</dd></div>
                    <div><dt class="text-ink-muted">{{ __('Akun dibuat') }}</dt><dd class="font-semibold text-ink">{{ $user->created_at ? Format::dateTime($user->created_at->format('Y-m-d H:i')) : '–' }}</dd></div>
                    <div><dt class="text-ink-muted">{{ __('Sesi aktif') }}</dt><dd class="num font-semibold text-ink">{{ __(':n perangkat', ['n' => count($sessions)]) }}</dd></div>
                </dl>
            </x-panel>

            <x-panel title="Penghapusan akun">
                <p class="flex gap-3 text-sm text-ink-muted">
                    <x-icon name="shield" class="mt-0.5 size-4 shrink-0 text-ink-faint" />
                    <span>
                        @if ($user->hasRole('admin'))
                            {{ __('Akun pengguna dihapus dari halaman Pengguna & role. Akun Anda sendiri hanya bisa dihapus oleh admin lain.') }}
                        @else
                            {{ __('Akun terhubung dengan riwayat penilaian dan keputusan, jadi penghapusan dilakukan oleh admin. Hubungi admin bila akun ini perlu dihapus.') }}
                        @endif
                    </span>
                </p>
            </x-panel>
        </div>
    </div>
</x-app-layout>
