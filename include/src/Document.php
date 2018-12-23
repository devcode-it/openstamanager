<?php

namespace Common;

abstract class Document extends Model
{
    /**
     * Restituisce la collezione di righe e articoli con valori rilevanti per i conti.
     *
     * @return iterable
     */
    public function getRighe()
    {
        $descrizioni = $this->descrizioni;
        $righe = $this->righe;
        $articoli = $this->articoli;

        return $descrizioni->merge($righe)->merge($articoli)->sortBy('order');
    }

    abstract public function righe();

    abstract public function articoli();

    abstract public function descrizioni();

    abstract public function scontoGlobale();

    /**
     * Restituisce la collezione di righe e articoli con valori rilevanti per i conti.
     *
     * @return iterable
     */
    protected function getRigheContabili()
    {
        $sconto = $this->scontoGlobale ? [$this->scontoGlobale] : [];

        return $this->getRighe()->merge(collect($sconto));
    }

    /**
     * Funzione per l'arrotondamento degli importi;.
     *
     * @param float $value
     *
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
        return $this->round($this->getRigheContabili()->sum('imponibile'));
    }

    /**
     * Calcola lo sconto totale della fattura.
     *
     * @return float
     */
    public function getScontoAttribute()
    {
        return $this->round($this->getRigheContabili()->sum('sconto'));
    }

    /**
     * Calcola l'imponibile scontato della fattura.
     *
     * @return float
     */
    public function getImponibileScontatoAttribute()
    {
        return $this->round($this->getRigheContabili()->sum('imponibile_scontato'));
    }

    /**
     * Calcola l'IVA totale della fattura.
     *
     * @return float
     */
    public function getIvaAttribute()
    {
        return $this->round($this->getRigheContabili()->sum('iva'));
    }

    /**
     * Calcola la rivalsa INPS totale della fattura.
     *
     * @return float
     */
    public function getRivalsaINPSAttribute()
    {
        return $this->round($this->getRigheContabili()->sum('rivalsa_inps'));
    }

    /**
     * Calcola la ritenuta d'acconto totale della fattura.
     *
     * @return float
     */
    public function getRitenutaAccontoAttribute()
    {
        return $this->round($this->getRigheContabili()->sum('ritenuta_acconto'));
    }

    /**
     * Calcola il totale della fattura.
     *
     * @return float
     */
    public function getTotaleAttribute()
    {
        return $this->round($this->getRigheContabili()->sum('totale'));
    }

    /**
     * Calcola il netto a pagare della fattura.
     *
     * @return float
     */
    public function getNettoAttribute()
    {
        return $this->round($this->getRigheContabili()->sum('netto'));
    }

    /**
     * Calcola la spesa totale relativa alla fattura.
     *
     * @return float
     */
    public function getSpesaAttribute()
    {
        return $this->round($this->getRigheContabili()->sum('spesa'));
    }

    /**
     * Calcola il netto a pagare della fattura.
     *
     * @return float
     */
    public function getGuadagnoAttribute()
    {
        return $this->round($this->getRigheContabili()->sum('guadagno'));
    }
}
