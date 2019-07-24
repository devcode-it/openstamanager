<?php

namespace Modules\Preventivi\Components;

use Common\Components\Article;
use Modules\Articoli\Articolo as Original;
use Modules\Preventivi\Preventivo;

class Articolo extends Article
{
    use RelationTrait;

    public $movimenta_magazzino = false;

    protected $table = 'co_righe_preventivi';

    /**
     * Crea un nuovo articolo collegato ad una preventivo.
     *
     * @param Preventivo $preventivo
     * @param Original   $articolo
     *
     * @return self
     */
    public static function build(Preventivo $preventivo, Original $articolo)
    {
        $model = parent::build($preventivo, $articolo);

        return $model;
    }

    public function movimentaMagazzino($qta)
    {
        return;
    }

    public function getDirection()
    {
        return $this->preventivo->tipo->dir;
    }
}
