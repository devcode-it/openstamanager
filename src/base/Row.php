<?php

namespace Base;

use Illuminate\Database\Eloquent\Builder;

abstract class Row extends Description
{
    protected static function boot($bypass = false)
    {
        parent::boot($bypass);

        if (!$bypass) {
            static::addGlobalScope('rows', function (Builder $builder) {
                $builder->whereNull('idarticolo')->where('idarticolo', '=', 0);
            });
        }
    }

    /*
    public function getPrezzoAttribute()
    {
        return $this->prezzo;
    }

    public function getSubtotaleAttribute()
    {
        return $this->prezzo * $this->qta;
    }
    */

    public function getTotaleAttribute()
    {
        return $this->subtotale + $this->iva;
    }

    public function getScontoAttribute()
    {
        return calcola_sconto([
            'sconto' => $this->sconto_unitario,
            'prezzo' => $this->prezzo ?: $this->prezzo_vendita, // Compatibilità con gli interventi
            'tipo' => $this->tipo_sconto,
            'qta' => $this->qta,
        ]);
    }

    public function setIdRivalsaINPSAttribute($value)
    {
        $this->attributes['idrivalsainps'] = $value;

        // Calcolo rivalsa inps
        $rivalsa = database()->fetchOne('SELECT * FROM co_rivalsainps WHERE id = '.prepare($value));
        $this->rivalsainps = ($this->subtotale - $this->sconto) / 100 * $rivalsa['percentuale'];
    }

    public function getCalcoloRitenutaAccontoAttribute()
    {
        return $this->calcolo_ritenutaacconto ?: 'Imponibile';
    }

    public function setCalcoloRitenutaAccontoAttribute($value)
    {
        return $this->attributes['calcolo_ritenutaacconto'] = $value;
    }

    public function setIdRitenutaAccontoAttribute($value)
    {
        $this->attributes['idritenutaacconto'] = $value;

        // Calcolo ritenuta d'acconto
        $ritenuta = database()->fetchOne('SELECT * FROM co_ritenutaacconto WHERE id = '.prepare($value));
        $conto = ($this->subtotale - $this->sconto);

        if ($this->calcolo_ritenutaacconto == 'Imponibile + rivalsa inps') {
            $conto += $this->rivalsainps;
        }

        $this->ritenutaacconto = $conto / 100 * $ritenuta['percentuale'];
    }

    /* Retrocompatibilità */
    public function setScontoUnitarioAttribute($value)
    {
        $this->attributes['sconto_unitario'] = $value;

        $this->fixSconto();
    }

    public function setTipoScontoAttribute($value)
    {
        $this->attributes['tipo_sconto'] = $value;

        $this->fixSconto();
    }

    protected function fixSconto()
    {
        $this->attributes['sconto'] = $this->sconto;
    }

    protected function fixIva()
    {
        $iva = database()->fetchOne('SELECT * FROM co_iva WHERE id = :id_iva', [
            ':id_iva' => $this->idiva,
        ]);
        $descrizione = $iva['descrizione'];

        $valore = ($this->subtotale - $this->sconto) * $iva['percentuale'] / 100;

        $this->desc_iva = $descrizione;
        $this->iva = $valore;

        // Compatibilità con gli interventi
        if (!isset($this->prezzo_vendita)) {
            $this->iva_indetraibile = $valore / 100 * $iva['indetraibile'];
        }

        $this->attributes['sconto'] = $this->sconto;
    }

    public function setIdIvaAttribute($value)
    {
        $this->attributes['idiva'] = $value;

        $this->fixIva();
    }

    public function getPrezzoAttribute()
    {
        return $this->subtotale / $this->qta;
    }

    public function setSubtotale($prezzo, $qta)
    {
        $this->qta = $qta;

        $this->subtotale = $prezzo * $qta;

        $this->fixIva();
    }
}
