<?php

return [
    'min' => [
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'password' => [
        'letters' => 'El campo :attribute debe contener al menos una letra.',
        'mixed' => 'El campo :attribute debe contener al menos una mayúscula y una minúscula.',
        'numbers' => 'El campo :attribute debe contener al menos un número.',
        'symbols' => 'El campo :attribute debe contener al menos un símbolo.',
        'uncompromised' => 'La :attribute elegida apareció en una filtración de datos. Elige otra distinta.',
    ],
    'attributes' => [
        'password' => 'contraseña',
        'password_actual' => 'contraseña actual',
        'password_confirmation' => 'confirmación de la contraseña',
        'nombre' => 'nombre',
        'email' => 'correo electrónico',
    ],
];
