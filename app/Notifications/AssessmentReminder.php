<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/** HRD mengingatkan atasan bahwa masih ada bawahan yang belum dinilai / belum dikirim. */
class AssessmentReminder extends Notification
{
    /** @param  list<string>  $names  karyawan yang penilaiannya belum terkirim */
    public function __construct(private string $periodName, private string $deadline, private array $names) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $count = count($this->names);
        $list = implode(', ', array_slice($this->names, 0, 5)) . ($count > 5 ? ', dan ' . ($count - 5) . ' lainnya' : '');

        return [
            // Teks Indonesia tetap disimpan (cadangan); tampilan memakai kunci + data supaya mengikuti bahasa pembaca.
            'title' => 'Pengingat penilaian',
            'body' => "{$count} penilaian periode {$this->periodName} belum dikirim (batas {$this->deadline}): {$list}.",
            'i18n' => [
                'title' => 'Pengingat penilaian',
                'body' => ':count penilaian periode :period belum dikirim (batas :deadline): :list.',
                'params' => ['count' => $count, 'period' => $this->periodName, 'deadline' => $this->deadline,
                    'list' => implode(', ', array_slice($this->names, 0, 5)), 'more' => max(0, $count - 5)],
            ],
            'url' => route('manager.assessments', [], false),
        ];
    }
}
