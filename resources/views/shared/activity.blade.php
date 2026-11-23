@php
    use App\Support\Format;

    $tones = ['periode' => 'brand', 'kriteria' => 'warning', 'karyawan' => 'neutral', 'penilaian' => 'neutral', 'saw' => 'brand', 'keputusan' => 'success', 'akun' => 'neutral'];
    $active = collect($filters)->filter(fn ($v) => filled($v))->isNotEmpty();
    $isChange = fn ($v) => is_array($v) && (array_key_exists('dari', $v) || array_key_exists('ke', $v));
    $show = fn ($v) => is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : ($v === null || $v === '' ? '–' : (string) $v);
@endphp

<x-app-layout title="Log aktivitas" subtitle="Jejak siapa melakukan apa dan kapan: perubahan bobot, penilaian, perhitungan SAW, keputusan direktur, dan akun.">
    <x-slot:actions>
        <x-btn variant="secondary" icon="download" :href="route($exportRoute, request()->query())">Unduh CSV</x-btn>
    </x-slot:actions>

    {{-- Filter: GET supaya bisa di-bookmark. Tanggal tidak tersedia di komponen filter-bar, jadi ditulis di sini dengan gaya yang sama. --}}
    <form method="GET" class="mb-4 grid gap-3 sm:grid-cols-2 lg:flex lg:flex-wrap lg:items-end" role="search" x-data>
        <div class="relative sm:col-span-2 lg:w-72">
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-ink-faint" />
            <label for="log-q" class="sr-only">Cari keterangan atau nama pengguna</label>
            <input id="log-q" type="search" name="q" value="{{ $filters['q'] }}" maxlength="100" placeholder="Cari keterangan atau nama" class="field-input pl-9">
        </div>
        <div>
            <label for="log-kategori" class="sr-only">Kategori</label>
            <select id="log-kategori" name="kategori" class="field-input pr-9 lg:w-auto" x-on:change="$el.form.requestSubmit()">
                <option value="">Kategori: semua</option>
                @foreach ($categories as $value => $label)
                    <option value="{{ $value }}" @selected($filters['kategori'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="log-periode" class="sr-only">Periode</label>
            <select id="log-periode" name="periode" class="field-input pr-9 lg:w-auto" x-on:change="$el.form.requestSubmit()">
                <option value="">Periode: semua</option>
                @foreach ($periods as $id => $name)
                    <option value="{{ $id }}" @selected($filters['periode'] === $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-center gap-2">
            <label for="log-dari" class="shrink-0 text-sm text-ink-muted">Dari</label>
            <input id="log-dari" type="date" name="dari" value="{{ $filters['dari'] }}" class="field-input @error('dari') !border-bad @enderror">
        </div>
        <div class="flex items-center gap-2">
            <label for="log-sampai" class="shrink-0 text-sm text-ink-muted">Sampai</label>
            <input id="log-sampai" type="date" name="sampai" value="{{ $filters['sampai'] }}" class="field-input @error('sampai') !border-bad @enderror">
        </div>
        <div class="flex items-center gap-3 sm:col-span-2 lg:col-span-1">
            <x-btn type="submit" variant="secondary">Cari</x-btn>
            @if ($active)
                <a href="{{ url()->current() }}" class="link text-sm">Hapus filter</a>
            @endif
            <p class="num ml-auto text-sm text-ink-muted lg:ml-0">{{ Format::number($logs->total()) }} catatan</p>
        </div>
    </form>
    @error('sampai')<p class="field-error -mt-2 mb-4">{{ $message }}</p>@enderror

    <x-panel flush>
        @if ($logs->isEmpty())
            <x-empty-state icon="history" :title="$active ? 'Tidak ada catatan yang cocok' : 'Belum ada aktivitas tercatat'"
                           :body="$active ? 'Coba kata kunci lain atau hapus filter.' : 'Aktivitas mulai tercatat sejak fitur log dipasang.'" />
        @else
            <ul class="divide-y divide-line">
                @foreach ($logs as $log)
                    <li class="px-5 py-3.5" x-data="{ open: false }">
                        <div class="flex flex-wrap items-start gap-x-4 gap-y-1.5">
                            <div class="min-w-0 flex-1">
                                <p class="text-ink">
                                    <span class="font-semibold">{{ $log->user_name ?? 'Sistem' }}</span>
                                    <span class="text-ink-muted">·</span>
                                    {{ $log->description }}
                                </p>
                                <p class="mt-0.5 flex flex-wrap items-center gap-x-2 text-sm text-ink-muted">
                                    <span class="num">{{ Format::dateTime($log->created_at->format('Y-m-d H:i')) }}</span>
                                    @if ($log->period)<span>· {{ $log->period->period_name }}</span>@endif
                                    @if ($log->ip_address)<span class="num">· IP {{ $log->ip_address }}</span>@endif
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <x-badge :tone="$tones[$log->category] ?? 'neutral'">{{ $categories[$log->category] ?? $log->category }}</x-badge>
                                @if ($log->properties)
                                    <button type="button" class="link text-sm" @click="open = !open" :aria-expanded="open">
                                        <span x-text="open ? 'Tutup' : 'Rincian'">Rincian</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                        @if ($log->properties)
                            <dl x-show="open" x-cloak class="mt-3 grid gap-x-6 gap-y-1.5 rounded-ctl bg-canvas px-4 py-3 text-sm sm:grid-cols-[max-content_minmax(0,1fr)]">
                                @foreach ($log->properties as $key => $value)
                                    <dt class="font-semibold text-ink-muted">{{ str_replace('_', ' ', $key) }}</dt>
                                    <dd class="num min-w-0 break-words text-ink">
                                        @if ($isChange($value))
                                            {{ $show($value['dari']) }} → {{ $show($value['ke']) }}
                                        @elseif (is_array($value) && collect($value)->every($isChange))
                                            @foreach ($value as $field => $change)
                                                <span class="mr-3 inline-block">{{ $field }}: {{ $show($change['dari'] ?? null) }} → {{ $show($change['ke'] ?? null) }}</span>
                                            @endforeach
                                        @elseif (is_array($value) && ! array_is_list($value))
                                            @foreach ($value as $k => $v)
                                                <span class="mr-3 inline-block">{{ $k }}: {{ $show($v) }}</span>
                                            @endforeach
                                        @elseif (is_array($value))
                                            {{ implode(', ', array_map($show, $value)) }}
                                        @else
                                            {{ $show($value) }}
                                        @endif
                                    </dd>
                                @endforeach
                            </dl>
                        @endif
                    </li>
                @endforeach
            </ul>
            {{ $logs->links() }}
        @endif
    </x-panel>
    <p class="mt-3 text-sm text-ink-muted">Log hanya bisa dibaca. Tidak ada yang bisa mengubah atau menghapus catatan dari aplikasi.</p>
</x-app-layout>
