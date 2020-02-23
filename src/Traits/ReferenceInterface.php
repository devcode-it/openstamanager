<?php

namespace Traits;

use Models\Module;
use Models\Plugin;
use Stringy\Stringy;

interface ReferenceInterface
{
    public function getReferenceName();
    public function getReferenceNumber();
    public function getReferenceDate();

    public function getReference();
}
