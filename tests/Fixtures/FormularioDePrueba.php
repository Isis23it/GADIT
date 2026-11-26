<?php

namespace Tests\Fixtures;

use App\Actions\Action;
use App\Exceptions\ReglaDeNegocioException;
use App\Livewire\Concerns\ConFormulario;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

/** Formulario mínimo para probar ConFormulario y los componentes x-formulario (GAD-52). */
class FormularioDePrueba extends Component
{
    use ConFormulario;

    public ?int $registroId = null;

    public string $nombre = '';

    public string $notas = '';

    /** Si trae texto, la acción lanza esa regla de negocio. */
    public ?string $mensajeRegla = null;

    /** @return array<string, array<int, string>> */
    protected function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:120'],
            'notas' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function abrir(?int $id = null): void
    {
        $usuario = $id === null ? null : Usuario::findOrFail($id);

        $this->abrirFormulario($usuario);
        $this->registroId = $id;
        $this->nombre = $usuario->nombre ?? '';
    }

    public function guardar(): void
    {
        $this->guardarFormulario($this->accion());
    }

    public function guardarYCapturarOtro(): void
    {
        $this->guardarFormulario($this->accion(), capturarOtro: true);
    }

    private function accion(): \Closure
    {
        return function (array $datos): void {
            if ($this->mensajeRegla !== null) {
                throw new ReglaDeNegocioException($this->mensajeRegla);
            }

            if ($this->registroId === null) {
                Usuario::factory()->create(['nombre' => $datos['nombre']]);

                return;
            }

            $usuario = Usuario::findOrFail($this->registroId);

            (new class extends Action
            {
                public function verificar(Model $modelo, string $actualizadoEn): void
                {
                    $this->verificarBloqueoOptimista($modelo, $actualizadoEn);
                }
            })->verificar($usuario, (string) $this->actualizadoEn);

            $usuario->update(['nombre' => $datos['nombre']]);
        };
    }

    public function render(): string
    {
        return <<<'HTML'
        <div>
            <x-formulario id="f-prueba" wire:submit="guardar" :error-regla="$errorRegla">
                <div>
                    <x-etiqueta para="nombre">Nombre</x-etiqueta>
                    <input id="nombre" type="text" wire:model="nombre">
                    <x-error-campo para="nombre" />
                </div>
                <div>
                    <x-etiqueta para="notas">Notas</x-etiqueta>
                    <input id="notas" type="text" wire:model="notas">
                    <x-error-campo para="notas" />
                </div>
            </x-formulario>

            <x-formulario.pie formulario="f-prueba" :otro="true" />
        </div>
        HTML;
    }
}
