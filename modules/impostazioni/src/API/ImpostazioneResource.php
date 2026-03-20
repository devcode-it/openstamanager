<?php

namespace Modules\Impostazioni\API;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\QueryParameter;
use Models\Setting;
use Modules\Impostazioni\API\Controllers\GetImpostazioneController;
use Modules\Impostazioni\API\Controllers\ListImpostazioniController;
use Modules\Impostazioni\API\Controllers\ListSezioniImpostazioniController;
use Modules\Impostazioni\API\Controllers\UpdateImpostazioneController;
use Modules\Impostazioni\API\Models\ListSezioniImpostazioniResponse;
use Modules\Impostazioni\API\Models\UpdateImpostazioneRequest;
use Modules\Impostazioni\API\Models\UpdateImpostazioneResponse;

#[ApiResource(
    shortName: 'Impostazione',
    operations: [
        new Get(
            uriTemplate: '/impostazioni/sezioni',
            controller: ListSezioniImpostazioniController::class,
            output: ListSezioniImpostazioniResponse::class,
        ),
        new GetCollection(
            uriTemplate: '/impostazioni',
            controller: ListImpostazioniController::class,
            paginationEnabled: false,
            parameters: [
                'ricerca' => new QueryParameter(property: 'hydra:freetextQuery', required: false),
                'sezione' => new QueryParameter(property: 'hydra:freetextQuery', required: false),
            ]
        ),
        new Get(
            uriTemplate: '/impostazione/{id}',
            controller: GetImpostazioneController::class,
        ),
        new Put(
            uriTemplate: '/impostazione/{id}',
            controller: UpdateImpostazioneController::class,
            input: UpdateImpostazioneRequest::class,
            output: UpdateImpostazioneResponse::class,
        ),
    ],
)]
class ImpostazioneResource
{
    public function __construct(
        public int $id,
        public string $sezione,
        public string $nome,
        public ?string $valore,
        public string $tipo,
        public bool $editable,
        public ?string $created_at,
        public ?string $updated_at,
    ) {
    }

    public static function fromModel(Setting $setting)
    {
        return new self(
            id: $setting->id,
            sezione: $setting->sezione,
            nome: $setting->nome,
            valore: $setting->valore,
            tipo: $setting->tipo,
            editable: $setting->editable,
            created_at: $setting->created_at,
            updated_at: $setting->updated_at,
        );
    }
}
