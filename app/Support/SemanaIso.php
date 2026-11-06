<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/** Semana ISO equivalente a YEARWEEK(fecha, 3) de MySQL: empieza en lunes, formato AAAASS (RN-11). */
final class SemanaIso
{
    /** Devuelve 202501, 202452, etc. Coincide con YEARWEEK(fecha, 3) de MySQL. */
    public static function de(CarbonInterface|string $fecha): int
    {
        $c = $fecha instanceof CarbonInterface ? $fecha : Carbon::parse($fecha);

        return $c->isoWeekYear * 100 + $c->isoWeek;
    }

    /** 202501 → 'Sem 01 / 2025' */
    public static function formatear(int $semana): string
    {
        return sprintf('Sem %02d / %d', $semana % 100, intdiv($semana, 100));
    }
}
