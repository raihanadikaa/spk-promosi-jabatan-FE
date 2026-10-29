<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Format tampilan angka & tanggal gaya Indonesia (koma desimal).
 */
class Format
{
    public static function score(?float $value, int $decimals = 4): string
    {
        return $value === null ? '–' : number_format($value, $decimals, ',', '.');
    }

    public static function number(float|int $value, int $decimals = 0): string
    {
        return number_format($value, $decimals, ',', '.');
    }

    public static function date(?string $value): string
    {
        return $value ? CarbonImmutable::parse($value)->locale('id')->translatedFormat('j M Y') : '–';
    }

    public static function dateTime(?string $value): string
    {
        return $value ? CarbonImmutable::parse($value)->locale('id')->translatedFormat('j M Y, H.i') : '–';
    }

    /** Selisih hari dari hari ini; positif = masih ada waktu. */
    public static function daysLeft(string $date): int
    {
        return (int) CarbonImmutable::today()->diffInDays(CarbonImmutable::parse($date), false);
    }
}
