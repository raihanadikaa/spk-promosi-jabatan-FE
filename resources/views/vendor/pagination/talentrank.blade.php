{{-- Paginasi TalentRank: ringkasan jumlah + navigasi halaman. Dipasang global di AppServiceProvider. --}}
@if ($paginator->total() > 0)
    <nav class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-5 py-3" aria-label="{{ __('Navigasi halaman') }}">
        <p class="num text-sm text-ink-muted">
            {{ __('Menampilkan :from–:to dari :total', ['from' => $paginator->firstItem(), 'to' => $paginator->lastItem(), 'total' => $paginator->total()]) }}
        </p>

        @if ($paginator->hasPages())
            {{-- Di HP hanya ‹ halaman sekarang › (deretan nomor membuat halaman melebar); nomor lengkap mulai layar sm. --}}
            <ul class="flex flex-wrap items-center gap-1">
                <li>
                    @if ($paginator->onFirstPage())
                        <span class="grid size-9 place-items-center rounded-ctl text-ink-faint" aria-disabled="true" aria-label="{{ __('Sebelumnya') }}"><x-icon name="chevron-left" class="size-4" /></span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="grid size-9 place-items-center rounded-ctl text-ink hover:bg-canvas" aria-label="{{ __('Halaman sebelumnya') }}"><x-icon name="chevron-left" class="size-4" /></a>
                    @endif
                </li>

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <li class="hidden sm:block"><span class="grid size-9 place-items-center text-sm text-ink-faint">…</span></li>
                    @endif
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            <li @class(['hidden sm:block' => $page != $paginator->currentPage()])>
                                @if ($page == $paginator->currentPage())
                                    <span class="num grid size-9 place-items-center rounded-ctl bg-brand text-sm font-bold text-white" aria-current="page"><span class="sr-only sm:hidden">{{ __('Halaman :n dari :total', ['n' => $page, 'total' => $paginator->lastPage()]) }}</span><span aria-hidden="true">{{ $page }}</span></span>
                                @else
                                    <a href="{{ $url }}" class="num grid size-9 place-items-center rounded-ctl text-sm text-ink hover:bg-canvas" aria-label="{{ __('Halaman :n', ['n' => $page]) }}">{{ $page }}</a>
                                @endif
                            </li>
                        @endforeach
                    @endif
                @endforeach

                <li>
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="grid size-9 place-items-center rounded-ctl text-ink hover:bg-canvas" aria-label="{{ __('Halaman berikutnya') }}"><x-icon name="chevron-right" class="size-4" /></a>
                    @else
                        <span class="grid size-9 place-items-center rounded-ctl text-ink-faint" aria-disabled="true" aria-label="{{ __('Berikutnya') }}"><x-icon name="chevron-right" class="size-4" /></span>
                    @endif
                </li>
            </ul>
        @endif
    </nav>
@endif
