<x-app-layout title="Notifikasi" subtitle="Pengingat penilaian, permintaan review ulang, dan kabar lain untuk akun Anda.">
    <x-slot:actions>
        @if ($unread)
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <x-btn type="submit" variant="secondary" icon="check">{{ __('Tandai semua dibaca') }}</x-btn>
            </form>
        @endif
    </x-slot:actions>

    <div class="mb-4 inline-flex gap-1 rounded-panel border border-line bg-surface p-1 shadow-sm" role="group" aria-label="{{ __('Saring notifikasi') }}">
        @foreach (['semua' => __('Semua (:n)', ['n' => $total]), 'belum' => __('Belum dibaca (:n)', ['n' => $unread])] as $key => $label)
            <a href="{{ route('notifications.index', $key === 'belum' ? ['status' => 'belum'] : []) }}"
               @class(['rounded-ctl px-4 py-2 text-sm font-semibold transition-colors', 'bg-brand text-white shadow-sm' => $filter === $key, 'text-ink-muted hover:bg-canvas hover:text-ink' => $filter !== $key])
               @if ($filter === $key) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </div>

    <x-panel flush>
        @if ($notifications->isEmpty())
            <x-empty-state icon="bell" title="{{ $filter === 'belum' ? __('Semua notifikasi sudah dibaca') : __('Belum ada notifikasi') }}"
                           body="Notifikasi muncul saat ada pengingat penilaian atau permintaan dari direktur." />
        @else
            <ul class="tr-stagger divide-y divide-line">
                @foreach ($notifications as $n)
                    <li>
                        <a href="{{ route('notifications.open', $n->id) }}" @class(['flex items-start gap-4 px-5 py-4 transition-colors hover:bg-canvas', 'bg-brand-soft/40' => ! $n->read_at])>
                            <span @class(['grid size-10 shrink-0 place-items-center rounded-full', 'bg-brand-soft text-brand' => ! $n->read_at, 'bg-info-bg text-info' => $n->read_at]) aria-hidden="true">
                                <x-icon name="bell" class="size-4" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex flex-wrap items-center gap-2">
                                    <span @class(['text-ink', 'font-bold' => ! $n->read_at, 'font-semibold' => $n->read_at])>{{ \App\Support\NotificationText::title($n) }}</span>
                                    @unless ($n->read_at)<x-badge tone="brand">{{ __('Baru') }}</x-badge>@endunless
                                </span>
                                <span class="mt-0.5 block text-sm text-ink-muted">{{ \App\Support\NotificationText::body($n) }}</span>
                                <span class="mt-1 block text-xs text-ink-faint">{{ $n->created_at->diffForHumans() }} · {{ \App\Support\Format::dateTime($n->created_at->format('Y-m-d H:i')) }}</span>
                            </span>
                            <x-icon name="chevron-right" class="mt-2 text-ink-faint" />
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="border-t border-line px-5 py-3">{{ $notifications->links() }}</div>
        @endif
    </x-panel>
</x-app-layout>
