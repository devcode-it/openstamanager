<?php

namespace Common\Components;

use Common\Document;
use Illuminate\Database\Eloquent\Builder;
use Modules\Iva\Aliquota;
use Modules\Ritenute\RitenutaAcconto;
use Modules\Ritenute\RivalsaINPS;

abstract class Row extends Description
{
    protected $prezzo_unitario_vendita_riga = null;

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
        return $this->imponibile - $this->sconto;
    }

    /**
     * Restituisce il totale (imponibile + iva + rivalsa_inps + iva_rivalsainps) dell'elemento.
     *
     * @return float
     */
    public function getTotaleAttribute()
    {
        return $this->imponibile_scontato + $this->iva + $this->rivalsa_inps + $this->iva_rivalsa_inps;
    }

    /**
     * Restituisce il netto a pagare (totale - ritenuta_acconto) dell'elemento.
     *
     * @return float
     */
    public function getNettoAttribute()
    {
        return $this->totale - $this->ritenuta_acconto;
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

    public function getRivalsaINPSAttribute()
    {
        return $this->imponibile_scontato / 100 * $this->rivalsa->percentuale;
    }

    public function getIvaRivalsaINPSAttribute()
    {
        return $this->rivalsa_inps / 100 * $this->aliquota->percentuale;
    }

    public function getRitenutaAccontoAttribute()
    {
        $result = $this->imponibile_scontato;

        if ($this->calcolo_ritenuta_acconto == 'IMP+RIV') {
            $result += $this->rivalsainps;
        }

        return $result / 100 * $this->ritenuta->percentuale;
    }

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
     * Imposta l'identificatore della Rivalsa INPS.
     *
     * @param int $value
     */
    public function setIdRivalsaINPSAttribute($value)
    {
        $this->attributes['idrivalsainps'] = $value;
        $this->load('rivalsa');
    }

    /**
     * Imposta l'identificatore della Ritenuta d'Acconto.
     *
     * @param int $value
     */
    public function setIdRitenutaAccontoAttribute($value)
    {
        $this->attributes['idritenutaacconto'] = $value;
        $this->load('ritenuta');
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

        return $this->prezzo_unitario_vendita_riga;
    }

    /**
     * Save the model to the database.
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
        $this->fixRitenutaAcconto();
        $this->fixRivalsaINPS();

        return parent::save($options);
    }

    public function aliquota()
    {
        return $this->belongsTo(Aliquota::class, 'idiva');
    }

    public function rivalsa()
    {
        return $this->belongsTo(RivalsaINPS::class, 'idrivalsainps');
    }

    public function ritenuta()
    {
        return $this->belongsTo(RitenutaAcconto::class, 'idritenutaacconto');
    }

    protected static function boot($bypass = false)
    {
        parent::boot(true);

        if (!$bypass) {
            static::addGlobalScope('rows', function (Builder $builder) {
                $builder->whereNull('idarticolo')->orWhere('idarticolo', '=', 0);
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
     * Effettua i conti per la Rivalsa INPS.
     */
    protected function fixRivalsaINPS()
    {
        $this->attributes['rivalsainps'] = $this->rivalsa_inps;
    }

    /**
     * Effettua i conti per la Ritenuta d'Acconto, basandosi sul valore del campo calcolo_ritenuta_acconto.
     */
    protected function fixRitenutaAcconto()
    {
        $this->attributes['ritenutaacconto'] = $this->ritenuta_acconto;
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
}
