@php
    use App\Support\Format;

    $roleLabel = \App\Queries\PromotionData::roleLabels()[$user->roleName()] ?? $user->roleName();
    $tabs = [
        'profil' => ['user', 'Profil'],
        'keamanan' => ['shield', 'Keamanan'],
        'tampilan' => ['palette', 'Tampilan'],
        'akun' => ['settings', 'Akun'],
    ];
    $hasPhoto = (bool) $user->avatar_path;
@endphp

<x-app-layout title="Profil & pengaturan" subtitle="Atur foto, data diri, keamanan login, dan tampilan aplikasi untuk akun Anda.">
    <div x-data="{ tab: @js($tab) }"
         x-init="$watch('tab', t => { const u = new URL(location.href); u.searchParams.set('tab', t); history.replaceState(null, '', u) })">

        {{-- Tab --}}
        <div class="-mx-4 mb-6 overflow-x-auto px-4 sm:mx-0 sm:px-0">
            <div class="inline-flex min-w-full gap-1 rounded-panel border border-line bg-surface p-1 shadow-sm sm:min-w-0" role="tablist" aria-label="Bagian pengaturan">
                @foreach ($tabs as $key => [$icon, $label])
                    <a href="{{ route('account.settings', ['tab' => $key]) }}" role="tab"
                       @click.prevent="tab = '{{ $key }}'"
                       :aria-selected="(tab === '{{ $key }}').toString()"
                       :class="tab === '{{ $key }}' ? 'bg-brand text-white shadow-sm' : 'text-ink-muted hover:bg-canvas hover:text-ink'"
                       @class(['flex flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-ctl px-4 py-2 text-sm font-semibold transition-colors sm:flex-none', 'bg-brand text-white shadow-sm' => $tab === $key, 'text-ink-muted' => $tab !== $key])>
                        <x-icon :name="$icon" class="size-4" /> {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- ================= PROFIL ================= --}}
        <div x-show="tab === 'profil'" @if ($tab !== 'profil') x-cloak @endif class="grid gap-6 xl:grid-cols-[minmax(0,26rem)_minmax(0,1fr)]">
            <x-panel title="Foto profil" description="Tampil di menu akun, sidebar, dan daftar pengguna.">
                <div x-data="photoEditor" class="flex flex-col items-center text-center">
                    {{-- Area foto: klik atau seret file ke sini --}}
                    <div class="group relative"
                         @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="dragging = false; pick($event.dataTransfer.files[0])">
                        <x-avatar :user="$user" size="size-32" text="text-3xl" class="ring-4 ring-surface shadow-md transition-transform duration-300 group-hover:scale-[1.03]" />
                        <button type="button" @click="$refs.file.click()"
                                class="absolute inset-0 grid place-items-center rounded-full bg-ink/0 text-white opacity-0 transition-all duration-200 hover:bg-[rgb(0_0_0/0.45)] hover:opacity-100 focus-visible:bg-[rgb(0_0_0/0.45)] focus-visible:opacity-100"
                                aria-label="Unggah foto baru">
                            <x-icon name="camera" class="size-7" />
                        </button>
                        <div x-show="dragging" x-cloak class="absolute -inset-2 grid place-items-center rounded-full border-2 border-dashed border-brand bg-brand-soft/90 text-sm font-semibold text-brand">Lepas di sini</div>
                        <span class="absolute bottom-1 right-1 grid size-9 place-items-center rounded-full border-2 border-surface bg-brand text-white shadow-md" aria-hidden="true"><x-icon name="camera" class="size-4" /></span>
                    </div>
                    <p class="mt-4 font-bold text-ink">{{ $user->name }}</p>
                    <p class="text-sm text-ink-muted">{{ $roleLabel }}</p>

                    <input x-ref="file" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" tabindex="-1" @change="pick($event.target.files[0]); $event.target.value = ''">
                    <p x-show="error" x-cloak x-text="error" class="field-error mt-3" role="alert"></p>
                    @error('photo')<p class="field-error mt-3" role="alert">{{ $message }}</p>@enderror

                    <div class="mt-5 flex flex-wrap justify-center gap-2">
                        <x-btn icon="upload" @click="$refs.file.click()">Unggah foto</x-btn>
                        @if ($hasPhoto)
                            <form method="POST" action="{{ route('account.photo.delete') }}" x-data @submit="if (!confirm('Hapus foto profil?')) $event.preventDefault()">
                                @csrf @method('DELETE')
                                <x-btn type="submit" variant="secondary" icon="trash">Hapus foto</x-btn>
                            </form>
                        @endif
                    </div>
                    <p class="mt-3 text-xs text-ink-faint">JPG, PNG, atau WebP, maks. 5 MB. Bisa juga diseret ke foto di atas.</p>

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
                                        <h2 id="editor-title" class="text-md font-bold text-ink">Atur foto profil</h2>
                                        <p class="text-sm text-ink-muted">Geser & perbesar untuk memotong, lalu pilih filter.</p>
                                    </div>
                                    <button type="button" class="grid size-9 place-items-center rounded-ctl text-ink-muted hover:bg-canvas" @click="close()" aria-label="Tutup editor"><x-icon name="x" /></button>
                                </div>

                                <div class="grid gap-0 lg:grid-cols-[minmax(0,1fr)_18rem]">
                                    {{-- Kanvas crop --}}
                                    <div class="relative bg-[#0B111D] p-3">
                                        <div class="tr-cropper relative mx-auto aspect-square w-full max-w-[30rem]" :style="`--tr-filter: ${css}`">
                                            <img x-ref="image" alt="Foto yang sedang diatur" class="block max-w-full opacity-0">
                                        </div>
                                        <div class="mt-3 flex flex-wrap items-center justify-center gap-1.5">
                                            <button type="button" class="tr-tool" @click="zoom(-0.1)" title="Perkecil" aria-label="Perkecil"><x-icon name="zoom-out" class="size-4" /></button>
                                            <input type="range" min="0" max="100" step="1" x-model.number="zoomLevel" @input="setZoom()" class="w-32 accent-brand" aria-label="Perbesar">
                                            <button type="button" class="tr-tool" @click="zoom(0.1)" title="Perbesar" aria-label="Perbesar"><x-icon name="zoom-in" class="size-4" /></button>
                                            <span class="mx-1 h-6 w-px bg-white/15" aria-hidden="true"></span>
                                            <button type="button" class="tr-tool" @click="rotate(-90)" title="Putar kiri" aria-label="Putar kiri"><x-icon name="rotate-ccw" class="size-4" /></button>
                                            <button type="button" class="tr-tool" @click="rotate(90)" title="Putar kanan" aria-label="Putar kanan"><x-icon name="rotate-cw" class="size-4" /></button>
                                            <button type="button" class="tr-tool" @click="flip()" title="Balik cermin" aria-label="Balik cermin"><x-icon name="flip" class="size-4" /></button>
                                            <button type="button" class="tr-tool !w-auto px-3 text-xs font-semibold" @click="reset()">Atur ulang</button>
                                        </div>
                                    </div>

                                    {{-- Pratinjau + filter --}}
                                    <div class="flex flex-col gap-5 border-t border-line p-5 lg:border-l lg:border-t-0">
                                        <div>
                                            <p class="field-label">Pratinjau</p>
                                            <div class="flex items-end gap-4">
                                                <div class="tr-preview size-24 overflow-hidden rounded-full bg-canvas ring-4 ring-line" :style="`filter: ${css}`"></div>
                                                <div class="tr-preview size-12 overflow-hidden rounded-full bg-canvas ring-2 ring-line" :style="`filter: ${css}`"></div>
                                                <div class="tr-preview size-8 overflow-hidden rounded-full bg-canvas ring-2 ring-line" :style="`filter: ${css}`"></div>
                                            </div>
                                        </div>

                                        <div>
                                            <p class="field-label">Filter</p>
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
                                                <span class="flex justify-between text-sm font-semibold text-ink">Kecerahan <span class="num text-ink-muted" x-text="(brightness > 0 ? '+' : '') + brightness"></span></span>
                                                <input type="range" min="-30" max="30" x-model.number="brightness" class="mt-1 w-full accent-brand">
                                            </label>
                                            <label class="block">
                                                <span class="flex justify-between text-sm font-semibold text-ink">Kontras <span class="num text-ink-muted" x-text="(contrast > 0 ? '+' : '') + contrast"></span></span>
                                                <input type="range" min="-30" max="30" x-model.number="contrast" class="mt-1 w-full accent-brand">
                                            </label>
                                        </div>

                                        <div class="mt-auto flex gap-2 pt-2">
                                            <x-btn variant="secondary" class="flex-1" @click="close()">Batal</x-btn>
                                            <button type="button" @click="save()" :disabled="saving"
                                                    class="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-ctl bg-brand px-4 font-semibold text-white shadow-sm transition-colors hover:bg-brand-hover disabled:opacity-70">
                                                <span x-show="saving" x-cloak class="tr-spinner" aria-hidden="true"></span>
                                                <span x-text="saving ? 'Menyimpan…' : 'Simpan foto'">Simpan foto</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Avatar siap pakai --}}
                <div class="mt-6 border-t border-line pt-5">
                    <p class="field-label">Atau pakai avatar</p>
                    <p class="mb-3 text-sm text-ink-muted">{{ $hasPhoto ? 'Memilih avatar akan menggantikan foto Anda.' : 'Pilih salah satu, atau kembali ke inisial nama.' }}</p>
                    <form method="POST" action="{{ route('account.avatar.preset') }}" class="grid grid-cols-5 gap-2.5"
                          x-data @submit="@if ($hasPhoto) if (!confirm('Ganti foto dengan avatar ini? Foto lama akan dihapus.')) $event.preventDefault() @endif">
                        @csrf @method('PUT')
                        @php $initialUser = (clone $user)->forceFill(['avatar_path' => null, 'avatar_preset' => null]); @endphp
                        <button type="submit" name="preset" value="" title="Inisial nama"
                                @class(['tr-pick', 'tr-pick-on' => ! $hasPhoto && ! $user->avatar_preset])>
                            <x-avatar :user="$initialUser" size="size-full" text="text-sm" />
                            <span class="sr-only">Inisial nama</span>
                        </button>
                        @foreach ($presets as $key => $p)
                            @php $presetUser = (clone $user)->forceFill(['avatar_path' => null, 'avatar_preset' => $key]); @endphp
                            <button type="submit" name="preset" value="{{ $key }}" title="{{ $p['label'] }}"
                                    @class(['tr-pick', 'tr-pick-on' => ! $hasPhoto && $user->avatar_preset === $key])>
                                <x-avatar :user="$presetUser" size="size-full" />
                                <span class="sr-only">Avatar {{ $p['label'] }}</span>
                            </button>
                        @endforeach
                    </form>
                </div>
            </x-panel>

            <div class="space-y-6">
                <x-panel title="Data diri" description="Nama tampil di laporan dan log aktivitas.">
                    <form method="POST" action="{{ route('account.profile.update') }}" class="grid gap-5 sm:grid-cols-2" novalidate>
                        @csrf @method('PUT')
                        <div class="sm:col-span-2">
                            <label for="name" class="field-label">Nama lengkap</label>
                            <input id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="120" autocomplete="name"
                                   @error('name') aria-invalid="true" aria-describedby="name-error" @enderror class="field-input @error('name') !border-bad @enderror">
                            @error('name')<p id="name-error" class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="email" class="field-label">Email</label>
                            <input id="email" value="{{ $user->email }}" disabled class="field-input cursor-not-allowed bg-canvas text-ink-muted">
                            <p class="field-help">Email login diatur oleh admin.</p>
                        </div>
                        <div>
                            <label for="phone" class="field-label">Nomor HP <span class="font-normal text-ink-faint">(opsional)</span></label>
                            <input id="phone" name="phone" value="{{ old('phone', $user->phone) }}" inputmode="tel" maxlength="20" autocomplete="tel" placeholder="08xx xxxx xxxx"
                                   @error('phone') aria-invalid="true" aria-describedby="phone-error" @enderror class="field-input @error('phone') !border-bad @enderror">
                            @error('phone')<p id="phone-error" class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="flex justify-end sm:col-span-2">
                            <x-btn type="submit" icon="check">Simpan profil</x-btn>
                        </div>
                    </form>
                </x-panel>

                <x-panel title="Ringkasan akun">
                    <dl class="grid gap-4 text-sm sm:grid-cols-3">
                        <div class="rounded-ctl bg-canvas px-4 py-3"><dt class="text-ink-muted">Role</dt><dd class="mt-0.5 font-semibold text-ink">{{ $roleLabel }}</dd></div>
                        <div class="rounded-ctl bg-canvas px-4 py-3"><dt class="text-ink-muted">Bergabung</dt><dd class="mt-0.5 font-semibold text-ink">{{ $user->created_at ? Format::date($user->created_at->toDateString()) : '–' }}</dd></div>
                        <div class="rounded-ctl bg-canvas px-4 py-3"><dt class="text-ink-muted">Login terakhir</dt><dd class="mt-0.5 font-semibold text-ink">{{ $user->last_login_at ? Format::dateTime($user->last_login_at->format('Y-m-d H:i')) : '–' }}</dd></div>
                    </dl>
                </x-panel>
            </div>
        </div>

        {{-- ================= KEAMANAN ================= --}}
        <div x-show="tab === 'keamanan'" @if ($tab !== 'keamanan') x-cloak @endif class="grid gap-6 xl:grid-cols-2">
            <x-panel title="Ganti password" description="Minimal 8 karakter berisi huruf dan angka. Sesi di perangkat lain akan dikeluarkan.">
                <form method="POST" action="{{ route('account.password.update') }}" class="space-y-5" novalidate>
                    @csrf @method('PUT')
                    @foreach ([['current_password', 'Password saat ini', 'current-password'], ['password', 'Password baru', 'new-password'], ['password_confirmation', 'Ulangi password baru', 'new-password']] as [$field, $label, $ac])
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
                    <div class="flex justify-end"><x-btn type="submit" icon="lock">Ganti password</x-btn></div>
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
                                <p class="font-semibold text-ink">{{ $s['browser'] }} di {{ $s['os'] }}</p>
                                <p class="text-sm text-ink-muted">
                                    <span class="num">{{ $s['ip'] ?? 'IP tidak diketahui' }}</span> ·
                                    @if ($s['current'])
                                        <span class="font-semibold text-ok">Perangkat ini</span>
                                    @else
                                        aktif {{ $s['last']->diffForHumans() }}
                                    @endif
                                </p>
                            </div>
                            @unless ($s['current'])
                                <form method="POST" action="{{ route('account.sessions.destroy', $s['id']) }}" x-data @submit="if (!confirm('Keluarkan sesi di perangkat ini?')) $event.preventDefault()">
                                    @csrf @method('DELETE')
                                    <x-btn type="submit" size="sm" variant="secondary">Keluarkan</x-btn>
                                </form>
                            @endunless
                        </li>
                    @endforeach
                    @if (count($sessions) > 4)
                        <li class="px-5 py-2.5 text-center">
                            <button type="button" class="link text-sm" @click="all = !all"
                                    x-text="all ? 'Sembunyikan' : 'Tampilkan semua {{ count($sessions) }} sesi'">Tampilkan semua {{ count($sessions) }} sesi</button>
                        </li>
                    @endif
                </ul>
                @if (count($sessions) > 1)
                    <form method="POST" action="{{ route('account.sessions.others') }}" class="flex flex-col gap-3 border-t border-line bg-canvas px-5 py-4 sm:flex-row sm:items-end" novalidate>
                        @csrf
                        <div class="flex-1">
                            <label for="sess-password" class="field-label">Keluar dari semua perangkat lain</label>
                            <input type="password" id="sess-password" name="password" autocomplete="current-password" placeholder="Masukkan password Anda" required
                                   @error('password', 'sessions') aria-invalid="true" @enderror class="field-input @error('password', 'sessions') !border-bad @enderror">
                            @error('password', 'sessions')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <x-btn type="submit" variant="danger" icon="log-out">Keluarkan semua</x-btn>
                    </form>
                @else
                    <p class="border-t border-line px-5 py-3 text-sm text-ink-muted">Hanya perangkat ini yang sedang masuk.</p>
                @endif
            </x-panel>
        </div>

        {{-- ================= TAMPILAN ================= --}}
        <div x-show="tab === 'tampilan'" @if ($tab !== 'tampilan') x-cloak @endif>
            <x-panel title="Tema" description="Disimpan di perangkat ini. 'Ikuti sistem' menyesuaikan setelan terang/gelap HP atau laptop Anda." class="max-w-3xl">
                <div x-data="themePicker" class="grid gap-3 sm:grid-cols-3" role="radiogroup" aria-label="Tema">
                    @foreach ([['light', 'Terang', 'sun'], ['dark', 'Gelap', 'moon'], ['system', 'Ikuti sistem', 'monitor']] as [$value, $label, $icon])
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
        </div>

        {{-- ================= AKUN ================= --}}
        <div x-show="tab === 'akun'" @if ($tab !== 'akun') x-cloak @endif class="max-w-3xl space-y-6">
            <x-panel title="Informasi akun">
                <dl class="grid gap-x-8 gap-y-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-ink-muted">Email login</dt><dd class="font-semibold text-ink">{{ $user->email }}</dd></div>
                    <div><dt class="text-ink-muted">Role</dt><dd class="font-semibold text-ink">{{ $roleLabel }}</dd></div>
                    <div><dt class="text-ink-muted">Akun dibuat</dt><dd class="font-semibold text-ink">{{ $user->created_at ? Format::dateTime($user->created_at->format('Y-m-d H:i')) : '–' }}</dd></div>
                    <div><dt class="text-ink-muted">Sesi aktif</dt><dd class="num font-semibold text-ink">{{ count($sessions) }} perangkat</dd></div>
                </dl>
            </x-panel>

            <section class="overflow-hidden rounded-panel border border-bad/40 bg-surface shadow-sm" aria-labelledby="hapus-akun">
                <header class="flex items-start gap-3 border-b border-bad/30 bg-bad-bg/60 px-5 py-4">
                    <x-icon name="user-x" class="mt-0.5 text-bad" />
                    <div>
                        <h2 id="hapus-akun" class="text-md font-bold text-bad">Hapus akun</h2>
                        <p class="mt-0.5 text-sm text-ink-muted">Akun dan foto dihapus permanen. Penilaian, keputusan, dan log yang pernah Anda buat tetap tersimpan sebagai arsip.</p>
                    </div>
                </header>
                <div class="p-5">
                    @if ($deleteBlocker)
                        <p class="flex gap-2 rounded-ctl bg-warn-bg px-4 py-3 text-sm text-warn"><x-icon name="alert" class="mt-0.5 size-4 shrink-0" /> {{ $deleteBlocker }}</p>
                    @else
                        <form method="POST" action="{{ route('account.destroy') }}" class="grid gap-4 sm:grid-cols-2" novalidate
                              x-data="{ text: '' }" @submit="if (!confirm('Hapus akun Anda secara permanen? Tindakan ini tidak bisa dibatalkan.')) $event.preventDefault()">
                            @csrf @method('DELETE')
                            <div>
                                <label for="del-password" class="field-label">Password</label>
                                <input type="password" id="del-password" name="password" autocomplete="current-password" required class="field-input @error('password', 'deleteAccount') !border-bad @enderror">
                                @error('password', 'deleteAccount')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="del-confirm" class="field-label">Ketik <span class="font-mono text-bad">HAPUS</span></label>
                                <input id="del-confirm" name="confirmation" x-model="text" autocomplete="off" required class="field-input @error('confirmation', 'deleteAccount') !border-bad @enderror">
                                @error('confirmation', 'deleteAccount')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="flex justify-end sm:col-span-2">
                                <x-btn type="submit" variant="danger" icon="trash" x-bind:disabled="text !== 'HAPUS'">Hapus akun saya</x-btn>
                            </div>
                        </form>
                    @endif
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
