<?php

namespace App\Livewire\Usuarios;

use App\Enums\RolUsuario;
use App\Livewire\Concerns\ConListado;
use App\Models\Usuario;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Usuarios (RF-15, solo admin): lista con nombre, correo, rol, estado y fecha de alta,
 * con los patrones de listado de RF-01 (búsqueda, orden, paginación y filtros en la URL).
 * El alta, el cambio de rol y desactivar/reactivar (RN-23) se conectan con las actions de usuarios de GAD-37;
 * el botón «Crear usuario» del estado vacío llega con ellas.
 */
#[Layout('layouts.app', ['title' => 'Usuarios'])]
class Usuarios extends Component
{
    use ConListado;

    /** Clave pública de la columna => columna de la tabla. */
    private const COLUMNAS = [
        'nombre' => 'nombre',
        'correo' => 'email',
        'rol' => 'rol',
        'estado' => 'activo',
        'alta' => 'created_at',
    ];

    public function render(): View
    {
        $consulta = Usuario::query();

        $this->aplicarBusqueda($consulta, ['nombre', 'email']);

        $fichas = [];

        $rol = $this->filtro('rol', array_map(fn (RolUsuario $r) => $r->value, RolUsuario::cases()));
        if ($rol !== null) {
            $consulta->where('rol', $rol);
            $fichas['rol'] = 'Rol: '.RolUsuario::from($rol)->label();
        }

        $estado = $this->filtro('estado', ['activo', 'inactivo']);
        if ($estado !== null) {
            $consulta->where('activo', $estado === 'activo');
            $fichas['estado'] = 'Estado: '.($estado === 'activo' ? 'Activo' : 'Inactivo');
        }

        $this->aplicarOrden($consulta, self::COLUMNAS, 'nombre');

        return view('livewire.usuarios.usuarios', [
            'usuarios' => $consulta->paginate($this->tamanoPagina()),
            'ordenActivo' => $this->columnaDeOrden(self::COLUMNAS, 'nombre'),
            'fichas' => $fichas,
            'roles' => RolUsuario::cases(),
            'hayFiltros' => $this->hayFiltrosActivos(),
        ]);
    }
}
