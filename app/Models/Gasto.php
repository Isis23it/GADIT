<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\GastoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gasto extends Model
{
    /** @use HasFactory<GastoFactory> */
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'gastos';

    protected $fillable = [
        'fecha',
        'concepto',
        'importe_con_iva',
        'categoria_id',
        'cuenta_id',
    ];

    /** @return MorphMany<Adjunto, $this> */
    public function adjuntos(): MorphMany
    {
        return $this->morphMany(Adjunto::class, 'entidad', 'entidad', 'entidad_id');
    }
}
