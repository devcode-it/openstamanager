<?php

namespace Modules\Fatture\Components;

use Common\Components\Article;
use Modules\Articoli\Articolo as Original;
use Modules\Fatture\Fattura;

class Articolo extends Article
{
    use RelationTrait;

    protected $table = 'co_righe_documenti';
    protected $serialRowID = 'documento';

    /**
     * Crea un nuovo articolo collegato ad una fattura.
     *
     * @return self
     */
    public static function build(Fattura $fattura, Original $articolo)
    {
        $model = parent::build($fattura, $articolo);

        return $model;
    }

    public function movimenta($qta)
    {
        parent::movimenta($qta);

        // Movimentazione forzata per Note di credito/debito
        if ($this->parent->isNota()) {
            $this->movimentaMagazzino($qta);
        }
    }
}
