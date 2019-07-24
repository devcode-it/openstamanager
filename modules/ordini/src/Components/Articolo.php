<?php

namespace Modules\Ordini\Components;

use Common\Components\Article;
use Modules\Articoli\Articolo as Original;
use Modules\Ordini\Ordine;

class Articolo extends Article
{
    use RelationTrait;

    public $movimenta_magazzino = false;

    protected $table = 'or_righe_ordini';
    protected $serialRowID = 'ordine';

    /**
     * Crea un nuovo articolo collegato ad una ordine.
     *
     * @param Ordine   $ordine
     * @param Original $articolo
     *
     * @return self
     */
    public static function build(Ordine $ordine, Original $articolo)
    {
        $model = parent::build($ordine, $articolo);

        return $model;
    }

    public function movimentaMagazzino($qta)
    {
        return;
    }

    public function getDirection()
    {
        return $this->ordine->tipo->dir;
    }
}
