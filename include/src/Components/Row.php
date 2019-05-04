<?php

namespace Common\Components;

use Common\Document;
use Illuminate\Database\Eloquent\Builder;
use Modules\Iva\Aliquota;

abstract class Row extends Description
{
    protected $prezzo_unitario_vendita_riga = null;

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
        return $this->prezzo_unitario_vendita * $this->qta;
    }

    /**
     * Restituisce l'imponibile scontato dell'elemento.
     *
     * @return float
     */
    public function getImponibileScontatoAttribute()
    {
        $result = $this->prezzo_unitario_vendita >= 0 ? $this->imponibile : -$this->imponibile;

        $result -= $this->sconto;

        return $this->prezzo_unitario_vendita >= 0 ? $result : -$result;
    }

    /**
     * Restituisce il totale (imponibile + iva) dell'elemento.
     *
     * @return float
     */
    public function getTotaleAttribute()
    {
        return $this->imponibile_scontato + $this->iva;
    }

    /**
     * Restituisce la spesa (prezzo_unitario_acquisto * qta) relativa all'elemento.
     *
     * @return float
     */
    public function getSpesaAttribute()
    {
        return $this->prezzo_unitario_acquisto * $this->qta;
    }

    /**
     * Restituisce il gaudagno totale (imponibile_scontato - spesa) relativo all'elemento.
     *
     * @return float
     */
    public function getGuadagnoAttribute()
    {
        return $this->imponibile_scontato - $this->spesa;
    }

    // Attributi della componente

    public function getIvaIndetraibileAttribute()
    {
        return $this->iva / 100 * $this->aliquota->indetraibile;
    }

    public function getIvaAttribute()
    {
        return ($this->imponibile_scontato) * $this->aliquota->percentuale / 100;
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
            'prezzo' => $this->prezzo_unitario_vendita,
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
     * Imposta il costo unitario della riga.
     *
     * @param float $value
     */
    public function setPrezzoUnitarioVenditaAttribute($value)
    {
        $this->prezzo_unitario_vendita_riga = $value;
    }

    /**
     * Restituisce il costo unitario della riga.
     */
    public function getPrezzoUnitarioVenditaAttribute()
    {
        if (!isset($this->prezzo_unitario_vendita_riga)) {
            $this->prezzo_unitario_vendita_riga = $this->attributes['subtotale'] / $this->qta;
        }

        return !is_nan($this->prezzo_unitario_vendita_riga) ? $this->prezzo_unitario_vendita_riga : 0;
    }

    /**
     * Salva la riga, impostando i campi dipendenti dai singoli parametri.
     *
     * @param array $options
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
        $this->prezzo_unitario_vendita = $original->prezzo_unitario_vendita;

        parent::customAfterDataCopiaIn($original);
    }
}
