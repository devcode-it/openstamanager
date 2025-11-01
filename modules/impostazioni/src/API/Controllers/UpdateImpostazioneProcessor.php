<?php

namespace Modules\Impostazioni\API\Controllers;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Models\Setting;
use Modules\Impostazioni\API\Models\UpdateImpostazioneRequest;
use Modules\Impostazioni\API\Models\UpdateImpostazioneResponse;

final class UpdateImpostazioneProcessor implements ProcessorInterface
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): UpdateImpostazioneResponse
    {
        if (!$data instanceof UpdateImpostazioneRequest) {
            throw new \InvalidArgumentException();
        }

        $id = $uriVariables['id'];
        $response = new UpdateImpostazioneResponse();

        $impostazione = Setting::find($id);
        if (!$impostazione->editable) {
            $response->edited = true;

            return $response;
        }

        $response->edited = \Settings::setValue($impostazione->id, $data->valore);

        return $response;
    }
}
