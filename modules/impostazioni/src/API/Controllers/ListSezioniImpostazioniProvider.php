<?php

namespace Modules\Impostazioni\API\Controllers;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Models\Setting;
use Modules\Impostazioni\API\Models\ListSezioniImpostazioniResponse;

final class ListSezioniImpostazioniProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?ListSezioniImpostazioniResponse
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

        return $response;
    }
}
