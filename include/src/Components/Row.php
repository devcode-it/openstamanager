<?php

namespace Common\Components;

use Common\Document;
use Illuminate\Database\Eloquent\Builder;
use Modules\Iva\Aliquota;

abstract class Row extends Description
{
    protected $casts = [
        'qta' => 'float',
        //'qta_evasa' => 'float',
    ];

    public static function build(Document $document, $bypass = false)
    {
        return parent::build($document, true);
    }

    // Attributi di contabilità

    /**
     * Restituisce l'imponibile dell'elemento.
     *
     * @return float
     */
    public function getImponibileAttribute()
    {
        return $this->prezzo_unitario * $this->qta;
    }

    /**
     * Restituisce il totale imponibile dell'elemento.
     *
     * @return float
     */
    public function getTotaleImponibileAttribute()
    {
        $result = $this->prezzo_unitario >= 0 ? $this->imponibile : -$this->imponibile;

        $result -= $this->sconto;

        return $this->prezzo_unitario >= 0 ? $result : -$result;
    }

    /**
     * Restituisce il totale (imponibile + iva) dell'elemento.
     *
     * @return float
     */
    public function getTotaleAttribute()
    {
        return $this->totale_imponibile + $this->iva;
    }

    /**
     * Restituisce la spesa (costo_unitario * qta) relativa all'elemento.
     *
     * @return float
     */
    public function getSpesaAttribute()
    {
        return $this->costo_unitario * $this->qta;
    }

    /**
     * Restituisce il margine totale (imponibile - spesa) relativo all'elemento.
     *
     * @return float
     */
    public function getMargineAttribute()
    {
        return $this->totale_imponibile - $this->spesa;
    }

    /**
     * Restituisce il margine percentuale relativo all'elemento.
     *
     * @return float
     */
    public function getMarginePercentualeAttribute()
    {
        return (1 - ($this->spesa / $this->imponibile)) * 100;
    }

    // Attributi della componente

    public function getIvaIndetraibileAttribute()
    {
        return $this->iva / 100 * $this->aliquota->indetraibile;
    }

    public function getIvaAttribute()
    {
        return ($this->totale_imponibile) * $this->aliquota->percentuale / 100;
    }

    public function getIvaDetraibileAttribute()
    {
        return $this->iva - $this->iva_indetraibile;
    }

    public function getSubtotaleAttribute()
    {
        return $this->imponibile;
    }

    /**
     * Restituisce lo sconto della riga corrente in euro.
     *
     * @return float
     */
    public function getScontoAttribute()
    {
        return calcola_sconto([
            'sconto' => $this->sconto_unitario,
            'prezzo' => $this->prezzo_unitario,
            'tipo' => $this->tipo_sconto,
            'qta' => $this->qta,
        ]);
    }

    /**
     * Imposta l'identificatore dell'IVA.
     *
     * @param int $value
     */
    public function setIdIvaAttribute($value)
    {
        $this->attributes['idiva'] = $value;
        $this->load('aliquota');
    }

    /**
     * Imposta il prezzo unitario per la riga.
     *
     * @param int $value
     */
    public function setPrezzoUnitarioAttribute($value)
    {
        $percentuale_iva = floatval($this->aliquota->percentuale) / 100;

        // Gestione IVA incorporata
        if ($this->parent->direzione == 'entrata' && setting('Utilizza prezzi di vendita con IVA incorporata')) {
            $this->attributes['prezzo_unitario_ivato'] = $value;

            $this->attributes['iva_unitaria'] = $value * $percentuale_iva / (1 + $percentuale_iva); // Calcolo IVA
            $this->attributes['prezzo_unitario'] = $value - $this->attributes['iva_unitaria'];
        } else {
            $this->attributes['prezzo_unitario'] = $value;

            $this->attributes['iva_unitaria'] = $value * $percentuale_iva; // Calcolo IVA
            $this->attributes['prezzo_unitario_ivato'] = $value + $this->attributes['iva_unitaria'];
        }
    }

    public function setSconto($value, $type)
    {
        $percentuale_iva = floatval($this->aliquota->percentuale) / 100;

        if ($type == 'PRC') {
            $this->attributes['sconto_percentuale'] = $value;

            $sconto = calcola_sconto([
                'sconto' => $this->sconto_unitario,
                'prezzo' => $this->prezzo_unitario,
                'tipo' => 'PRC',
                'qta' => 1,
            ]);
        } else {
            $this->attributes['sconto_percentuale'] = 0;
            $sconto = $value;
        }

        // Gestione IVA incorporata
        if ($this->parent->direzione == 'entrata' && setting('Utilizza prezzi di vendita con IVA incorporata')) {
            $this->attributes['sconto_unitario_ivato'] = $sconto;

            $this->attributes['sconto_iva'] = $sconto * $percentuale_iva / (1 + $percentuale_iva); // Calcolo IVA
            $this->attributes['sconto_unitario'] = $sconto - $this->attributes['sconto_iva'];
        } else {
            $this->attributes['sconto_unitario'] = $sconto;

            $this->attributes['sconto_iva'] = $sconto * $percentuale_iva; // Calcolo IVA
            $this->attributes['sconto_unitario_ivato'] = $sconto + $this->attributes['sconto_iva'];
        }
    }

    /**
     * Salva la riga, impostando i campi dipendenti dai singoli parametri.
     *
     * @return bool
     */
    public function save(array $options = [])
    {
        // Fix dei campi statici
        $this->fixSubtotale();
        $this->fixSconto();

        $this->fixIva();

        return parent::save($options);
    }

    public function aliquota()
    {
        return $this->belongsTo(Aliquota::class, 'idiva');
    }

    protected static function boot($bypass = false)
    {
        parent::boot(true);

        $table = parent::getTableName();

        if (!$bypass) {
            static::addGlobalScope('rows', function (Builder $builder) use ($table) {
                $builder->whereNull($table.'.idarticolo')->orWhere($table.'.idarticolo', '=', 0);
            });

            static::addGlobalScope('not_discounts', function (Builder $builder) use ($table) {
                $builder->where($table.'.is_sconto', '=', 0);
            });
        }
    }

    /**
     * Effettua i conti per il subtotale della riga.
     */
    protected function fixSubtotale()
    {
        $this->attributes['subtotale'] = $this->imponibile;
    }

    /**
     * Effettua i conti per l'IVA.
     */
    protected function fixIva()
    {
        $this->attributes['iva'] = $this->iva;

        $descrizione = $this->aliquota->descrizione;
        if (!empty($descrizione)) {
            $this->attributes['desc_iva'] = $descrizione;
        }

        $this->fixIvaIndetraibile();
    }

    /**
     * Effettua i conti per l'IVA indetraibile.
     */
    protected function fixIvaIndetraibile()
    {
        $this->attributes['iva_indetraibile'] = $this->iva_indetraibile;
    }

    /**
     * Effettua i conti per lo sconto totale.
     */
    protected function fixSconto()
    {
        $this->attributes['sconto'] = $this->sconto;
    }

    /**
     * Azione personalizzata per la copia dell'oggetto (dopo la copia).
     *
     * @param $original
     */
    protected function customAfterDataCopiaIn($original)
    {
        $this->prezzo_unitario = $original->prezzo_unitario;

        parent::customAfterDataCopiaIn($original);
    }
}
