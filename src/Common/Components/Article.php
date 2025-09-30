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

namespace Common\Components;

use Common\Document;
use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Builder;
use Modules\Articoli\Articolo as Original;
use Modules\Articoli\Movimento;
use Plugins\ListinoFornitori\DettaglioFornitore;

abstract class Article extends Accounting
{
    use SimpleModelTrait;

    protected $abilita_movimentazione = true;

    protected $serialRowID;
    protected $serialsList;

    protected $qta_movimentazione = 0;

    public static function build(?Document $document = null, ?Original $articolo = null)
    {
        $model = new static();
        $model->setDocument($document);

        $model->articolo()->associate($articolo);

        $model->descrizione = $articolo->getTranslation('title');
        $model->abilita_serial = $articolo->abilita_serial;
        $model->um = $articolo->um;

        if (empty($model->idiva)) {
            $default_iva = setting('Iva predefinita');

            $model->idiva = $default_iva;
        }

        return $model;
    }

    public function isDescrizione()
    {
        return false;
    }

    public function isSconto()
    {
        return false;
    }

    public function isRiga()
    {
        return false;
    }

    public function isArticolo()
    {
        return true;
    }

    /**
     * Metodo dedicato a gestire in automatico la movimentazione del magazzino in relazione all'articolo di riferimento sulla base delle caratteristiche del movimento (magazzino abilitato o meno).
     */
    public function movimenta($qta)
    {
        if (!$this->getDocument()->movimenta_magazzino) {
            return;
        }

        $movimenta = true;

        // Movimenta il magazzino solo se l'articolo non è già stato movimentato da un documento precedente
        if ($this->hasOriginalComponent()) {
            $original = $this->getOriginalComponent();
            $movimenta = !$original->getDocument()->movimenta_magazzino;
        }

        if ($movimenta) {
            $this->movimentaMagazzino($qta);
        }
    }

    public function getDirection()
    {
        return $this->getDocument()->direzione;
    }

    /**
     * Restituisce il codice impostato per l'articolo corrente.
     */
    public function getCodiceAttribute()
    {
        return $this->dettaglioFornitore->codice_fornitore ?: $this->articolo->codice;
    }

    /**
     * Imposta i seriali collegati all'articolo.
     *
     * @param array $serials
     */
    public function setSerialsAttribute($serials)
    {
        if (!$this->useSerials()) {
            return;
        }

        // Inversione di movimento nei seriali in caso di nota di credito
        $dir = $this->getDirection();

        $document = $this->getDocument();

        if ($document instanceof \Modules\Fatture\Fattura) {
            if ($document->isNota()) {
                if ($this->getDirection() == 'uscita') {
                    $dir = 'entrata';
                } else {
                    $dir = 'uscita';
                }
            }
        }

        $serials = array_clean($serials);

        database()->sync('mg_prodotti', [
            'id_riga_'.$this->serialRowID => $this->id,
            'dir' => $dir,
            'id_articolo' => $this->idarticolo,
        ], [
            'serial' => $serials,
        ]);

        $this->serialsList = $serials;
    }

    /**
     * Rimuove i seriali collegati all'articolo.
     *
     * @param array $serials
     */
    public function removeSerials($serials)
    {
        if (!$this->useSerials()) {
            return;
        }

        database()->detach('mg_prodotti', [
            'id_riga_'.$this->serialRowID => $this->id,
            'dir' => $this->getDirection(),
            'id_articolo' => $this->idarticolo,
        ], [
            'serial' => array_clean($serials),
        ]);

        $this->serialsList = null;
    }

    /**
     * Restituisce l'elenco dei seriali collegati all'articolo del documento.
     *
     * @return array
     */
    public function getSerialsAttribute()
    {
        if (!$this->useSerials()) {
            return [];
        }

        if (!isset($this->serialsList)) {
            // Individuazione dei seriali
            $results = database()->fetchArray('SELECT serial FROM mg_prodotti WHERE serial IS NOT NULL AND id_riga_'.$this->serialRowID.' = '.prepare($this->id));

            $this->serialsList = array_column($results, 'serial');
        }

        return $this->serialsList;
    }

    /**
     * Restituisce il numero di seriali mancanti per il completamento dell'articolo.
     *
     * @return float
     */
    public function getMissingSerialsNumberAttribute()
    {
        if (!$this->useSerials()) {
            return 0;
        }

        $missing = $this->qta - count($this->serials);

        return $missing;
    }

    /**
     * Modifica la quantità dell'articolo e movimenta automaticamente il magazzino.
     *
     * @param float $value
     */
    public function setQtaAttribute($value)
    {
        if (!$this->cleanupSerials($value)) {
            throw new \UnexpectedValueException();
        }

        $diff = parent::setQtaAttribute($value);

        if ($this->abilita_movimentazione) {
            $this->qta_movimentazione += $diff;
        }
    }

    public function articolo()
    {
        return $this->belongsTo(Original::class, 'idarticolo')
            ->withTrashed();
    }

    public function dettaglioFornitore()
    {
        return $this->belongsTo(DettaglioFornitore::class, 'id_dettaglio_fornitore')->withTrashed();
    }

    public function movimentazione($value = true)
    {
        $this->abilita_movimentazione = $value;
    }

    /**
     * Salva l'articolo, eventualmente movimentandone il magazzino.
     *
     * @return bool
     */
    public function save(array $options = [])
    {
        if (!empty($this->qta_movimentazione)) {
            $this->movimenta($this->qta_movimentazione);
            $this->qta_movimentazione = 0;
        }

        return parent::save($options);
    }

    public function canDelete()
    {
        $serials = $this->usedSerials();

        return empty($serials);
    }

    public function delete()
    {
        if (!$this->canDelete()) {
            throw new \InvalidArgumentException();
        }

        $this->serials = [];

        $this->qta = 0; // Fix movimentazione automatica
        if (!empty($this->qta_movimentazione)) {
            $this->movimenta($this->qta_movimentazione);
        }

        return parent::delete();
    }

    protected function useSerials()
    {
        return !empty($this->abilita_serial) && !empty($this->serialRowID);
    }

    protected function movimentaMagazzino($qta)
    {
        $documento = $this->getDocument();
        $data = $documento->getReferenceDate();

        $qta_movimento = $documento->direzione == 'uscita' ? $qta : -$qta;
        $movimento = Movimento::descrizioneMovimento($qta_movimento, $documento->direzione).' - '.$documento->getReference();

        // Gestione della sede: priorità alla sede esplicita, poi quella del documento
        if (isset($this->qta_movimentazione_sede)) {
            $id_sede = $this->qta_movimentazione_sede;
        } elseif ($documento instanceof \Modules\Interventi\Intervento) {
            $id_sede = $documento->idsede_partenza;
        } else {
            $id_sede = $documento->direzione == 'uscita' ? $documento->idsede_destinazione : $documento->idsede_partenza;
        }

        // Fix per valori di sede a NULL
        $id_sede = $id_sede ?: 0;
        $qta_finale = $qta_movimento;

        if (!setting('Permetti selezione articoli con quantità minore o uguale a zero in Documenti di Vendita') && $documento->direzione == 'entrata' && !$this->articolo->servizio) {
            $qta_sede = Movimento::where('idarticolo', $this->articolo->id)
                ->where('idsede', $id_sede)
                ->groupBy('idarticolo')
                ->sum('qta');

            $qta_modifica = $this->attributes['qta'] - $this->original['qta'];

            // Se la quantità supera la giacenza in sede allora movimento solo quello che resta
            if (($qta_sede + $qta_finale) < 0 && $qta_sede >= 0) {
                $qta_finale = -$qta_sede;
                $this->attributes['qta'] = $qta_sede + ($qta_modifica != 0 ? $this->original['qta'] : 0);
            }

            // Se la quantità sede per qualche motivo è negativa correggo la quantità della riga con la differenza
            elseif ($qta_sede < 0 && $this->original['qta'] >= abs($qta_sede)) {
                $qta_finale = abs($qta_sede);
                $this->attributes['qta'] = $this->original['qta'] - abs($qta_sede);
            }

            // Se la quantità sede per qualche motivo è negativa e supera la quantità della riga azzero quest'ultima
            elseif ($qta_sede < 0 && $this->original['qta'] < abs($qta_sede)) {
                $qta_finale = $this->original['qta'];
                $this->attributes['qta'] = 0;
            }
        }

        $this->articolo->movimenta($qta_finale, $movimento, $data, false, [
            'reference_type' => $documento::class,
            'reference_id' => $documento->id,
            'idsede' => $id_sede,
        ]);
    }

    protected static function boot()
    {
        parent::boot();

        // Pre-caricamento Articolo
        static::addGlobalScope('articolo', function (Builder $builder) {
            $builder->with('articolo', 'dettaglioFornitore');
        });

        $table = static::getTableName();
        static::addGlobalScope('articles', function (Builder $builder) use ($table) {
            $builder->whereNotNull($table.'.idarticolo')->where($table.'.idarticolo', '<>', 0);
        });
    }

    /**
     * Restituisce l'elenco dei seriali collegati e utilizzati da altri documenti.
     *
     * @return array
     */
    protected function usedSerials()
    {
        if (!$this->useSerials()) {
            return [];
        }
        if ($this->getDirection() == 'uscita') {
            $results = database()->fetchArray("SELECT serial FROM mg_prodotti WHERE serial IN (SELECT DISTINCT serial FROM mg_prodotti WHERE dir = 'entrata') AND serial IS NOT NULL AND id_riga_".$this->serialRowID.' = '.prepare($this->id));

            return array_column($results, 'serial');
        }

        return [];
    }

    /**
     * Pulisce i seriali non utilizzati nel caso di riduzione della quantità, se possibile.
     *
     * @return bool
     */
    protected function cleanupSerials($new_qta)
    {
        if (!$this->useSerials()) {
            return true;
        }

        // Se la nuova quantità è minore della precedente
        if ($this->qta > $new_qta) {
            $seriali_usati = $this->usedSerials();
            $count_seriali_usati = count($seriali_usati);

            // Controllo sulla possibilità di rimuovere i seriali (se non utilizzati da documenti di vendita)
            if ($this->getDirection() == 'uscita' && $new_qta < $count_seriali_usati) {
                return false;
            } else {
                // Controllo sul numero di seriali effettivi da rimuovere
                $seriali = $this->serials;

                if ($new_qta < count($seriali)) {
                    $rimovibili = array_diff($seriali, $seriali_usati);

                    // Rimozione dei seriali aggiuntivi
                    $serials = array_slice($rimovibili, 0, $new_qta - $count_seriali_usati);

                    $this->serials = array_merge($seriali_usati, $serials);
                }
            }
        }

        return true;
    }

    /**
     * Azione personalizzata per la copia dell'oggetto (dopo la copia).
     *
     * Forza il salvataggio del prezzo_unitario, per rendere compatibile il sistema con gli Interventi.
     */
    protected function customAfterDataCopiaIn($original)
    {
        $this->prezzo_unitario = $original->prezzo_unitario;
        $this->sconto_unitario = $original->sconto_unitario;

        parent::customAfterDataCopiaIn($original);
    }

    /**
     * Azione personalizzata per la copia dell'oggetto (inizializzazione della copia).
     */
    protected function customInitCopiaIn($original)
    {
        $this->articolo()->associate($original->articolo);
    }
}
