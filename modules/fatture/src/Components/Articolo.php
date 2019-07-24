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
     * @param Fattura  $fattura
     * @param Original $articolo
     *
     * @return self
     */
    public static function build(Fattura $fattura, Original $articolo)
    {
        $model = parent::build($fattura, $articolo);

        return $model;
    }

    public function movimentaMagazzino($qta)
    {
        // Se il documento è generato da un ddt o intervento allora **non** movimento il magazzino
        if (!empty($this->idddt) || !empty($this->idintervento)) {
            return;
        }

        $fattura = $this->fattura;
        $tipo = $fattura->tipo;

        $numero = $fattura->numero_esterno ?: $fattura->numero;
        $data = $fattura->data;

        $carico = ($tipo->dir == 'entrata') ? tr('Ripristino articolo da _TYPE_ _NUM_') : tr('Carico magazzino da _TYPE_ numero _NUM_');
        $scarico = ($tipo->dir == 'entrata') ? tr('Scarico magazzino per _TYPE_ numero _NUM_') : tr('Rimozione articolo da _TYPE_ _NUM_');

        $qta = ($tipo->dir == 'uscita') ? -$qta : $qta;
        $movimento = ($qta < 0) ? $carico : $scarico;

        $movimento = replace($movimento, [
            '_TYPE_' => $tipo->descrizione,
            '_NUM_' => $numero,
        ]);

        $partenza = $fattura->direzione == 'uscita' ? $fattura->idsede_destinazione : $fattura->idsede_partenza;
        $arrivo = $fattura->direzione == 'uscita' ? $fattura->idsede_partenza : $fattura->idsede_destinazione;

        $this->articolo->movimenta(-$qta, $movimento, $data, false, [
            'iddocumento' => $fattura->id,
            'idsede_azienda' => $partenza,
            'idsede_controparte' => $arrivo,
        ]);
    }

    public function getDirection()
    {
        return $this->fattura->tipo->dir;
    }
}
