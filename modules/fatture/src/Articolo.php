<?php

namespace Modules\Fatture;

use Modules\Articoli\Articolo as Original;
use Base\Article;

class Articolo extends Article
{
    protected $table = 'co_righe_documenti';

    /**
     * Crea un nuovo articolo collegato ad una fattura.
     *
     * @param Fattura  $fattura
     * @param Original $articolo
     *
     * @return self
     */
    public static function make(Fattura $fattura, Original $articolo)
    {
        $model = parent::make($articolo);

        $model->fattura()->associate($fattura);

        $model->save();

        return $model;
    }

    public function movimenta($qta)
    {
        // Se il documento è generato da un ddt o intervento allora **non** movimento il magazzino
        if (!empty($this->idddt) || !empty($this->idintervento)) {
            return;
        }

        $fattura = $this->fattura()->first();
        $tipo = $fattura->tipo()->first();

        $numero = $fattura->numero_esterno ?: $fattura->numero;
        $data = $fattura->data;

        $carico = ($tipo->dir == 'entrata') ? tr('Ripristino articolo da _TYPE_ _NUM_') : tr('Carico magazzino da _TYPE_ numero _NUM_');
        $scarico = ($tipo->dir == 'entrata') ? tr('Scarico magazzino per _TYPE_ numero _NUM_') : tr('Rimozione articolo da _TYPE_ _NUM_') ;

        $qta = ($tipo->dir == 'uscita') ? -$qta : $qta;
        $movimento = ($qta < 0) ? $carico : $scarico;

        $movimento = replace($movimento, [
            '_TYPE_' => $tipo->descrizione,
            '_NUM_' => $numero,
        ]);

        $this->articolo()->first()->movimenta(-$qta, $movimento, $data, false, [
            'iddocumento' => $fattura->id,
        ]);
    }

    public function getDirection()
    {
        return $this->fattura()->first()->tipo()->first()->dir;
    }

    public function fattura()
    {
        return $this->belongsTo(Fattura::class, 'iddocumento');
    }
}
