<?php

use App\Support\Clabe;

it('RN-22: CLABE válida con verificador correcto', function () {
    expect(Clabe::esValida('002180001234567896'))->toBeTrue()
        ->and(Clabe::esValida('012180000000000015'))->toBeTrue()
        ->and(Clabe::esValida('030180012345678907'))->toBeTrue();
});

it('RN-22: limpia espacios y guiones antes de validar', function () {
    expect(Clabe::esValida('0021 8000 1234 5678 96'))->toBeTrue();
});

it('RN-22: CLABE inválida es rechazada', function () {
    expect(Clabe::esValida('002180001234567895'))->toBeFalse()  // verificador incorrecto
        ->and(Clabe::esValida('00218000123456789'))->toBeFalse()   // 17 dígitos
        ->and(Clabe::esValida('0021800012345678960'))->toBeFalse() // 19 dígitos
        ->and(Clabe::esValida('00218000123456789A'))->toBeFalse(); // contiene letra
});

it('RN-22: ultimos4 devuelve los últimos 4 dígitos', function () {
    expect(Clabe::ultimos4('002180001234567896'))->toBe('7896');
});

it('RN-22: enmascarar muestra solo los últimos 4 dígitos', function () {
    expect(Clabe::enmascarar('002180001234567896'))->toBe('•••• •••• •••• 7896');
});
