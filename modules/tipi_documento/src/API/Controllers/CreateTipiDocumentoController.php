<?php

namespace Modules\TipiDocumento\API\Controllers;

use API\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Models\Locale;
use Modules\Fatture\Tipo;
use Modules\TipiDocumento\API\Controllers\Models\CreateTipiDocumentoRequest;
use Modules\TipiDocumento\API\Controllers\Models\CreateTipiDocumentoResponse;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class CreateTipiDocumentoController extends BaseController
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $this->init($request, CreateTipiDocumentoRequest::class);

        $tipo_new = Tipo::where('name', $data->name)->where('dir', '=', $data->dir)->where('codice_tipo_documento_fe', '=', $data->codice_tipo_documento_fe)->first();

        if (!empty($tipo_new)) {
            throw new ConflictHttpException(tr('Questa combinazione di nome, codice e direzione è già stata utilizzata per un altro tipo di documento.'));
        }

        $tipo = Tipo::build($data->dir, $data->codice_tipo_documento_fe);
        if (Locale::getDefault()->id == Locale::getPredefined()->id) {
            $tipo->name = $data->name;
        }
        $id_record = database()->lastInsertedID();
        $tipo->save();

        $response = new CreateTipiDocumentoResponse();
        $response->id = $id_record;
        $response->text = $data->name;

        return new JsonResponse($response);
    }

    protected function hasAccess($request): bool
    {
        return $this->hasModuleWriteAccess('Tipi documento');
    }
}
