<?php

use App\Models\Usuario;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\Fixtures\FormularioDePrueba;

it('RF-03: el error de validación sale bajo el campo y conserva lo capturado', function () {
    Livewire::test(FormularioDePrueba::class)
        ->set('nombre', '')
        ->set('notas', 'lo capturado')
        ->call('guardar')
        ->assertHasErrors(['nombre' => 'required'])
        ->assertSeeHtml('id="nombre-error"')
        ->assertSet('notas', 'lo capturado');

    expect(Usuario::count())->toBe(0);
});

it('RF-03: un error de regla de negocio sale en un aviso rojo con el texto de la sección 7', function () {
    Livewire::test(FormularioDePrueba::class)
        ->set('nombre', 'Prueba')
        ->set('mensajeRegla', 'La fecha no puede ser posterior a hoy')
        ->call('guardar')
        ->assertSet('errorRegla', 'La fecha no puede ser posterior a hoy')
        ->assertSeeHtml('role="alert"')
        ->assertSee('La fecha no puede ser posterior a hoy')
        ->assertNotDispatched('aviso');

    expect(Usuario::count())->toBe(0);
});

it('RF-04: al guardar avisa «Guardado» y guarda el registro', function () {
    Livewire::test(FormularioDePrueba::class)
        ->set('nombre', 'Caso A')
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertSet('errorRegla', null)
        ->assertDispatched('aviso', tipo: 'exito', mensaje: 'Guardado');

    expect(Usuario::where('nombre', 'Caso A')->exists())->toBeTrue();
});

it('RF-04: los botones se desactivan mientras se guarda', function () {
    Livewire::test(FormularioDePrueba::class)
        ->assertSeeHtml('wire:loading.attr="disabled"')
        ->assertSeeHtml('wire:target="guardar,guardarYCapturarOtro"');
});

it('RF-04: «Guardar y capturar otro» guarda y deja el formulario vacío', function () {
    Livewire::test(FormularioDePrueba::class)
        ->assertSee('Guardar y capturar otro')
        ->set('nombre', 'Caso B')
        ->set('notas', 'algo')
        ->call('guardarYCapturarOtro')
        ->assertHasNoErrors()
        ->assertSet('nombre', '')
        ->assertSet('notas', '')
        ->assertDispatched('aviso', tipo: 'exito', mensaje: 'Guardado');

    expect(Usuario::where('nombre', 'Caso B')->exists())->toBeTrue();
});

it('RF-05: si nadie modificó el registro, guarda los cambios', function () {
    $usuario = Usuario::factory()->create(['nombre' => 'Original']);

    Livewire::test(FormularioDePrueba::class)
        ->call('abrir', $usuario->id)
        ->set('nombre', 'Editado')
        ->call('guardar')
        ->assertSet('errorRegla', null)
        ->assertDispatched('aviso', tipo: 'exito', mensaje: 'Guardado');

    expect($usuario->fresh()->nombre)->toBe('Editado');
});

it('RF-05: si updated_at cambió muestra el aviso de DEC-12 y no guarda', function () {
    $usuario = Usuario::factory()->create(['nombre' => 'Original']);

    $formulario = Livewire::test(FormularioDePrueba::class)->call('abrir', $usuario->id);

    // Otra persona modifica el registro después de que se abrió el formulario.
    $this->travel(5)->seconds();
    $usuario->update(['nombre' => 'De otra persona']);

    $formulario
        ->set('nombre', 'Mío')
        ->call('guardar')
        ->assertSet('errorRegla', 'Otra persona modificó este registro mientras lo editabas. Recarga para ver los cambios')
        ->assertSeeHtml('role="alert"')
        ->assertNotDispatched('aviso');

    expect($usuario->fresh()->nombre)->toBe('De otra persona');
});

it('DEC-12: el cliente no puede sobrescribir actualizadoEn para saltarse el bloqueo', function () {
    expect(fn () => Livewire::test(FormularioDePrueba::class)->set('actualizadoEn', '2020-01-01 00:00:00'))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});
