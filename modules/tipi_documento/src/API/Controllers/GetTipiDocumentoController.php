<?php

namespace Modules\TipiDocumento\API\Controllers;

use API\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Fatture\Tipo;
use Modules\TipiDocumento\API\TipiDocumentoResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetTipiDocumentoController extends BaseController
{
    public function __invoke(Request $request): JsonResponse
    {
        $this->init($request);

        $id_record = $request->route('id');

        $tipo = Tipo::find($id_record);
        if (empty($tipo)) {
            throw new NotFoundHttpException();
        }
        $response = TipiDocumentoResource::fromModel($tipo);

        return new JsonResponse($response);
    }

    protected function hasAccess($request): bool
    {
        return $this->hasModuleReadAccess('Tipi documento');
    }
}
