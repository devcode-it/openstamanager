<?php

namespace Modules\TipiDocumento\API\Controllers;

use API\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Models\Locale;
use Modules\Fatture\Tipo;
use Modules\TipiDocumento\API\Controllers\Models\UpdateTipiDocumentoRequest;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class UpdateTipiDocumentoController extends BaseController
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $this->init($request, UpdateTipiDocumentoRequest::class);

        $tipo_new = Tipo::where('name', $data->name)->where('dir', '=', $data->dir)->where('codice_tipo_documento_fe', '=', $data->codice_tipo_documento_fe)->first();

        if (!empty($tipo_new) && $tipo_new->id != $data->getId()) {
            throw new ConflictHttpException(tr('Questa combinazione di nome, codice e direzione è già stata utilizzata per un altro tipo di documento.'));
        }
        if (!empty($predefined)) {
            Tipo::where('dir', $data->dir)->update(['predefined' => 0]);
        }

        $tipo = Tipo::find($data->getId());
        if (Locale::getDefault()->id == Locale::getPredefined()->id) {
            $tipo->name = $data->name;
        }
        $tipo->dir = $data->dir;
        $tipo->codice_tipo_documento_fe = $data->codice_tipo_documento_fe;
        $tipo->help = $data->help;
        $tipo->predefined = $data->predefined;
        $tipo->enabled = $data->enabled;
        $tipo->id_segment = $data->id_segment;
        $tipo->save();

        $tipo->setTranslation('title', $data->name);

        return new JsonResponse();
    }

    protected function hasAccess($request): bool
    {
        return $this->hasModuleWriteAccess('Tipi documento');
    }
}
