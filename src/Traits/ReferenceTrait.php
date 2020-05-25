<?php

namespace Traits;

use Stringy\Stringy;

trait ReferenceTrait
{
    abstract public function getReferenceName();

    abstract public function getReferenceNumber();

    abstract public function getReferenceDate();

    public function getReference()
    {
        // Informazioni disponibili
        $name = $this->getReferenceName();

        $number = $this->getReferenceNumber();
        $date = $this->getReferenceDate();

        // Testi predefiniti
        if (!empty($date) && !empty($number)) {
            $description = tr('_DOC_ num. _NUM_ del _DATE_');
        } elseif (!empty($number)) {
            $description = tr('_DOC_ num. _NUM_');
        } elseif (!empty($date)) {
            $description = tr('_DOC_ del _DATE_');
        } else {
            $description = tr('_DOC_');
        }

        // Creazione descrizione
        $description = replace($description, [
            '_DOC_' => $name,
            '_NUM_' => $number,
            '_DATE_' => dateFormat($date),
        ]);

        return $description;
    }
}
