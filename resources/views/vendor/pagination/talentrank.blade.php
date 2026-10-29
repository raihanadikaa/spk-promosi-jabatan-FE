{{-- Paginasi TalentRank: ringkasan jumlah + navigasi halaman. Dipasang global di AppServiceProvider. --}}
@if ($paginator->total() > 0)
    <nav class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-5 py-3" aria-label="Navigasi halaman">
        <p class="num text-sm text-ink-muted">
            Menampilkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }}
        </p>

        @if ($paginator->hasPages())
            <ul class="flex items-center gap-1">
                <li>
                    @if ($paginator->onFirstPage())
                        <span class="grid size-9 place-items-center rounded-ctl text-ink-faint" aria-disabled="true" aria-label="Sebelumnya"><x-icon name="chevron-left" class="size-4" /></span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="grid size-9 place-items-center rounded-ctl text-ink hover:bg-canvas" aria-label="Halaman sebelumnya"><x-icon name="chevron-left" class="size-4" /></a>
                    @endif
                </li>

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <li><span class="grid size-9 place-items-center text-sm text-ink-faint">…</span></li>
                    @endif
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            <li>
                                @if ($page == $paginator->currentPage())
                                    <span class="num grid size-9 place-items-center rounded-ctl bg-brand text-sm font-bold text-white" aria-current="page">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}" class="num grid size-9 place-items-center rounded-ctl text-sm text-ink hover:bg-canvas" aria-label="Halaman {{ $page }}">{{ $page }}</a>
                                @endif
                            </li>
                        @endforeach
                    @endif
                @endforeach

                <li>
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="grid size-9 place-items-center rounded-ctl text-ink hover:bg-canvas" aria-label="Halaman berikutnya"><x-icon name="chevron-right" class="size-4" /></a>
                    @else
                        <span class="grid size-9 place-items-center rounded-ctl text-ink-faint" aria-disabled="true" aria-label="Berikutnya"><x-icon name="chevron-right" class="size-4" /></span>
                    @endif
                </li>
            </ul>
        @endif
    </nav>
@endif
