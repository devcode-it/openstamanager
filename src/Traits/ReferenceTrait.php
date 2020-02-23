<?php

namespace Traits;

use Models\Module;
use Models\Plugin;
use Stringy\Stringy;

trait ReferenceTrait
{
    public abstract function getReferenceName();
    public abstract function getReferenceNumber();
    public abstract function getReferenceDate();

    public function getReference(){
        $name = $this->getReferenceName();
        $number = $this->getReferenceNumber();
        $date = $this->getReferenceDate();

        // Testo relativo
        $name = Stringy::create($name)->toLowerCase();

        if (!empty($date) && !empty($number)) {
            $description = tr('Rif. _DOC_ num. _NUM_ del _DATE_', [
                '_DOC_' => $name,
                '_NUM_' => $number,
                '_DATE_' => dateFormat($date),
            ]);
        } else if (!empty($number)) {
            $description = tr('Rif. _DOC_ num. _NUM_', [
                '_DOC_' => $name,
                '_NUM_' => $number,
            ]);
        } else if (!empty($date)) {
            $description = tr('Rif. _DOC_ del _DATE_', [
                '_DOC_' => $name,
                '_DATE_' => dateFormat($date),
            ]);
        }else {
            $description = tr('Rif. _DOC_', [
                '_DOC_' => $name,
            ]);
        }

        return $description;
    }
}
