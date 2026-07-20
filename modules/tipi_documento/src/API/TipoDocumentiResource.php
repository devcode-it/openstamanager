<?php

namespace Modules\TipiDocumento\API;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Modules\Fatture\Tipo;
use Modules\TipiDocumento\API\Controllers\CreateTipiDocumentoController;
use Modules\TipiDocumento\API\Controllers\DeleteTipiDocumentoController;
use Modules\TipiDocumento\API\Controllers\GetTipiDocumentoController;
use Modules\TipiDocumento\API\Controllers\Models\CreateTipiDocumentoRequest;
use Modules\TipiDocumento\API\Controllers\Models\CreateTipiDocumentoResponse;
use Modules\TipiDocumento\API\Controllers\Models\UpdateTipiDocumentoRequest;
use Modules\TipiDocumento\API\Controllers\UpdateTipiDocumentoController;

#[ApiResource(
    shortName: 'TipiDocumenti',
    operations: [
        /*
        new GetCollection(
            uriTemplate: '/tipi-documenti',
            controller: ListTipiDocumentoController::class,
            paginationEnabled: false,
        ),
        */
        new Get(
            uriTemplate: '/tipo-documenti/{id}',
            controller: GetTipiDocumentoController::class,
        ),
        new Post(
            uriTemplate: '/tipo-documenti',
            controller: CreateTipiDocumentoController::class,
            input: CreateTipiDocumentoRequest::class,
            output: CreateTipiDocumentoResponse::class,
        ),
        new Put(
            uriTemplate: '/tipo-documenti/{id}',
            controller: UpdateTipiDocumentoController::class,
            input: UpdateTipiDocumentoRequest::class,
        ),
        new Delete(
            uriTemplate: '/tipo-documenti/{id}',
            controller: DeleteTipiDocumentoController::class,
        ),
    ],
)]
class TipiDocumentoResource
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $reversed,
        public string $dir,
        public string $codice_tipo_documento_fe,
        public ?string $help,
        public bool $predefined,
        public bool $enabled,
        public int $id_segment,
        public ?string $deleted_at,
        public ?string $created_at,
        public ?string $updated_at,
    ) {
    }

    public static function fromModel(Tipo $record)
    {
        return new self(
            id: $record->id,
            name: $record->name,
            reversed: $record->reversed,
            dir: $record->dir,
            codice_tipo_documento_fe: $record->codice_tipo_documento_fe,
            help: $record->help,
            predefined: $record->predefined,
            enabled: $record->enabled,
            id_segment: $record->id_segment,
            deleted_at: $record->deleted_at,
            created_at: $record->created_at,
            updated_at: $record->updated_at,
        );
    }
}
