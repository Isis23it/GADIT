<?php

use App\Support\SemanaIso;

it('RN-11: B10 semana ISO coincide con YEARWEEK(fecha, 3)', function () {
    expect(SemanaIso::de('2024-12-29'))->toBe(202452) // domingo: sem 52 de 2024
        ->and(SemanaIso::de('2024-12-30'))->toBe(202501) // lunes: sem 1 de 2025
        ->and(SemanaIso::de('2025-01-01'))->toBe(202501) // miércoles
        ->and(SemanaIso::de('2021-01-03'))->toBe(202053) // domingo: sem 53 de 2020
        ->and(SemanaIso::de('2025-12-29'))->toBe(202601) // lunes: sem 1 de 2026
        ->and(SemanaIso::de('2026-01-01'))->toBe(202601); // jueves
});

it('RN-11: formatear devuelve Sem SS / AAAA', function () {
    expect(SemanaIso::formatear(202501))->toBe('Sem 01 / 2025')
        ->and(SemanaIso::formatear(202452))->toBe('Sem 52 / 2024')
        ->and(SemanaIso::formatear(202053))->toBe('Sem 53 / 2020');
});
