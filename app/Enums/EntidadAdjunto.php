<?php

namespace App\Enums;

enum EntidadAdjunto: string
{
    case Gasto          = 'gasto';
    case Factura        = 'factura';
    case Cobro          = 'cobro';
    case Contrato       = 'contrato';
    case CuentaPorPagar = 'cuenta_por_pagar';
    case Prestamo       = 'prestamo';

    public function label(): string
    {
        return match ($this) {
            self::Gasto          => 'Gasto',
            self::Factura        => 'Factura',
            self::Cobro          => 'Cobro',
            self::Contrato       => 'Contrato',
            self::CuentaPorPagar => 'Cuenta por pagar',
            self::Prestamo       => 'Préstamo',
        };
    }
}
