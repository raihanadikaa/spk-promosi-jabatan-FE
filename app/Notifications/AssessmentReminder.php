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
            'title' => 'Pengingat penilaian',
            'body' => "{$count} penilaian periode {$this->periodName} belum dikirim (batas {$this->deadline}): {$list}.",
            'url' => route('manager.assessments', [], false),
        ];
    }
}
