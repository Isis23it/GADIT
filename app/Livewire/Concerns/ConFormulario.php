<?php

namespace App\Livewire\Concerns;

use App\Exceptions\ReglaDeNegocioException;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;

/**
 * Comportamiento común de los formularios (RF-02 a RF-05, DEC-12).
 *
 * El componente que lo use define sus reglas de validación y dos métodos públicos:
 * guardar() y guardarYCapturarOtro(), que llaman a guardarFormulario() con la Action correspondiente.
 */
trait ConFormulario // @phpstan-ignore trait.unused (primer uso real en GAD-58; hoy solo lo usa el componente de prueba)
{
    /** Aviso rojo sobre el formulario con el texto de la sección 7 (RF-03). */
    public ?string $errorRegla = null;

    /** updated_at del registro al abrir el formulario; la Action lo compara para el bloqueo optimista (RF-05). */
    #[Locked]
    public ?string $actualizadoEn = null;

    /** Prepara el formulario al abrirlo: sin errores previos y, al editar, con el updated_at actual. */
    protected function abrirFormulario(?Model $registro = null): void
    {
        $this->resetValidation();
        $this->errorRegla = null;
        $this->actualizadoEn = $registro === null ? null : (string) $registro->updated_at;
    }

    /**
     * Valida en servidor (RF-03), ejecuta la acción y avisa «Guardado» (RF-04).
     * Un error de regla de negocio (incluido el de RF-05) se muestra sobre el formulario y no guarda.
     *
     * @param  \Closure(array<string, mixed>): mixed  $accion  Recibe los datos ya validados.
     */
    protected function guardarFormulario(\Closure $accion, bool $capturarOtro = false, ?string $modal = null): bool
    {
        $this->errorRegla = null;

        $datos = $this->validate();

        try {
            $accion($datos);
        } catch (ReglaDeNegocioException $e) {
            $this->errorRegla = $e->getMessage();

            return false;
        }

        $this->dispatch('aviso', tipo: 'exito', mensaje: 'Guardado');

        if ($capturarOtro) {
            $this->reiniciarFormulario();
        } elseif ($modal !== null) {
            $this->dispatch('cerrar-modal', nombre: $modal);
        }

        return true;
    }

    /**
     * Deja el formulario vacío para «Guardar y capturar otro».
     * Resetea TODAS las propiedades públicas: si el componente tiene filtros o ids que deben sobrevivir,
     * se sobrescribe y se usa resetExcept().
     */
    protected function reiniciarFormulario(): void
    {
        $this->reset();
        $this->resetValidation();
    }
}
