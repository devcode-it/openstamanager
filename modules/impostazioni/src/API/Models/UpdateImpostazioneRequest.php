<?php

namespace Modules\Impostazioni\API\Models;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateImpostazioneRequest
{
    #[Assert\NotBlank]
    public string $valore;
}
