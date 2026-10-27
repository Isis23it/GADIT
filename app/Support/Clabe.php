<?php

namespace App\Support;

/** Validación y enmascarado de CLABE interbancaria (RN-22, Apéndice D). */
final class Clabe
{
    private const PESOS = [3, 7, 1];

    public static function limpiar(string $valor): string
    {
        return (string) preg_replace('/[\s-]/', '', $valor);
    }

    public static function esValida(string $valor): bool
    {
        $clabe = self::limpiar($valor);

        if (! preg_match('/^\d{18}$/D', $clabe)) {
            return false;
        }

        $suma = 0;
        for ($i = 0; $i < 17; $i++) {
            $suma += ((int) $clabe[$i] * self::PESOS[$i % 3]) % 10;
        }

        return (10 - $suma % 10) % 10 === (int) $clabe[17];
    }

    public static function ultimos4(string $clabe): string
    {
        return substr($clabe, -4);
    }

    public static function enmascarar(string $clabe): string
    {
        return '•••• •••• •••• '.substr($clabe, -4);
    }
}
