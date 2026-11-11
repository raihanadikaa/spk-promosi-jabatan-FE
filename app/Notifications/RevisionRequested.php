<?php

namespace App\Notifications;

use App\Models\RankingResult;
use Illuminate\Notifications\Notification;

/** Direktur meminta HRD meninjau ulang satu kandidat promosi. Disimpan di tabel notifications. */
class RevisionRequested extends Notification
{
    public function __construct(private RankingResult $result, private string $director) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Permintaan review ulang',
            'body' => "{$this->director} meminta review ulang untuk {$this->result->employee->name}: {$this->result->approval_note}",
            'url' => route('hrd.ranking.show', $this->result->employee_id, false),
        ];
    }
}
