<?php

namespace Modules\Impostazioni\API\Controllers;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Models\Setting;
use Modules\Impostazioni\API\ImpostazioneResource;

final class GetImpostazioneProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?ImpostazioneResource
    {
        $setting = Setting::find($uriVariables['id']);
        if (!$setting) {
            return null;
        }

        $response = ImpostazioneResource::fromModel($setting);

        return $response;
    }
}
