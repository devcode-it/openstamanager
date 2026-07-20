<?php

namespace Modules\TipiDocumento\API\Controllers\Models;

use Symfony\Component\Serializer\Attribute\Ignore;

class UpdateTipiDocumentoRequest
{
    public string $name;
    public string $dir;
    public string $codice_tipo_documento_fe;
    public ?string $help;
    public bool $predefined;
    public bool $enabled;
    public int $id_segment;
    private int $id; // Da URL

    #[Ignore]
    public function getId(): int
    {
        return $this->id;
    }
}
