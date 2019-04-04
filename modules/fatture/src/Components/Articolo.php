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

    public function movimenta($qta)
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

        $this->articolo->movimenta(-$qta, $movimento, $data, false, [
            'iddocumento' => $fattura->id,
        ]);
    }

    public function evasioneAltriArticoli($diff)
    {
        parent::evasioneAltriArticoli($diff);

        $database = database();

        // Se c'è un collegamento ad un ddt, aggiorno la quantità evasa
        if (!empty($this->idddt)) {
            $database->query('UPDATE dt_righe_ddt SET qta_evasa = qta_evasa + '.$diff.' WHERE descrizione = '.prepare($this->descrizione).' AND idarticolo = '.prepare($this->idarticolo).' AND idddt = '.prepare($this->idddt).' AND idiva = '.prepare($this->idiva).' AND qta_evasa < qta LIMIT 1');
        }

        // Se c'è un collegamento ad un ordine, aggiorno la quantità evasa
        elseif (!empty($this->idordine)) {
            $database->query('UPDATE or_righe_ordini SET qta_evasa = qta_evasa + '.$diff.' WHERE descrizione = '.prepare($this->descrizione).' AND idarticolo = '.prepare($this->idarticolo).' AND idordine = '.prepare($this->idordine).' AND idiva = '.prepare($this->idiva).' AND qta_evasa < qta LIMIT 1');
        }
    }

    public function getDirection()
    {
        return $this->fattura->tipo->dir;
    }
}
