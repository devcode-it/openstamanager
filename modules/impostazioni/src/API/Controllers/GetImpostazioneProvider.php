<?php

namespace Modules\Impostazioni\API\Controllers;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Models\Setting;
use Modules\Impostazioni\API\ImpostazioneResource;

final class GetImpostazioneProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?ImpostazioneResource
    {
        $user = Auth::user();
        if (!$user || !$user->is_admin) {
            throw new AuthorizationException();
        }

        $setting = Setting::find($uriVariables['id']);
        if (!$setting) {
            return null;
        }

        $response = ImpostazioneResource::fromModel($setting);

        return $response;
    }
}
