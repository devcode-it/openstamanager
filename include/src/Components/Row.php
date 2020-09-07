<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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
use Illuminate\Database\Eloquent\Builder;
use Modules\Iva\Aliquota;

abstract class Row extends Description
{
    protected $casts = [
        'qta' => 'float',
        'prezzo_unitario' => 'float',
        'prezzo_unitario_ivato' => 'float',
        'iva_unitaria' => 'float',
        'sconto_percentuale' => 'float',
        'sconto_unitario' => 'float',
        'sconto_iva_unitario' => 'float',
        'sconto_unitario_ivato' => 'float',
        //'qta_evasa' => 'float',
    ];

    protected $appends = [
        'prezzo_unitario_corrente',
        'sconto_unitario_corrente',
        'max_qta',
    ];

    public static function build(Document $document, $bypass = false)
    {
        return parent::build($document, true);
    }

    // Attributi di contabilità

    /**
     * Restituisce l'imponibile dell'elemento (prezzo unitario senza IVA * qta).
     *
     * @return float
     */
    public function getImponibileAttribute()
    {
        return $this->prezzo_unitario * $this->qta;
    }

    /**
     * Restituisce il totale imponibile dell'elemento (imponibile - sconto unitario senza IVA).
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
        return ($this->prezzo_unitario_ivato - $this->sconto_unitario_ivato) * $this->qta;
    }

    /**
     * Restituisce l'importo (unitario oppure unitario ivato a seconda dell'impostazione 'Utilizza prezzi di vendita con IVA incorporata') per la riga.
     *
     * @return float
     */
    public function getImportoAttribute()
    {
        return $this->incorporaIVA() ? $this->totale : $this->totale_imponibile;
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

    public function getIvaAttribute()
    {
        return ($this->iva_unitaria - $this->sconto_iva_unitario) * $this->qta;
    }

    public function getIvaIndetraibileAttribute()
    {
        return $this->iva / 100 * $this->aliquota->indetraibile;
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
        return $this->qta * $this->sconto_unitario;
    }

    /**
     * Restituisce il tipo di sconto della riga corrente.
     *
     * @return float
     */
    public function getTipoScontoAttribute()
    {
        return $this->sconto_percentuale ? 'PRC' : 'UNT';
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
     * Restituisce il prezzo unitario corrente (unitario oppure unitario ivato a seconda dell'impostazione 'Utilizza prezzi di vendita comprensivi di IVA') per la riga.
     *
     * @return float
     */
    public function getPrezzoUnitarioCorrenteAttribute()
    {
        // Gestione IVA incorporata
        if ($this->incorporaIVA()) {
            return $this->prezzo_unitario_ivato;
        } else {
            return $this->prezzo_unitario;
        }
    }

    /**
     * Restituisce lo sconto unitario corrente (unitario oppure unitario ivato a seconda dell'impostazione 'Utilizza prezzi di vendita comprensivi di IVA') per la riga.
     *
     * @return float
     */
    public function getScontoUnitarioCorrenteAttribute()
    {
        // Gestione IVA incorporata
        if ($this->incorporaIVA()) {
            return $this->sconto_unitario_ivato;
        } else {
            return $this->sconto_unitario;
        }
    }

    /**
     * Imposta il prezzo unitario (senza IVA) per la riga corrente.
     *
     * @param $value
     */
    public function setPrezzoUnitarioAttribute($value)
    {
        $this->attributes['prezzo_unitario'] = $value;
        $percentuale_iva = floatval($this->aliquota->percentuale) / 100;

        $this->attributes['iva_unitaria'] = $value * $percentuale_iva; // Calcolo IVA
        $this->attributes['prezzo_unitario_ivato'] = $value + $this->attributes['iva_unitaria'];
    }

    /**
     * Imposta il prezzo unitario ivato (con IVA) per la riga corrente.
     *
     * @param $value
     */
    public function setPrezzoUnitarioIvatoAttribute($value)
    {
        $this->attributes['prezzo_unitario_ivato'] = $value;
        $percentuale_iva = floatval($this->aliquota->percentuale) / 100;

        $this->attributes['iva_unitaria'] = $value * $percentuale_iva / (1 + $percentuale_iva); // Calcolo IVA
        $this->attributes['prezzo_unitario'] = $value - $this->attributes['iva_unitaria'];
    }

    /**
     * Imposta il sconto unitario (senza IVA) per la riga corrente.
     *
     * @param $value
     */
    public function setScontoUnitarioAttribute($value)
    {
        $this->attributes['sconto_unitario'] = $value;
        $percentuale_iva = floatval($this->aliquota->percentuale) / 100;

        $this->attributes['sconto_iva_unitario'] = $value * $percentuale_iva; // Calcolo IVA
        $this->attributes['sconto_unitario_ivato'] = $value + $this->attributes['sconto_iva_unitario'];
    }

    /**
     * Imposta il sconto unitario ivato (con IVA) per la riga corrente.
     *
     * @param $value
     */
    public function setScontoUnitarioIvatoAttribute($value)
    {
        $this->attributes['sconto_unitario_ivato'] = $value;
        $percentuale_iva = floatval($this->aliquota->percentuale) / 100;

        $this->attributes['sconto_iva_unitario'] = $value * $percentuale_iva / (1 + $percentuale_iva); // Calcolo IVA
        $this->attributes['sconto_unitario'] = $value - $this->attributes['sconto_iva_unitario'];
    }

    /**
     * Imposta il prezzo unitario secondo le informazioni indicate per valore e tipologia (UNT o PRC).
     *
     * @param $value
     * @param $type
     */
    public function setPrezzoUnitario($prezzo_unitario, $id_iva)
    {
        $this->id_iva = $id_iva;

        // Gestione IVA incorporata
        if ($this->incorporaIVA()) {
            $this->prezzo_unitario_ivato = $prezzo_unitario;
        } else {
            $this->prezzo_unitario = $prezzo_unitario;
        }
    }

    /**
     * Imposta lo sconto secondo le informazioni indicate per valore e tipologia (UNT o PRC).
     *
     * @param $value
     * @param $type
     */
    public function setSconto($value, $type)
    {
        $incorpora_iva = $this->incorporaIVA();

        if ($type == 'PRC') {
            $this->attributes['sconto_percentuale'] = $value;

            $sconto = calcola_sconto([
                'sconto' => $value,
                'prezzo' => $incorpora_iva ? $this->prezzo_unitario_ivato : $this->prezzo_unitario,
                'tipo' => 'PRC',
                'qta' => 1,
            ]);
        } else {
            $this->attributes['sconto_percentuale'] = 0;
            $sconto = $value;
        }

        // Gestione IVA incorporata
        if ($incorpora_iva) {
            $this->sconto_unitario_ivato = $sconto;
        } else {
            $this->sconto_unitario = $sconto;
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
        // Precaricamento Aliquota IVA
        static::addGlobalScope('aliquota', function (Builder $builder) {
            $builder->with('aliquota');
        });

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
        $this->attributes['tipo_sconto'] = $this->sconto_percentuale ? 'PRC' : 'UNT';
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

    protected function incorporaIVA()
    {
        return $this->parent->direzione == 'entrata' && setting('Utilizza prezzi di vendita comprensivi di IVA');
    }
}
