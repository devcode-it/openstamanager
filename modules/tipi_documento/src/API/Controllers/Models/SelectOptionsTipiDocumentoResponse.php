<?php

namespace Modules\TipiDocumento\API\Controllers\Models;

use DTO\SelectOptionsRecord;
use DTO\SelectOptionsResponse;

final class SelectOptionsTipiDocumentoRecord extends SelectOptionsRecord
{
    public string $descrizione;
    public string $title;
}

final class SelectOptionsTipiDocumentoResponse extends SelectOptionsResponse
{
    /**
     * @var SelectOptionsTipiDocumentoRecord[]
     */
    public array $results = [];
}
