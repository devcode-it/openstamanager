<?php

namespace Traits;

use App;

trait RowTrait
{
    protected $variabilePrezzo = 'prezzo';

    public function setIVA($id_iva)
    {
        $iva = database()->fetchOne('SELECT * FROM co_iva WHERE id = :id_iva', [
            ':id_iva' => $id_iva,
        ]);
        $descrizione = $iva['descrizione'];

        $valore = ($this->subtotale - $this->sconto) * $iva['percentuale'] / 100;

        $this->idiva = $iva['id'];
        $this->desc_iva = $descrizione;

        $this->iva = $valore;
        $this->iva_indetraibile = $valore / 100 * $iva['indetraibile'];
    }

    public function getPrezzoAttribute()
    {
        return $this->subtotale / $this->qta;
    }

    public function setSubtotale($prezzo, $qta)
    {
        $this->qta = $qta;

        $this->subtotale = $prezzo * $qta;
    }

    /*
    public function getPrezzoAttribute()
    {
        return $this->{$this->variabilePrezzo};
    }

    public function getSubtotaleAttribute()
    {
        return $this->prezzo * $this->qta;
    }
    */

    public function getTotaleAttribute()
    {
        return $this->subtotale + $this->iva;
    }

    public function getScontoAttribute()
    {
        return calcola_sconto([
            'sconto' => $this->sconto_unitario,
            'prezzo' => $this->prezzo,
            'tipo' =>  $this->tipo_sconto,
            'qta' => $this->qta,
        ]);
    }
}
