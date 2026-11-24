<?php

use Illuminate\Support\Facades\File;

it('RF-03: la etiqueta no lleva asterisco salvo que el campo se declare obligatorio', function () {
    $this->blade('<x-etiqueta para="nota">Nota</x-etiqueta>')
        ->assertSee('Nota')
        ->assertDontSee('*');

    $this->blade('<x-etiqueta para="nombre" obligatorio>Nombre</x-etiqueta>')
        ->assertSee('Nombre')
        ->assertSee('*')
        ->assertSee('aria-hidden="true"', false);
});

it('RF-03: toda etiqueta de las vistas declara si el campo es obligatorio', function () {
    $revisadas = 0;

    foreach (File::allFiles(resource_path('views')) as $archivo) {
        if (! str_ends_with($archivo->getFilename(), '.blade.php')) {
            continue;
        }

        preg_match_all('/<x-etiqueta\b[^>]*>/', $archivo->getContents(), $usos);

        foreach ($usos[0] as $uso) {
            expect($uso)->toMatch('/\bobligatorio\b/');
            $revisadas++;
        }
    }

    expect($revisadas)->toBeGreaterThan(0);
});
