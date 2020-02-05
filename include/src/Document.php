<?php

namespace Common;

use Common\Components\Description;

abstract class Document extends Model
{
    /**
     * Restituisce la collezione di righe e articoli con valori rilevanti per i conti.
     *
     * @return iterable
     */
    public function getRighe()
    {
        $results = $this->mergeCollections($this->descrizioni, $this->righe, $this->articoli, $this->sconti);

        return $results->sortBy('order');
    }

    /**
     * Restituisce la riga identificata dall'ID indicato.
     *
     * @param $type
     * @param $id
     *
     * @return mixed
     */
    public function getRiga($type, $id)
    {
        $righe = $this->getRighe();

        return $righe->first(function ($item) use ($type, $id) {
            return $item instanceof $type && $item->id == $id;
        });
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
     * Calcola il margine del documento.
     *
     * @return float
     */
    public function getMargineAttribute()
    {
        return $this->calcola('margine');
    }

    /**
     * Restituisce il margine percentuale del documento.
     *
     * @return float
     */
    public function getMarginePercentualeAttribute()
    {
        return (1 - ($this->spesa / ($this->totale_imponibile))) * 100;
    }

    public function delete()
    {
        $righe = $this->getRighe();

        $can_delete = true;
        foreach ($righe as $riga) {
            $can_delete &= $riga->canDelete();
        }

        if (!$can_delete) {
            throw new \InvalidArgumentException();
        }

        foreach ($righe as $riga) {
            $riga->delete();
        }

        return parent::delete();
    }

    /**
     * Metodo richiamato a seguito di modifiche sull'evasione generale delle righe del documento.
     * Utilizzabile per limpostazione automatica degli stati.
     *
     * @param Description $trigger
     */
    public function triggerEvasione(Description $trigger)
    {
        $this->setRelations([]);
    }

    /**
     * Metodo richiamato a seguito della modifica o creazione di una riga del documento.
     * Utilizzabile per limpostazione automatica di campi statici del documento.
     *
     * @param Description $trigger
     */
    public function triggerComponent(Description $trigger)
    {
        $this->setRelations([]);
    }

    /**
     * Costruisce una nuova collezione Laravel a partire da quelle indicate.
     *
     * @param array<\Illuminate\Support\Collection> ...$args
     *
     * @return \Illuminate\Support\Collection
     */
    protected function mergeCollections(...$args)
    {
        $collection = collect($args);

        return $collection->collapse();
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
