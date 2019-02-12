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
     * Crea un nuovo articolo collegato ad una contratto.
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
        $contratto = $this->contratto;
        $tipo = $contratto->tipo;

        $numero = $contratto->numero_esterno ?: $contratto->numero;
        $data = $contratto->data;

        $carico = ($tipo->dir == 'entrata') ? tr('Ripristino articolo da _TYPE_ _NUM_') : tr('Carico magazzino da _TYPE_ numero _NUM_');
        $scarico = ($tipo->dir == 'entrata') ? tr('Scarico magazzino per _TYPE_ numero _NUM_') : tr('Rimozione articolo da _TYPE_ _NUM_');

        $qta = ($tipo->dir == 'uscita') ? -$qta : $qta;
        $movimento = ($qta < 0) ? $carico : $scarico;

        $movimento = replace($movimento, [
            '_TYPE_' => $tipo->descrizione,
            '_NUM_' => $numero,
        ]);

        $this->articolo->movimenta(-$qta, $movimento, $data, false, [
            'iddocumento' => $contratto->id,
        ]);
    }

    public function getDirection()
    {
        return $this->contratto->tipo->dir;
    }
}
