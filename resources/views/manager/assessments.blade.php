@php use App\Support\Format; @endphp

<x-app-layout title="Penilaian bawahan" subtitle="Isi nilai setiap bawahan untuk {{ $period['name'] }}. Penilaian yang sudah dikirim tidak bisa diubah tanpa persetujuan HRD.">
    <x-panel flush>
        @if (empty($team))
            <x-empty-state icon="users" title="Belum ada bawahan" body="Hubungi HRD jika data tim Anda belum terdaftar." />
        @else
            <div class="overflow-x-auto">
                <table class="data-table min-w-[720px]">
                    <thead>
                        <tr>
                            <th scope="col">Nama</th>
                            <th scope="col">Jabatan</th>
                            <th scope="col" class="text-right">Masa kerja</th>
                            <th scope="col">Status</th>
                            <th scope="col">Terakhir dikirim</th>
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
                                <td class="text-sm text-ink-muted">{{ Format::dateTime($e['submitted_at']) }}</td>
                                <td class="text-right">
                                    @php
                                        [$label, $variant, $icon] = match ($e['status']) {
                                            'submitted' => ['Lihat', 'secondary', 'eye'],
                                            'draft' => ['Lanjutkan', 'primary', 'pencil'],
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
        @endif
    </x-panel>
</x-app-layout>
