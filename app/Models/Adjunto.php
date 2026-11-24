<?php

namespace App\Models;

use App\Enums\EntidadAdjunto;
use App\Models\Concerns\Auditable;
use Database\Factories\AdjuntoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Adjunto extends Model
{
    /** @use HasFactory<AdjuntoFactory> */
    use Auditable, HasFactory;

    protected $table = 'adjuntos';

    // La tabla solo tiene created_at (sin updated_at)
    const UPDATED_AT = null;

    protected $fillable = [
        'entidad',
        'entidad_id',
        'nombre',
        'ruta',
        'mime',
        'subido_por',
    ];

    // shortcut: 'entidad' no se castea a enum para no romper la resolución de morphTo; usar EntidadAdjunto::from($adjunto->entidad) cuando se necesite el enum
    protected static function bootAdjunto(): void
    {
        // shortcut: si el disco falla silenciosamente el archivo queda huérfano; envolver en transacción al llamarlo desde una Action
        static::deleting(function (self $adjunto): void {
            Storage::disk('local')->delete($adjunto->ruta);
        });
    }

    /**
     * El registro padre (Gasto, Factura, Cobro, etc.).
     * Nombre 'padre' para evitar conflicto con el atributo 'entidad' (EntidadAdjunto).
     *
     * @return MorphTo<Model, $this>
     */
    public function padre(): MorphTo
    {
        return $this->morphTo('entidad', 'entidad', 'entidad_id');
    }
}
