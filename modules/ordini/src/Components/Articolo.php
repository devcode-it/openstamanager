<?php

namespace Modules\Ordini\Components;

use Common\Components\Article;
use Modules\Articoli\Articolo as Original;
use Modules\Ordini\Ordine;

class Articolo extends Article
{
    use RelationTrait;

    protected $table = 'or_righe_ordini';

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

    public function movimenta($qta)
    {
        $ordine = $this->ordine;
        $tipo = $ordine->tipo;

        $numero = $ordine->numero_esterno ?: $ordine->numero;
        $data = $ordine->data;

        $carico = ($tipo->dir == 'entrata') ? tr('Ripristino articolo da _TYPE_ _NUM_') : tr('Carico magazzino da _TYPE_ numero _NUM_');
        $scarico = ($tipo->dir == 'entrata') ? tr('Scarico magazzino per _TYPE_ numero _NUM_') : tr('Rimozione articolo da _TYPE_ _NUM_');

        $qta = ($tipo->dir == 'uscita') ? -$qta : $qta;
        $movimento = ($qta < 0) ? $carico : $scarico;

        $movimento = replace($movimento, [
            '_TYPE_' => $tipo->descrizione,
            '_NUM_' => $numero,
        ]);

        $this->articolo->movimenta(-$qta, $movimento, $data, false, [
            'iddocumento' => $ordine->id,
        ]);
    }

    public function getDirection()
    {
        return $this->ordine->tipo->dir;
    }
}
