<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Notifikasi di aplikasi (lonceng di bar atas + halaman riwayat). Hanya milik user yang login. */
class NotificationController extends Controller
{
    public const PER_PAGE = 15;

    /** Riwayat semua notifikasi (sudah & belum dibaca), terbaru dulu. */
    public function index(Request $request): View
    {
        $filter = $request->query('status') === 'belum' ? 'belum' : 'semua';
        $user = $request->user();

        return view('notifications.index', [
            'filter' => $filter,
            'notifications' => ($filter === 'belum' ? $user->unreadNotifications() : $user->notifications())
                ->latest()->paginate(self::PER_PAGE)->withQueryString(),
            'unread' => $user->unreadNotifications()->count(),
            'total' => $user->notifications()->count(),
        ]);
    }

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

        return back()->with('success', __('Semua notifikasi ditandai sudah dibaca.'));
    }
}
