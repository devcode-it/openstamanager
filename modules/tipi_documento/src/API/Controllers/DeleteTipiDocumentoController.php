<?php

namespace Modules\TipiDocumento\API\Controllers;

use API\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Fatture\Tipo;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class DeleteTipiDocumentoController extends BaseController
{
    public function __invoke(Request $request): JsonResponse
    {
        $this->init($request);
        $id_record = $request->route('id');

        $tipo = Tipo::find($id_record);
        if (!$tipo) {
            throw new NotFoundHttpException();
        }

        $documenti = $dbo->fetchNum('SELECT `id` FROM `co_documenti` WHERE `id_tipo_documento` ='.prepare($id_record));

        if (empty($documenti)) {
            Tipo::destroy($id_record);
        } else {
            $tipo->deleted_at = date();
            $tipo->predefined = 0;
            $tipo->enabled = 0;
            $tipo->save();
        }

        return new JsonResponse();
    }

    protected function hasAccess($request): bool
    {
        return $this->hasModuleReadAccess('Tipi documento');
    }
}
