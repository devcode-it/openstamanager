<?php

namespace Modules\Impostazioni\API\Controllers;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Models\Setting;
use Modules\Impostazioni\API\Models\ListSezioniImpostazioniResponse;

final class ListSezioniImpostazioniProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?ListSezioniImpostazioniResponse
    {
        $user = Auth::user();
        if (!$user || !$user->is_admin) {
            throw new AuthorizationException();
        }

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
