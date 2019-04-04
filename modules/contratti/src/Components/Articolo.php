<?php

namespace Modules\Contratti\Components;

use Common\Components\Article;
use Modules\Articoli\Articolo as Original;
use Modules\Contratti\Contratto;

class Articolo extends Article
{
    use RelationTrait;

    protected $table = 'co_righe_contratti';

    /**
     * Crea un nuovo articolo collegato ad un contratto.
     *
     * @param Contratto $contratto
     * @param Original  $articolo
     *
     * @return self
     */
    public static function build(Contratto $contratto, Original $articolo)
    {
        $model = parent::build($contratto, $articolo);

        return $model;
    }

    public function movimenta($qta)
    {
        return;
    }

    public function getDirection()
    {
        return $this->contratto->tipo->dir;
    }
}
