<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

/**
 * Listados reutilizables (RF-01): búsqueda con espera de 300 ms, orden por columna,
 * tamaño de página de 10, 25, 50 o 100 y filtros, todo guardado en la URL.
 */
trait ConListado
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $buscar = '';

    #[Url(as: 'orden', except: '')]
    public string $orden = '';

    #[Url(as: 'dir', except: 'asc')]
    public string $direccion = 'asc';

    #[Url(as: 'por_pagina', except: 25)]
    public int $porPagina = 25;

    /** @var array<string, mixed> */
    #[Url(as: 'filtros', except: [])]
    public array $filtros = [];

    /** @return list<int> */
    public function tamanosPagina(): array
    {
        return [10, 25, 50, 100];
    }

    public function tamanoPagina(): int
    {
        return in_array($this->porPagina, $this->tamanosPagina(), true) ? $this->porPagina : 25;
    }

    public function updatedBuscar(): void
    {
        $this->resetPage();
    }

    public function updatedPorPagina(): void
    {
        $this->resetPage();
    }

    public function updatedFiltros(): void
    {
        $this->filtros = array_filter($this->filtros, fn ($valor) => $valor !== '' && $valor !== null);
        $this->resetPage();
    }

    public function ordenarPor(string $columna): void
    {
        if ($this->orden === $columna) {
            $this->direccion = $this->direccion === 'asc' ? 'desc' : 'asc';
        } else {
            $this->orden = $columna;
            $this->direccion = 'asc';
        }

        $this->resetPage();
    }

    /** Orden desde el selector de móvil: «columna:asc» o «columna:desc». */
    public function ordenarCon(string $valor): void
    {
        [$columna, $direccion] = array_pad(explode(':', $valor, 2), 2, '');

        if ($columna === '') {
            return;
        }

        $this->orden = $columna;
        $this->direccion = $direccion === 'desc' ? 'desc' : 'asc';
        $this->resetPage();
    }

    public function quitarFiltro(string $clave): void
    {
        unset($this->filtros[$clave]);
        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $this->reset('buscar', 'filtros');
        $this->resetPage();
    }

    protected function hayFiltrosActivos(): bool
    {
        return trim($this->buscar) !== '' || $this->filtros !== [];
    }

    /**
     * Valor de un filtro de la URL, solo si es uno de los permitidos.
     *
     * @param  list<string>  $permitidos
     */
    protected function filtro(string $clave, array $permitidos): ?string
    {
        $valor = $this->filtros[$clave] ?? null;

        return is_string($valor) && in_array($valor, $permitidos, true) ? $valor : null;
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $consulta
     * @param  list<string>  $columnas
     */
    protected function aplicarBusqueda(Builder $consulta, array $columnas): void
    {
        $texto = trim($this->buscar);

        if ($texto === '') {
            return;
        }

        $patron = '%'.addcslashes($texto, '\\%_').'%';

        $consulta->where(function (Builder $q) use ($columnas, $patron): void {
            foreach ($columnas as $columna) {
                $q->orWhere($columna, 'like', $patron);
            }
        });
    }

    /**
     * Clave pública de la columna por la que se ordena; si la URL trae una que no existe, la de por defecto.
     *
     * @param  array<string, string>  $columnas  clave pública => columna SQL
     */
    protected function columnaDeOrden(array $columnas, string $porDefecto): string
    {
        return array_key_exists($this->orden, $columnas) ? $this->orden : $porDefecto;
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $consulta
     * @param  array<string, string>  $columnas  clave pública => columna SQL
     */
    protected function aplicarOrden(Builder $consulta, array $columnas, string $porDefecto): void
    {
        $clave = $this->columnaDeOrden($columnas, $porDefecto);
        $direccion = $this->direccion === 'desc' ? 'desc' : 'asc';

        $consulta->orderBy($columnas[$clave], $direccion)
            ->orderBy($consulta->getModel()->getQualifiedKeyName());
    }
}
