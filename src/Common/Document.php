<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Common;

use Common\Components\Component;
use Illuminate\Database\Eloquent\Model as Model;

abstract class Document extends Model implements ReferenceInterface, DocumentInterface
{
    /**
     * Abilita la movimentazione automatica degli Articoli, finalizzata alla gestione interna del magazzino.
     *
     * @var bool
     */
    public static $movimenta_magazzino = true;
    protected $casts = [
        'sconto_finale' => 'float',
        'sconto_finale_percentuale' => 'float',
    ];

    /**
     * Restituisce il valore della variabile statica $movimenta_magazzino per il documento.
     *
     * @return bool
     */
    public function getMovimentaMagazzinoAttribute()
    {
        return static::$movimenta_magazzino;
    }

    /**
     * Restituisce tutti i documenti collegati al documento corrente attraverso la procedura di importazione delle righe.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getDocumentiCollegati()
    {
        $riferimenti = collect([
            $this->descrizioni()
                ->select(['original_document_id', 'original_document_type'])
                ->get(),
            $this->righe()
                ->select(['original_document_id', 'original_document_type'])
                ->get(),
            $this->articoli()
                ->select(['original_document_id', 'original_document_type'])
                ->get(),
            $this->sconti()
                ->select(['original_document_id', 'original_document_type'])
                ->get(),
        ])->flatten();

        return $riferimenti->reject(function ($item) {
            return empty($item->original_document_type);
        })->unique(function ($item) {
            return $item->original_document_type.'|'.$item->original_document_id;
        })->mapToGroups(function ($item) {
            return [$item->original_document_type => ($item->original_document_type)::find($item->original_document_id)];
        });
    }

    /**
     * Restituisce tutte le righe collegate al documento.
     *
     * @return \Illuminate\Support\Collection|iterable
     */
    public function getRighe()
    {
        $results = $this->mergeCollections($this->descrizioni, $this->righe, $this->articoli, $this->sconti);

        return $results->sortBy(function ($item) {
            return [$item->order, $item->id];
        });
    }

    /**
     * Restituisce la riga con tipo e identificativo corrispondente.
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
     * Restituisce le righe del documento raggruppate per documento di origine.
     *
     * @return \Illuminate\Support\Collection|iterable
     */
    public function getRigheRaggruppate()
    {
        $righe = $this->getRighe();

        $groups = $righe->groupBy(function ($item, $key) {
            if (!$item->hasOriginalComponent()) {
                return null;
            }

            $parent = $item->getOriginalComponent()->getDocument();

            return serialize($parent);
        });

        return $groups;
    }

    abstract public function righe();

    abstract public function articoli();

    abstract public function descrizioni();

    abstract public function sconti();

    abstract public function getDirezioneAttribute();

    public function triggerEvasione(Component $trigger)
    {
        $this->setRelations([]);
    }

    public function triggerComponent(Component $trigger)
    {
        $this->setRelations([]);
    }

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
        return $this->calcola('totale_imponibile') + $this->calcola('iva') + $this->rivalsa_inps + $this->iva_rivalsa_inps;
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
        return ($this->imponibile && $this->spesa) ? (($this->totale_imponibile / $this->spesa) - 1) * 100 : 100;
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

    public function toArray()
    {
        $array = parent::toArray();

        $result = array_merge($array, [
            'spesa' => $this->spesa,
            'imponibile' => $this->imponibile,
            'sconto' => $this->sconto,
            'totale_imponibile' => $this->totale_imponibile,
            'iva' => $this->iva,
            'totale' => $this->totale,
        ]);

        return $result;
    }

    /**
     * Imposta lo sconto finale.
     *
     * @param $sconto
     * @param $tipo
     */
    public function setScontoFinale($sconto, $tipo)
    {
        if ($tipo == 'PRC') {
            $this->sconto_finale_percentuale = $sconto;
            $this->sconto_finale = 0;
        } else {
            $this->sconto_finale = $sconto;
            $this->sconto_finale_percentuale = 0;
        }
    }

    /**
     * Restituisce lo sconto finale.
     */
    public function getScontoFinale()
    {
        $netto = $this->calcola('netto');

        if (!empty($this->sconto_finale_percentuale)) {
            $sconto = $netto * ($this->sconto_finale_percentuale / 100);
        } else {
            $sconto = $this->sconto_finale;
        }

        return $sconto;
    }

    /**
     * Calcola il netto a pagare del documento.
     *
     * @return float
     */
    public function getNettoAttribute()
    {
        $netto = $this->totale - $this->ritenuta_acconto - $this->ritenuta_contributi;
        if ($this->split_payment) {
            $netto = $netto - $this->iva;
        }
        $sconto_finale = $this->getScontoFinale();

        return $netto - $sconto_finale;
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
