@php
    use App\Support\Format;
    use Illuminate\Support\Js;

    $unfinished = $counts['pending'] + $counts['draft'];
    $pct = fn ($n) => $counts['total'] ? round($n / $counts['total'] * 100) : 0;
@endphp

<x-app-layout title="Monitor penilaian" subtitle="Pantau kelengkapan penilaian {{ $period['name'] }} dan ingatkan atasan yang belum mengirim.">
    @if ($canRemind && $unfinished > 0)
        <x-slot:actions>
            <form method="POST" action="{{ route('hrd.monitor.remind-all') }}"
                  x-data @submit="if (!confirm({{ Js::from('Kirim pengingat ke semua atasan yang masih punya ' . $unfinished . ' penilaian belum terkirim?') }})) $event.preventDefault()">
                @csrf
                <x-btn type="submit" icon="mail">Ingatkan semua atasan</x-btn>
            </form>
        </x-slot:actions>
    @endif

    <x-stat-strip class="mb-6" :items="[
        ['label' => 'Terkirim', 'value' => $counts['submitted'], 'hint' => $pct($counts['submitted']) . '% dari ' . $counts['total']],
        ['label' => 'Draft', 'value' => $counts['draft'], 'hint' => 'sudah mulai diisi'],
        ['label' => 'Belum dinilai', 'value' => $counts['pending'], 'hint' => 'perlu ditindaklanjuti'],
        ['label' => 'Batas penilaian', 'value' => Format::date($period['deadline']), 'hint' => $period['status'] === 'ongoing' ? Format::daysLeft($period['deadline']) . ' hari lagi' : 'periode ' . ($period['status'] === 'closed' ? 'ditutup' : 'belum dimulai')],
    ]" />

    @unless ($canRemind)
        <div class="mb-4 flex items-start gap-3 rounded-panel border border-line bg-info-bg px-4 py-3 text-sm text-info">
            <x-icon name="info" class="mt-0.5 size-4 shrink-0" />
            <p>Pengingat hanya bisa dikirim saat periode sedang berlangsung.</p>
        </div>
    @endunless
    @if ($withoutSupervisor > 0)
        <div class="mb-4 flex items-start gap-3 rounded-panel border border-warn/30 bg-warn-bg px-4 py-3 text-sm text-warn">
            <x-icon name="alert" class="mt-0.5 size-4 shrink-0" />
            <p>{{ $withoutSupervisor }} karyawan aktif belum punya atasan penilai, jadi tidak ada yang bisa menilai mereka.
               <a href="{{ route('hrd.employees') }}" class="font-semibold underline">Atur di halaman Karyawan</a>.</p>
        </div>
    @endif

    <x-filter-bar placeholder="Cari nama atau NIK" :search="$filters['q']" :total="$employees->total()"
                  :filters="[
                      ['name' => 'status', 'label' => 'Status', 'options' => ['unfinished' => 'Belum selesai', 'pending' => 'Belum dinilai', 'draft' => 'Draft', 'submitted' => 'Terkirim'], 'value' => $filters['status']],
                      ['name' => 'department', 'label' => 'Departemen', 'options' => $departments, 'value' => $filters['department_id']],
                  ]" />

    <x-panel flush>
        @if ($employees->isEmpty())
            <x-empty-state icon="search" title="Tidak ada karyawan yang cocok" body="Coba kata kunci lain atau hapus filter." />
        @else
            <div class="overflow-x-auto">
                <table class="data-table min-w-[820px]">
                    <thead>
                        <tr>
                            <th scope="col">Karyawan</th>
                            <th scope="col">Atasan penilai</th>
                            <th scope="col">Departemen</th>
                            <th scope="col">Status</th>
                            <th scope="col">Terakhir diperbarui</th>
                            <th scope="col"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($employees as $e)
                            <tr>
                                <td>
                                    <p class="font-semibold">{{ $e['name'] }}</p>
                                    <p class="num text-sm text-ink-muted">NIK {{ $e['nik'] }}</p>
                                </td>
                                <td @class(['!text-bad font-medium' => ! $e['supervisor_id']])>{{ $e['assessor'] }}</td>
                                <td>{{ $e['department'] }}</td>
                                <td><x-status :value="$e['status']" /></td>
                                <td class="num text-ink-muted">{{ $e['updated_at'] ? Format::dateTime($e['updated_at']) : '–' }}</td>
                                <td class="text-right">
                                    @if ($canRemind && $e['status'] !== 'submitted' && $e['supervisor_id'])
                                        <form method="POST" action="{{ route('hrd.monitor.remind', $e['id']) }}">
                                            @csrf
                                            <x-btn type="submit" size="sm" variant="ghost" icon="mail">Ingatkan<span class="sr-only"> atasan {{ $e['name'] }}</span></x-btn>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $employees->links() }}
        @endif
    </x-panel>
    <p class="mt-3 text-sm text-ink-muted">Pengingat muncul sebagai notifikasi di akun atasan, berisi daftar bawahan yang penilaiannya belum terkirim.</p>
</x-app-layout>
