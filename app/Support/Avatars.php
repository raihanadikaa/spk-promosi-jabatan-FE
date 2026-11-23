<?php

namespace App\Support;

/**
 * Avatar siap pakai (tanpa foto): gradien + motif sederhana, digambar sebagai SVG inline.
 * Kunci disimpan di users.avatar_preset. Null = inisial nama di atas warna yang diturunkan dari nama.
 */
class Avatars
{
    /** @var array<string, array{label: string, from: string, to: string, motif: string}> */
    public const PRESETS = [
        'tangga' => ['label' => 'Tangga', 'from' => '#2563EB', 'to' => '#1E3A8A', 'motif' => 'stairs'],
        'fajar' => ['label' => 'Fajar', 'from' => '#F59E0B', 'to' => '#DB2777', 'motif' => 'sun'],
        'laut' => ['label' => 'Laut', 'from' => '#06B6D4', 'to' => '#1D4ED8', 'motif' => 'waves'],
        'daun' => ['label' => 'Daun', 'from' => '#34D399', 'to' => '#047857', 'motif' => 'leaf'],
        'senja' => ['label' => 'Senja', 'from' => '#FB7185', 'to' => '#7C3AED', 'motif' => 'moon'],
        'gunung' => ['label' => 'Gunung', 'from' => '#94A3B8', 'to' => '#334155', 'motif' => 'mountain'],
        'bintang' => ['label' => 'Bintang', 'from' => '#6366F1', 'to' => '#0F172A', 'motif' => 'star'],
        'kopi' => ['label' => 'Kopi', 'from' => '#D6A77A', 'to' => '#7C2D12', 'motif' => 'circles'],
    ];

    /** Palet untuk avatar inisial: dipilih tetap dari nama, supaya tiap orang punya warna sendiri. */
    private const INITIAL_COLORS = [
        ['#2563EB', '#1E40AF'], ['#0D9488', '#115E59'], ['#7C3AED', '#5B21B6'], ['#DB2777', '#9D174D'],
        ['#EA580C', '#9A3412'], ['#0284C7', '#075985'], ['#65A30D', '#3F6212'], ['#475569', '#1E293B'],
    ];

    public static function exists(?string $key): bool
    {
        return $key !== null && array_key_exists($key, self::PRESETS);
    }

    /** @return array{0: string, 1: string} */
    public static function initialColors(string $name): array
    {
        return self::INITIAL_COLORS[crc32(mb_strtolower(trim($name))) % count(self::INITIAL_COLORS)];
    }

    public static function initials(string $name): string
    {
        return collect(preg_split('/\s+/', trim($name)) ?: [])
            ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: '?';
    }

    /** Motif putih semi-transparan di atas gradien (viewBox 0 0 64 64). */
    public static function motif(string $motif): string
    {
        return match ($motif) {
            'stairs' => '<path d="M14 46h10v-10h10v-10h14" fill="none" stroke="#fff" stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round"/><circle cx="47" cy="18" r="4" fill="#BFDBFE"/>',
            'sun' => '<circle cx="32" cy="40" r="12" fill="#fff" fill-opacity=".9"/><path d="M8 52h48" stroke="#fff" stroke-opacity=".7" stroke-width="3" stroke-linecap="round"/><path d="M32 16v6M16 26l4 4M48 26l-4 4" stroke="#fff" stroke-opacity=".8" stroke-width="3" stroke-linecap="round"/>',
            'waves' => '<path d="M6 30c6-5 12-5 18 0s12 5 18 0 12-5 18 0M6 42c6-5 12-5 18 0s12 5 18 0 12-5 18 0" fill="none" stroke="#fff" stroke-opacity=".85" stroke-width="3.5" stroke-linecap="round"/>',
            'leaf' => '<path d="M18 46c0-18 12-28 30-28 0 18-10 30-28 30" fill="#fff" fill-opacity=".85"/><path d="M18 46 38 28" stroke="#047857" stroke-opacity=".5" stroke-width="2.5" stroke-linecap="round"/>',
            'moon' => '<path d="M38 16a16 16 0 1 0 12 26 13 13 0 1 1-12-26Z" fill="#fff" fill-opacity=".9"/><circle cx="16" cy="18" r="1.8" fill="#fff"/><circle cx="22" cy="10" r="1.2" fill="#fff" fill-opacity=".8"/>',
            'mountain' => '<path d="m6 50 16-22 10 12 8-10 18 20Z" fill="#fff" fill-opacity=".85"/><path d="m22 28 4 6-4-1-4 2Z" fill="#334155" fill-opacity=".4"/>',
            'star' => '<path d="m32 14 5 11 12 1-9 8 3 12-11-6-11 6 3-12-9-8 12-1Z" fill="#FDE68A"/><circle cx="14" cy="46" r="1.6" fill="#fff"/><circle cx="50" cy="48" r="1.2" fill="#fff" fill-opacity=".8"/>',
            'circles' => '<circle cx="24" cy="28" r="11" fill="#fff" fill-opacity=".85"/><circle cx="42" cy="40" r="8" fill="#fff" fill-opacity=".55"/><circle cx="44" cy="20" r="4" fill="#fff" fill-opacity=".7"/>',
            default => '',
        };
    }
}
