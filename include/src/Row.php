<?php

namespace Common;

use Illuminate\Database\Eloquent\Builder;

abstract class Row extends Description
{
    protected $prezzo_unitario_vendita;

    protected static function boot($bypass = false)
    {
        parent::boot(true);

        if (!$bypass) {
            static::addGlobalScope('rows', function (Builder $builder) {
                $builder->whereNull('idarticolo')->orWhere('idarticolo', '=', 0);
            });
        }
    }

    public static function make($bypass = false)
    {
        return parent::make(true);
    }

    public function getTotaleAttribute()
    {
        return $this->subtotale + $this->iva;
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
     * Imposta l'identificatore della Rivalsa INPS, effettuando di conseguenza i conti.
     *
     * @param int $value
     */
    public function setIdRivalsaINPSAttribute($value)
    {
        $this->attributes['idrivalsainps'] = $value;

        $this->fixRivalsaINPS();
    }

    /**
     * Effettua i conti per la Rivalsa INPS.
     */
    protected function fixRivalsaINPS()
    {
        $rivalsa = database()->fetchOne('SELECT * FROM co_rivalsainps WHERE id = '.prepare($this->idrivalsainps));
        $this->attributes['rivalsainps'] = ($this->subtotale - $this->sconto) / 100 * $rivalsa['percentuale'];
    }

    /**
     * Restituisce il metodo di calcolo per la Ritenuta d'Acconto.
     *
     * @return string
     */
    public function getCalcoloRitenutaAccontoAttribute()
    {
        return $this->calcolo_ritenutaacconto ?: setting("Metodologia calcolo ritenuta d'acconto predefinito");
    }

    /**
     * Imposta il metodo di calcolo per la Ritenuta d'Acconto.
     *
     * @param string $value
     */
    public function setCalcoloRitenutaAccontoAttribute($value)
    {
        return $this->attributes['calcolo_ritenutaacconto'] = $value;

        $this->fixRitenutaAcconto();
    }

    /**
     * Imposta l'identificatore della Ritenuta d'Acconto, effettuando di conseguenza i conti in base al valore del campo calcolo_ritenuta_acconto.
     *
     * @param int $value
     */
    public function setIdRitenutaAccontoAttribute($value)
    {
        $this->attributes['idritenutaacconto'] = $value;

        $this->fixRitenutaAcconto();
    }

    /**
     * Effettua i conti per la Ritenuta d'Acconto.
     */
    protected function fixRitenutaAcconto()
    {
        // Calcolo ritenuta d'acconto
        $ritenuta = database()->fetchOne('SELECT * FROM co_ritenutaacconto WHERE id = '.prepare($this->idritenutaacconto));
        $conto = ($this->subtotale - $this->sconto);

        if ($this->calcolo_ritenuta_acconto == 'Imponibile + rivalsa inps') {
            $conto += $this->rivalsainps;
        }

        $this->attributes['ritenutaacconto'] = $conto / 100 * $ritenuta['percentuale'];
    }

    /**
     * Imposta il valore dello sconto.
     *
     * @param float $value
     */
    public function setScontoUnitarioAttribute($value)
    {
        $this->attributes['sconto_unitario'] = $value;

        $this->fixSconto();
    }

    /**
     * Imposta il tipo dello sconto.
     *
     * @param string $value
     */
    public function setTipoScontoAttribute($value)
    {
        $this->attributes['tipo_sconto'] = $value;

        $this->fixSconto();
    }

    /**
     * Effettua i conti per lo sconto totale.
     */
    protected function fixSconto()
    {
        $this->attributes['sconto'] = $this->sconto;

        $this->fixIva();
    }

    /**
     * Imposta l'identificatore dell'IVA, effettuando di conseguenza i conti.
     *
     * @param int $value
     */
    public function setIdIvaAttribute($value)
    {
        $this->attributes['idiva'] = $value;

        $this->fixIva();
    }

    /**
     * Effettua i conti per l'IVA.
     */
    protected function fixIva()
    {
        $iva = database()->fetchOne('SELECT * FROM co_iva WHERE id = :id_iva', [
            ':id_iva' => $this->idiva,
        ]);
        $descrizione = $iva['descrizione'];

        $valore = ($this->subtotale - $this->sconto) * $iva['percentuale'] / 100;

        $this->attributes['desc_iva'] = $descrizione;
        $this->attributes['iva'] = $valore;

        $this->fixIvaIndetraibile();
    }

    /**
     * Effettua i conti per l'IVA indetraibile.
     */
    protected function fixIvaIndetraibile()
    {
        $iva = database()->fetchOne('SELECT * FROM co_iva WHERE id = :id_iva', [
            ':id_iva' => $this->idiva,
        ]);

        $this->attributes['iva_indetraibile'] = $this->iva / 100 * $iva['indetraibile'];
    }

    /**
     * Imposta la quantità della riga.
     *
     * @param float $value
     */
    public function setQtaAttribute($value)
    {
        $this->attributes['qta'] = $value;

        $this->fixSubtotale();
        $this->fixSconto();
    }

    /**
     * Imposta il costo unitario della riga.
     *
     * @param float $value
     */
    public function setPrezzoUnitarioVenditaAttribute($value)
    {
        $this->prezzo_unitario_vendita = $value;

        $this->fixSubtotale();
        $this->fixSconto();
    }

    /**
     * Restituisce il costo unitario della riga.
     */
    public function getPrezzoUnitarioVenditaAttribute()
    {
        if (empty($this->prezzo_unitario_vendita)) {
            $this->prezzo_unitario_vendita = $this->subtotale / $this->qta;
        }

        return $this->prezzo_unitario_vendita;
    }

    /**
     * Effettua i conti per il subtotale della riga.
     */
    protected function fixSubtotale()
    {
        $this->attributes['subtotale'] = $this->prezzo_unitario_vendita * $this->qta;

        $this->fixIva();
        $this->fixRitenutaAcconto();
        $this->fixRivalsaINPS();
    }
}
