<?php

namespace Modules\Impostazioni\API\Controllers;

use API\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Models\Setting;
use Modules\Impostazioni\API\ImpostazioneResource;

final class GetImpostazioneController extends BaseController
{
    public function __invoke(Request $request): JsonResponse
    {
        $setting = Setting::find($request->route('id'));
        if (!$setting) {
            return null;
        }

        $response = ImpostazioneResource::fromModel($setting);

        return new JsonResponse($response);
    }
}
