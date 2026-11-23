<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\AssessmentPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Pencatat log aktivitas. Dipanggil setelah aksi berhasil.
 * Kalau pencatatan gagal, aksi utamanya tetap jalan (kegagalan hanya ditulis ke log server).
 */
class Activity
{
    /**
     * @param  string  $action  kategori.aksi, mis. "periode.mulai"; bagian sebelum titik menjadi kategori
     * @param  array<string, mixed>  $properties  detail tambahan (nilai lama/baru, jumlah, dsb). Jangan isi password.
     */
    public static function log(string $action, string $description, ?Model $subject = null, AssessmentPeriod|int|null $period = null, array $properties = []): void
    {
        try {
            $user = Auth::user();

            ActivityLog::create([
                'user_id' => $user?->id,
                'user_name' => $user?->name,
                'category' => str($action)->before('.')->toString(),
                'action' => $action,
                'description' => mb_strimwidth($description, 0, 500, '…'),
                'subject_type' => $subject ? class_basename($subject) : null,
                'subject_id' => $subject?->getKey(),
                'period_id' => $period instanceof AssessmentPeriod ? $period->id : $period,
                'properties' => $properties ?: null,
                'ip_address' => request()?->ip(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Gagal mencatat log aktivitas', ['action' => $action, 'error' => $e->getMessage()]);
        }
    }
}
