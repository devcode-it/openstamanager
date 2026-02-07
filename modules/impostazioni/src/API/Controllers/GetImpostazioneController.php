<?php

namespace Modules\Impostazioni\API\Controllers;

use API\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Models\Setting;
use Modules\Impostazioni\API\ImpostazioneResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

final class GetImpostazioneController extends BaseController
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->is_admin) {
            throw new AuthorizationException();
        }

        $setting = Setting::find($request->route('id'));
        if (!$setting) {
            return null;
        }

        $response = ImpostazioneResource::fromModel($setting);

        return new JsonResponse($response);
    }
}
