<?php

namespace Modules\Impostazioni\API\Controllers;

use API\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Models\Setting;
use Modules\Impostazioni\API\Models\UpdateImpostazioneRequest;
use Modules\Impostazioni\API\Models\UpdateImpostazioneResponse;

final class UpdateImpostazioneController extends BaseController
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $this->_cast($request, UpdateImpostazioneRequest::class);

        $id = $request->route('id');
        $response = new UpdateImpostazioneResponse();

        $impostazione = Setting::find($id);
        if (!$impostazione->editable) {
            $response->edited = true;

            return new JsonResponse($response);
        }

        $response->edited = \Settings::setValue($impostazione->id, $data->valore);

        return new JsonResponse($response);
    }
}
