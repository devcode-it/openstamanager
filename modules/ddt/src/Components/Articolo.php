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

    public function movimentaMagazzino($qta)
    {
        $ddt = $this->ddt;
        $tipo = $ddt->tipo;

        $numero = $ddt->numero_esterno ?: $ddt->numero;
        $data = $ddt->data;

        $carico = ($tipo->dir == 'entrata') ? tr('Ripristino articolo da _TYPE_ numero _NUM_') : tr('Carico magazzino da _TYPE_ numero _NUM_');
        $scarico = ($tipo->dir == 'entrata') ? tr('Scarico magazzino per _TYPE_ numero _NUM_') : tr('Rimozione articolo da _TYPE_ numero _NUM_');

        $qta = ($tipo->dir == 'uscita') ? -$qta : $qta;
        $movimento = ($qta < 0) ? $carico : $scarico;

        $movimento = replace($movimento, [
            '_TYPE_' => $tipo->descrizione,
            '_NUM_' => $numero,
        ]);

        $partenza = $ddt->direzione == 'uscita' ? $ddt->idsede_destinazione : $ddt->idsede_partenza;
        $arrivo = $ddt->direzione == 'uscita' ? $ddt->idsede_partenza : $ddt->idsede_destinazione;

        $this->articolo->movimenta(-$qta, $movimento, $data, false, [
            'idddt' => $ddt->id,
            'idsede_azienda' => $partenza,
            'idsede_controparte' => $arrivo,
        ]);
    }

    public function getDirection()
    {
        return $this->ddt->tipo->dir;
    }
}
