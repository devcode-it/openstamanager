<?php

namespace Modules\TipiDocumento\API\Controllers\Models;

use DTO\SelectOptionsRequest;

final class SelectOptionsTipiDocumentoRequest extends SelectOptionsRequest
{
    public string $dir;
}
