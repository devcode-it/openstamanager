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
        $sconti = $this->sconti;

        return $descrizioni->merge($righe)->merge($articoli)->merge($sconti)->sortBy('order');
    }

    /**
     * Restituisce la collezione di righe e articoli con valori rilevanti per i conti, raggruppate sulla base dei documenti di provenienza.
     * La chiave è la serializzazione del documento di origine, oppure null in caso non esista.
     *
     * @return iterable
     */
    public function getRigheRaggruppate()
    {
        $righe = $this->getRighe();

        $groups = $righe->groupBy(function ($item, $key) {
            if (!$item->hasOriginal()) {
                return null;
            }

            $parent = $item->getOriginal()->parent;

            return serialize($parent);
        });

        return $groups;
    }

    abstract public function righe();

    abstract public function articoli();

    abstract public function descrizioni();

    abstract public function sconti();

    /**
     * Calcola l'imponibile del documento.
     *
     * @return float
     */
    public function getImponibileAttribute()
    {
        return $this->calcola('imponibile');
    }

    /**
     * Calcola lo sconto totale del documento.
     *
     * @return float
     */
    public function getScontoAttribute()
    {
        return $this->calcola('sconto');
    }

    /**
     * Calcola il totale imponibile del documento.
     *
     * @return float
     */
    public function getTotaleImponibileAttribute()
    {
        return $this->calcola('totale_imponibile');
    }

    /**
     * Calcola l'IVA totale del documento.
     *
     * @return float
     */
    public function getIvaAttribute()
    {
        return $this->calcola('iva');
    }

    /**
     * Calcola il totale del documento.
     *
     * @return float
     */
    public function getTotaleAttribute()
    {
        return $this->calcola('totale');
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
     * Calcola il guadagno del documento.
     *
     * @return float
     */
    public function getGuadagnoAttribute()
    {
        return $this->calcola('guadagno');
    }

    public function delete()
    {
        $righe = $this->getRighe();
        foreach ($righe as $riga) {
            $riga->delete();
        }

        return parent::delete();
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
        return $this->getRighe();
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
