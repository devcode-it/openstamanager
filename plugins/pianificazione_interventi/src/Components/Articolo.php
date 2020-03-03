<?php

namespace Plugins\PianificazioneInterventi\Components;

use Common\Components\Article;
use Modules\Articoli\Articolo as Original;
use Modules\Contratti\Contratto;
use Plugins\PianificazioneInterventi\Promemoria;

class Articolo extends Article
{
    use RelationTrait;

    public $movimenta_magazzino = false;

    protected $table = 'co_righe_promemoria';

    /**
     * Crea un nuovo articolo collegato ad un contratto.
     *
     * @return self
     */
    public static function build(Promemoria $promemoria, Original $articolo)
    {
        $model = parent::build($promemoria, $articolo);

        return $model;
    }
}
