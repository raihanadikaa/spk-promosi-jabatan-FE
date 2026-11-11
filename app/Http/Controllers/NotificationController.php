<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Notifikasi di aplikasi (lonceng di sidebar). Hanya milik user yang login. */
class NotificationController extends Controller
{
    /** Tandai dibaca lalu buka halaman tujuannya. */
    public function open(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;
        // Hanya path internal (diawali satu "/"), supaya tidak bisa dipakai sebagai open redirect.
        $safe = is_string($url) && str_starts_with($url, '/') && ! str_starts_with($url, '//');

        return $safe ? redirect($url) : redirect()->to($request->user()->dashboardUrl());
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
