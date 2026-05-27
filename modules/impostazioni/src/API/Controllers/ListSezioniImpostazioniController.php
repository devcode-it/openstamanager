<?php

namespace Modules\Impostazioni\API\Controllers;

use API\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Models\Setting;
use Modules\Impostazioni\API\Models\ListSezioniImpostazioniResponse;

final class ListSezioniImpostazioniController extends BaseController
{
    public function __invoke(Request $request): JsonResponse
    {
        $gruppi = Setting::selectRaw('sezione AS nome, COUNT(id) AS numero')
            ->groupBy(['sezione'])
            ->orderBy('sezione')
            ->get();

        $response = new ListSezioniImpostazioniResponse();
        $response->sezioni = [];

        foreach ($gruppi as $key => $gruppo) {
            $response->sezioni[$gruppo->nome] = $gruppo->numero;
        }

        return new JsonResponse($response);
    }
}
