<?php

use App\Support\Formato;
use Brick\Math\BigDecimal;
use Carbon\Carbon;

it('RF-82: importe con coma de miles y dos decimales', function () {
    expect(Formato::importe('1234.56'))->toBe('1,234.56');
    expect(Formato::importe('1234567.5'))->toBe('1,234,567.50');
    expect(Formato::importe(1000))->toBe('1,000.00');
    expect(Formato::importe(BigDecimal::of('0.5')))->toBe('0.50');
});

it('RF-82: el negativo lleva signo menos y el cero se muestra 0.00', function () {
    expect(Formato::importe('-1234.56'))->toBe('−1,234.56');
    expect(Formato::importe('-0.001'))->toBe('0.00');
    expect(Formato::importe('0'))->toBe('0.00');
});

it('RF-82: la moneda lleva prefijo de pesos', function () {
    expect(Formato::moneda('1234.56'))->toBe('$1,234.56');
    expect(Formato::moneda('-1234.56'))->toBe('−$1,234.56');
});

it('RF-82: el porcentaje lleva dos decimales y espacio antes del signo', function () {
    expect(Formato::porcentaje('16'))->toBe('16.00 %');
    expect(Formato::porcentaje('22.5'))->toBe('22.50 %');
});

it('DEC-03: rechaza un float para dinero', function () {
    expect(fn () => Formato::importe(1234.56))->toThrow(InvalidArgumentException::class);
});

it('RF-82: fecha en dd/mm/aaaa', function () {
    expect(Formato::fecha('2026-10-14'))->toBe('14/10/2026');
    expect(Formato::fecha(null))->toBe('');
});

it('RF-82: fecha y hora en hora de México con 24 horas', function () {
    $utc = Carbon::parse('2026-10-14 22:05:00', 'UTC');

    expect(Formato::fechaHora($utc))->toBe('14/10/2026 16:05');
});

it('RF-82: un valor nulo se muestra vacío en vez de fallar', function () {
    expect(Formato::importe(null))->toBe('')
        ->and(Formato::moneda(null))->toBe('')
        ->and(Formato::porcentaje(null))->toBe('');
});
