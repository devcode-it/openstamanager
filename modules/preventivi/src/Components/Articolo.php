<?php

namespace Modules\Preventivi\Components;

use Common\Components\Article;
use Modules\Articoli\Articolo as Original;
use Modules\Preventivi\Preventivo;

class Articolo extends Article
{
    use RelationTrait;

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

    public function movimenta($qta)
    {
        $preventivo = $this->preventivo;
        $tipo = $preventivo->tipo;

        $numero = $preventivo->numero_esterno ?: $preventivo->numero;
        $data = $preventivo->data;

        $carico = ($tipo->dir == 'entrata') ? tr('Ripristino articolo da _TYPE_ _NUM_') : tr('Carico magazzino da _TYPE_ numero _NUM_');
        $scarico = ($tipo->dir == 'entrata') ? tr('Scarico magazzino per _TYPE_ numero _NUM_') : tr('Rimozione articolo da _TYPE_ _NUM_');

        $qta = ($tipo->dir == 'uscita') ? -$qta : $qta;
        $movimento = ($qta < 0) ? $carico : $scarico;

        $movimento = replace($movimento, [
            '_TYPE_' => $tipo->descrizione,
            '_NUM_' => $numero,
        ]);

        $this->articolo->movimenta(-$qta, $movimento, $data, false, [
            'iddocumento' => $preventivo->id,
        ]);
    }

    public function getDirection()
    {
        return $this->preventivo->tipo->dir;
    }
}
