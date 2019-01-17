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
     * Calcola l'imponibile della fattura.
     *
     * @return float
     */
    public function getImponibileAttribute()
    {
        return $this->calcola('imponibile');
    }

    /**
     * Calcola lo sconto totale della fattura.
     *
     * @return float
     */
    public function getScontoAttribute()
    {
        return $this->calcola('sconto');
    }

    /**
     * Calcola l'imponibile scontato della fattura.
     *
     * @return float
     */
    public function getImponibileScontatoAttribute()
    {
        return $this->calcola('imponibile_scontato');
    }

    /**
     * Calcola l'IVA totale della fattura.
     *
     * @return float
     */
    public function getIvaAttribute()
    {
        return $this->calcola('iva', 'iva_rivalsa_inps');
    }

    /**
     * Calcola la rivalsa INPS totale della fattura.
     *
     * @return float
     */
    public function getRivalsaINPSAttribute()
    {
        return $this->calcola('rivalsa_inps');
    }

    /**
     * Calcola l'iva della rivalsa INPS totale della fattura.
     *
     * @return float
     */
    public function getIvaRivalsaINPSAttribute()
    {
        return $this->calcola('iva_rivalsa_inps');
    }

    /**
     * Calcola la ritenuta d'acconto totale della fattura.
     *
     * @return float
     */
    public function getRitenutaAccontoAttribute()
    {
        return $this->calcola('ritenuta_acconto');
    }

    /**
     * Calcola il totale della fattura.
     *
     * @return float
     */
    public function getTotaleAttribute()
    {
        return $this->calcola('totale');
    }

    /**
     * Calcola il netto a pagare della fattura.
     *
     * @return float
     */
    public function getNettoAttribute()
    {
        return $this->calcola('netto');
    }

    /**
     * Calcola la spesa totale relativa alla fattura.
     *
     * @return float
     */
    public function getSpesaAttribute()
    {
        return $this->calcola('spesa');
    }

    /**
     * Calcola il guadagno della fattura.
     *
     * @return float
     */
    public function getGuadagnoAttribute()
    {
        return $this->calcola('guadagno');
    }

    /**
     * Calcola la somma degli attributi indicati come parametri.
     * Il metodo **non** deve essere adattato per ulteriori funzionalità: deve esclusivamente calcolare la somma richiesta in modo esplicito dagli argomenti.
     *
     * @param mixed ...$args
     *
     * @return float
     */
    protected function calcola(...$args)
    {
        $result = 0;
        foreach ($args as $arg) {
            $result += $this->getRigheContabili()->sum($arg);
        }

        return $this->round($result);
    }

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
     * Funzione per l'arrotondamento degli importi.
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
}
