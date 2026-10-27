<?php

use App\Support\Dinero;

it('RN-01: B3 redondeo medio hacia arriba', function () {
    expect((string) Dinero::of('2.675')->round())->toBe('2.68')
        ->and((string) Dinero::of('1.005')->round())->toBe('1.01')
        ->and((string) Dinero::of('0.005')->round())->toBe('0.01')
        ->and((string) Dinero::of('-0.005')->round())->toBe('-0.01')
        ->and((string) Dinero::of('348316.296')->round())->toBe('348316.30')
        ->and((string) Dinero::of('804778.6912')->round())->toBe('804778.69');
});

it('RN-01: acepta 1,234.56 y 1234.56 como el mismo valor', function () {
    expect(Dinero::of('1,234.56')->isEqualTo(Dinero::of('1234.56')))->toBeTrue();
});

it('RN-01: acepta el importe máximo y rechaza el que lo supera', function () {
    expect(fn () => Dinero::of('999999999999.99'))->not->toThrow(OverflowException::class);
    expect(fn () => Dinero::of('1000000000000.00'))->toThrow(OverflowException::class);
});

it('B4: IVA de cobros parciales sin perder centavos', function () {
    // Cobro 1: ROUND(50.02 × 16 / 100, 2) = ROUND(8.0032, 2) = 8.00
    $iva1 = Dinero::of('50.02')->times('16')->dividedBy('100')->round();
    expect((string) $iva1)->toBe('8.00');

    // IVA total de la factura: ROUND(100.05 × 16 / 100, 2) = ROUND(16.008, 2) = 16.01
    $ivaFactura = Dinero::of('100.05')->times('16')->dividedBy('100')->round();
    expect((string) $ivaFactura)->toBe('16.01');

    // Cobro 2 liquida: 16.01 - 8.00 = 8.01
    expect((string) $ivaFactura->minus($iva1))->toBe('8.01');
});

it('B8: reparto de utilidad con redondeo exacto', function () {
    $utilidad = Dinero::of('5187349.65');

    // 30 % → 1,556,204.895 → 1,556,204.90
    expect((string) $utilidad->times('30')->dividedBy('100')->round())->toBe('1556204.90');
    // 25 % → 1,296,837.4125 → 1,296,837.41
    expect((string) $utilidad->times('25')->dividedBy('100')->round())->toBe('1296837.41');
    // 22.5 % → 1,167,153.67125 → 1,167,153.67
    expect((string) $utilidad->times('22.5')->dividedBy('100')->round())->toBe('1167153.67');
});
