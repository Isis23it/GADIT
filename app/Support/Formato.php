<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Formatos de presentación del sistema (RF-82).
 *
 * El dinero nunca pasa por float (DEC-03): se recibe como texto, entero o BigNumber.
 */
final class Formato
{
    private const MENOS = '−';

    private const ZONA = 'America/Mexico_City';

    /** Importe en columnas: 1,234.56 (negativo con signo menos). */
    public static function importe(mixed $valor): string
    {
        if ($valor === null) {
            return '';
        }

        [$negativo, $texto] = self::numero($valor);

        return ($negativo ? self::MENOS : '').$texto;
    }

    /** Importe en tarjetas, totales y pie: $1,234.56 */
    public static function moneda(mixed $valor): string
    {
        if ($valor === null) {
            return '';
        }

        [$negativo, $texto] = self::numero($valor);

        return ($negativo ? self::MENOS : '').'$'.$texto;
    }

    /** Porcentaje con dos decimales y espacio antes del signo: 16.00 % */
    public static function porcentaje(mixed $valor): string
    {
        if ($valor === null) {
            return '';
        }

        return self::importe($valor).' %';
    }

    /** Fecha dd/mm/aaaa. */
    public static function fecha(CarbonInterface|string|null $valor): string
    {
        return $valor === null ? '' : Carbon::parse($valor)->format('d/m/Y');
    }

    /** Fecha y hora de auditoría dd/mm/aaaa hh:mm, 24 horas, hora de México (DEC-10). */
    public static function fechaHora(CarbonInterface|string|null $valor): string
    {
        return $valor === null
            ? ''
            : Carbon::parse($valor)->setTimezone(self::ZONA)->format('d/m/Y H:i');
    }

    /**
     * @return array{bool, string} Si es negativo y el texto con miles y 2 decimales.
     */
    private static function numero(mixed $valor): array
    {
        if (is_float($valor)) {
            throw new InvalidArgumentException('El dinero no usa float (DEC-03): pasa un texto, un entero o un BigNumber.');
        }

        $decimal = BigDecimal::of($valor)->toScale(2, RoundingMode::HalfUp);
        [$entero, $decimales] = explode('.', (string) $decimal->abs());
        $entero = (string) preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', $entero);

        return [$decimal->isNegative(), $entero.'.'.$decimales];
    }
}
