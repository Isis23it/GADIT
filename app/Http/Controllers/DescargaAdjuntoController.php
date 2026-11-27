<?php

namespace App\Http\Controllers;

use App\Models\Adjunto;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DescargaAdjuntoController extends Controller
{
    public function __invoke(Adjunto $adjunto): StreamedResponse
    {
        // Resuelve el padre manualmente para garantizar compatibilidad con enforceMorphMap
        $clase = Relation::getMorphedModel($adjunto->entidad);
        abort_if($clase === null, 404);
        $padre = $clase::findOrFail($adjunto->entidad_id);

        Gate::authorize('view', $padre);

        abort_unless(Storage::disk('local')->exists($adjunto->ruta), 404);

        $inline = in_array($adjunto->mime, ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png']);

        return $inline
            ? Storage::disk('local')->response($adjunto->ruta, $adjunto->nombre)
            : Storage::disk('local')->download($adjunto->ruta, $adjunto->nombre);
    }
}
