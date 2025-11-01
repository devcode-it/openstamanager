<?php

namespace Modules\Impostazioni\API\Controllers;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ParameterNotFound;
use ApiPlatform\State\ProviderInterface;
use Models\Setting;
use Modules\Impostazioni\API\ImpostazioneResource;

final class ListImpostazioniProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?array
    {
        $sezione = $operation->getParameters()->get('sezione')->getValue();
        $search = $operation->getParameters()->get('ricerca')->getValue();

        // Trova le impostazioni che corrispondono alla ricerca
        if (!($search instanceof ParameterNotFound)) {
            $impostazioni = Setting::where('nome', 'like', '%'.$search.'%')
                ->orWhere('sezione', 'like', '%'.$search.'%')
                ->get();
        } elseif (!($sezione instanceof ParameterNotFound)) {
            $impostazioni = Setting::where('sezione', $sezione)->get();
        } else {
            $impostazioni = Setting::all();
        }

        $results = [];
        foreach ($impostazioni as $impostazione) {
            $results[] = ImpostazioneResource::fromModel($impostazione);
        }

        return $results;
    }
}
