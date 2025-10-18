<?php

namespace Controllers;

use ApiPlatform\State\ProcessorInterface;

class APIController implements ProcessorInterface
{
    public function process(mixed $data, ...$args): mixed
    {  
        return $this->__invoke($data);
    }

    public function __invoke(mixed $data): mixed {
        return $data;
    }
}
