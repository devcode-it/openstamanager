<?php

namespace Modules\DDT\Components;

use Common\Components\Article;
use Modules\Articoli\Articolo as Original;
use Modules\DDT\DDT;

class Articolo extends Article
{
    use RelationTrait;

    protected $table = 'dt_righe_ddt';
    protected $serialRowID = 'ddt';

    /**
     * Crea un nuovo articolo collegato ad una ddt.
     *
     * @return self
     */
    public static function build(DDT $ddt, Original $articolo)
    {
        $model = parent::build($ddt, $articolo);

        return $model;
    }
}
