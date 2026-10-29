@php use App\Support\Format; @endphp

<x-app-layout title="Penilaian bawahan"
              subtitle="Isi nilai setiap bawahan untuk {{ $period['name'] }}. {{ $counts['submitted'] }} dari {{ $counts['total'] }} sudah terkirim. Penilaian terkirim terkunci; HRD bisa membukanya kembali.">
    @unless ($open)
        <div class="mb-6 flex gap-3 rounded-panel border border-line bg-surface px-4 py-3 text-ink-muted" role="note">
            <x-icon name="lock" class="mt-0.5" />
            <p>Periode {{ $period['name'] }} tidak sedang berlangsung. Nilai hanya bisa dilihat.</p>
        </div>
    @endunless

    @if ($counts['total'] > 0)
        <x-filter-bar placeholder="Cari nama atau NIK" :search="$filters['q']" :total="$team->total()"
                      :filters="[['name' => 'status', 'label' => 'Status', 'value' => $filters['status'],
                                  'options' => ['pending' => 'Belum dinilai', 'draft' => 'Draft', 'submitted' => 'Terkirim']]]" />
    @endif

    <x-panel flush>
        @if ($counts['total'] === 0)
            <x-empty-state icon="users" title="Belum ada bawahan" body="Hubungi HRD jika data tim Anda belum terdaftar." />
        @elseif ($team->isEmpty())
            <x-empty-state icon="search" title="Tidak ada yang cocok" body="Coba kata kunci lain atau hapus filter." />
        @else
            <div class="overflow-x-auto">
                <table class="data-table min-w-[720px]">
                    <thead>
                        <tr>
                            <th scope="col">Nama</th>
                            <th scope="col">Jabatan</th>
                            <th scope="col" class="text-right">Masa kerja</th>
                            <th scope="col">Status</th>
                            <th scope="col">Terakhir diubah</th>
                            <th scope="col"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($team as $e)
                            <tr>
                                <td>
                                    <p class="font-semibold">{{ $e['name'] }}</p>
                                    <p class="num text-sm text-ink-muted">NIK {{ $e['nik'] }}</p>
                                </td>
                                <td>{{ $e['position'] }}</td>
                                <td class="num text-right">{{ Format::number($e['tenure'], 1) }} th</td>
                                <td><x-status :value="$e['status']" /></td>
                                <td class="text-sm text-ink-muted">{{ Format::dateTime($e['submitted_at'] ?? $e['updated_at']) }}</td>
                                <td class="text-right">
                                    @php
                                        [$label, $variant, $icon] = match (true) {
                                            $e['status'] === 'submitted', ! $open => ['Lihat', 'secondary', 'eye'],
                                            $e['status'] === 'draft' => ['Lanjutkan', 'primary', 'pencil'],
                                            default => ['Isi nilai', 'primary', 'pencil'],
                                        };
                                    @endphp
                                    <x-btn size="sm" :variant="$variant" :icon="$icon" :href="route('manager.assessments.edit', $e['id'])">
                                        {{ $label }}<span class="sr-only"> penilaian {{ $e['name'] }}</span>
                                    </x-btn>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $team->links() }}
        @endif
    </x-panel>
</x-app-layout>
