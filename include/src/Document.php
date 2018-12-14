<?php

namespace Common;

use Illuminate\Database\Eloquent\Builder;

abstract class Document extends Model
{
    /**
     * Restituisce la collezione di righe e articoli con valori rilevanti per i conti.
     *
     * @return iterable
     */
    protected function getRighe()
    {
        return $this->righe->merge($this->articoli);
    }

    /**
     * Funzione per l'arrotondamento degli importi;
     *
     * @param float $value
     * @return float
     */
    protected function round($value)
    {
        $decimals = 2;

        return round($value, $decimals);
    }

    /**
     * Calcola l'imponibile della fattura.
     *
     * @return float
     */
    public function getImponibileAttribute()
    {
        return $this->round($this->getRighe()->sum('imponibile'));
    }

    /**
     * Calcola lo sconto totale della fattura.
     *
     * @return float
     */
    public function getScontoAttribute()
    {
        return $this->round($this->getRighe()->sum('sconto'));
    }

    /**
     * Calcola l'imponibile scontato della fattura.
     *
     * @return float
     */
    public function getImponibileScontatoAttribute()
    {
        return $this->round($this->getRighe()->sum('imponibile_scontato'));
    }

    /**
     * Calcola l'IVA totale della fattura.
     *
     * @return float
     */
    public function getIvaAttribute()
    {
        return $this->round($this->getRighe()->sum('iva'));
    }

    /**
     * Calcola la rivalsa INPS totale della fattura.
     *
     * @return float
     */
    public function getRivalsaINPSAttribute()
    {
        return $this->round($this->getRighe()->sum('rivalsa_inps'));
    }

    /**
     * Calcola la ritenuta d'acconto totale della fattura.
     *
     * @return float
     */
    public function getRitenutaAccontoAttribute()
    {
        return $this->round($this->getRighe()->sum('ritenuta_acconto'));
    }

    /**
     * Calcola il totale della fattura.
     *
     * @return float
     */
    public function getTotaleAttribute()
    {
        return $this->round($this->getRighe()->sum('totale'));
    }

    /**
     * Calcola il netto a pagare della fattura.
     *
     * @return float
     */
    public function getNettoAttribute()
    {
        return $this->round($this->getRighe()->sum('netto'));
    }
}
