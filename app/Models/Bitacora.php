<?php

namespace App\Models;

use App\Enums\AccionBitacora;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bitacora extends Model
{
    protected $table = 'bitacora';

    // La tabla solo tiene created_at (RN-21, SEG-12)
    const UPDATED_AT = null;

    protected $fillable = [
        'usuario_id',
        'tabla',
        'registro_id',
        'accion',
        'antes',
        'despues',
    ];

    protected function casts(): array
    {
        return [
            'accion'  => AccionBitacora::class,
            'antes'   => 'array',
            'despues' => 'array',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
