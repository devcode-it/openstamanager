<?php

namespace Traits;

use Stringy\Stringy;

trait ReferenceTrait
{
    abstract public function getReferenceName();

    abstract public function getReferenceNumber();

    abstract public function getReferenceDate();

    public function getReference($text = null)
    {
        // Testo di default
        $text = empty($text) ? tr('Rif. _DOCUMENT_') : $text;
        $content = [];

        // Testo relativo
        $name = $this->getReferenceName();
        $name = Stringy::create($name)->toLowerCase();
        $content[] = $name;

        // Riferimento al numero
        $number = $this->getReferenceNumber();
        if (!empty($number)) {
            $content[] = tr('num. _NUM_', [
                '_NUM_' => $number,
            ]);
        }

        // Riferimento alla data
        $date = $this->getReferenceDate();
        if (!empty($date)) {
            $content[] = tr('del _DATE_', [
                '_DATE_' => dateFormat($date),
            ]);
        }

        // Creazione descrizione
        $description = replace($text, [
            '_DOCUMENT_' => implode(' ', $content),
        ]);

        return $description;
    }
}
