<?php

namespace Common\Components;

use Common\Document;
use Illuminate\Database\Eloquent\Builder;
use Modules\Articoli\Articolo as Original;
use UnexpectedValueException;

abstract class Article extends Row
{
    protected $serialRowID = null;
    protected $abilita_movimentazione = true;

    protected $qta_movimentazione = 0;

    public static function build(Document $document, Original $articolo)
    {
        $model = parent::build($document, true);

        $model->articolo()->associate($articolo);

        $model->descrizione = $articolo->descrizione;
        $model->abilita_serial = $articolo->abilita_serial;
        $model->um = $articolo->um;

        return $model;
    }

    abstract public function movimenta($qta);

    abstract public function getDirection();

    /**
     * Imposta i seriali collegati all'articolo.
     *
     * @param array $serials
     */
    public function setSerialsAttribute($serials)
    {
        database()->sync('mg_prodotti', [
            'id_riga_'.$this->serialRowID => $this->id,
            'dir' => $this->getDirection(),
            'id_articolo' => $this->idarticolo,
        ], [
            'serial' => array_clean($serials),
        ]);
    }

    /**
     * Rimuove i seriali collegati all'articolo.
     *
     * @param array $serials
     */
    public function removeSerials($serials)
    {
        database()->detach('mg_prodotti', [
            'id_riga_'.$this->serialRowID => $this->id,
            'dir' => $this->getDirection(),
            'id_articolo' => $this->idarticolo,
        ], [
            'serial' => array_clean($serials),
        ]);
    }

    /**
     * Restituisce l'elenco dei seriali collegati all'articolo del documento.
     *
     * @return array
     */
    public function getSerialsAttribute()
    {
        if (empty($this->abilita_serial)) {
            return [];
        }

        // Individuazione dei seriali
        $results = database()->fetchArray('SELECT serial FROM mg_prodotti WHERE serial IS NOT NULL AND id_riga_'.$this->serialRowID.' = '.prepare($this->id));

        return array_column($results, 'serial');
    }

    /**
     * Modifica la quantità dell'articolo e movimenta automaticamente il magazzino.
     *
     * @param float $value
     */
    public function setQtaAttribute($value)
    {
        if (!$this->cleanupSerials($value)) {
            throw new UnexpectedValueException();
        }

        $previous = $this->qta;
        $diff = $value - $previous;

        $this->attributes['qta'] = $value;

        if ($this->abilita_movimentazione) {
            $this->qta_movimentazione += $diff;
        }

        $database = database();

        // Se c'è un collegamento ad un ddt, aggiorno la quantità evasa
        if (!empty($this->idddt)) {
            $database->query('UPDATE dt_righe_ddt SET qta_evasa = qta_evasa + '.$diff.' WHERE descrizione = '.prepare($this->descrizione).' AND idarticolo = '.prepare($this->idarticolo).' AND idddt = '.prepare($this->idddt).' AND idiva = '.prepare($this->idiva));
        }

        // Se c'è un collegamento ad un ordine, aggiorno la quantità evasa
        if (!empty($this->idordine)) {
            $database->query('UPDATE or_righe_ordini SET qta_evasa = qta_evasa + '.$diff.' WHERE descrizione = '.prepare($this->descrizione).' AND idarticolo = '.prepare($this->idarticolo).' AND idordine = '.prepare($this->idordine).' AND idiva = '.prepare($this->idiva));
        }
    }

    public function articolo()
    {
        return $this->belongsTo(Original::class, 'idarticolo');
    }

    public function movimentazione($value = true)
    {
        $this->abilita_movimentazione = $value;
    }

    /**
     * Salva l'articolo, eventualmente movimentandone il magazzino.
     *
     * @param array $options
     *
     * @return bool
     */
    public function save(array $options = [])
    {
        if (!empty($this->qta_movimentazione)) {
            $this->movimenta($this->qta_movimentazione);
        }

        return parent::save($options);
    }

    protected static function boot()
    {
        parent::boot(true);

        static::addGlobalScope('articles', function (Builder $builder) {
            $builder->whereNotNull('idarticolo')->where('idarticolo', '<>', 0);
        });
    }

    protected function usedSerials()
    {
        if ($this->getDirection() == 'uscita') {
            $results = database()->fetchArray("SELECT serial FROM mg_prodotti WHERE serial IN (SELECT DISTINCT serial FROM mg_prodotti WHERE dir = 'entrata') AND serial IS NOT NULL AND id_riga_".$this->serialRowID.' = '.prepare($this->id));

            return array_column($results, 'serial');
        }

        return [];
    }

    protected function cleanupSerials($new_qta)
    {
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

    protected function customInitCopiaIn($original)
    {
        $this->articolo()->associate($original->articolo);
    }

    protected function customBeforeDataCopiaIn($original)
    {
        $this->movimentazione(false);

        parent::customBeforeDataCopiaIn($original);
    }

    protected function customAfterDataCopiaIn($original)
    {
        $this->movimentazione(true);

        parent::customAfterDataCopiaIn($original);
    }
}
