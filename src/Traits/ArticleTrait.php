<?php

namespace Traits;

use App;

trait ArticleTrait
{
    use RowTrait;

    protected $serialID = 'documento';

    public function setSerials($serials)
    {
        database()->sync('mg_prodotti', [
            'id_riga_'.$this->serialID => $this->id,
            'dir' => 'entrata',
            'id_articolo' => $this->idarticolo,
        ], [
            'serial' => $serials,
        ]);
    }
}
