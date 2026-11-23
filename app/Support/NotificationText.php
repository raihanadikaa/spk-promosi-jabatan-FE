<?php

namespace App\Support;

use Illuminate\Notifications\DatabaseNotification;

/**
 * Judul & isi notifikasi dalam bahasa pembaca. Notifikasi baru menyimpan kunci + data (data['i18n']);
 * notifikasi lama (tanpa kunci) tetap ditampilkan dengan teks yang tersimpan.
 */
class NotificationText
{
    public static function title(DatabaseNotification $n): string
    {
        $i18n = $n->data['i18n'] ?? null;

        return $i18n ? __($i18n['title']) : ($n->data['title'] ?? __('Notifikasi'));
    }

    public static function body(DatabaseNotification $n): string
    {
        $i18n = $n->data['i18n'] ?? null;
        if (! $i18n) {
            return (string) ($n->data['body'] ?? '');
        }
        $params = $i18n['params'] ?? [];
        // Daftar nama: ", dan N lainnya" ikut diterjemahkan.
        if (! empty($params['more'])) {
            $params['list'] .= ', ' . __('dan :n lainnya', ['n' => $params['more']]);
        }
        unset($params['more']);

        return __($i18n['body'], $params);
    }
}
