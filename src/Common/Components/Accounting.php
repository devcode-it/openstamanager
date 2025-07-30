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

use Illuminate\Database\Eloquent\Builder;
use Modules\Iva\Aliquota;

/**
 * Classe dedicata alla gestione delle informazioni contabili standard di una componente dei Documenti.
 *
 * Prevede i seguenti campi nel database:
 *
 * @property float costo_unitario
 * @property float prezzo_unitario
 * @property float iva_unitaria = prezzo_unitario * percentuale_iva
 * @property float prezzo_unitario_ivato = prezzo_unitario + iva_unitaria
 * @property float sconto_unitario
 * @property float sconto_iva_unitario = sconto_unitario * percentuale_iva
 * @property float sconto_unitario_ivato = sconto_unitario + sconto_iva_unitario
 * @property float iva_unitaria_scontata = iva_unitaria - sconto_iva_unitario
 *
 * Introduce i seguenti campi ausiliari:
 * @property float imponibile = prezzo_unitario * qta
 * @property float sconto = sconto_unitario * qta
 * @property float totale_imponibile = (prezzo_unitario - sconto_unitario) * qta [Imponibile con sconto]
 * @property float iva = (iva_unitaria - sconto_iva_unitario) * qta
 * @property float totale = (prezzo_unitario_ivato - sconto_unitario_ivato) * qta [Totale imponibile con IVA]
 * @property float importo = se incorpora_iva: totale, altrimenti: totale_imponibile
 * @property float spesa = costo_unitario * qta
 *
 * Per una estensione del sistema dei totali (Rivalsa, Ritenuta, ...), si consiglia di introdurre un relativo Netto a pagare. [Fatture]
 *
 * @since 2.4.18
 */
abstract class Accounting extends Component
{
    protected $casts = [
        'qta' => 'float',
        'qta_evasa' => 'float',
        'prezzo_unitario' => 'float',
        'prezzo_unitario_ivato' => 'float',
        'iva_unitaria' => 'float',
        'iva_unitaria_scontata' => 'float',
        'sconto_percentuale' => 'float',
        'sconto_unitario' => 'float',
        'sconto_iva_unitario' => 'float',
        'sconto_unitario_ivato' => 'float',
        'provvigione_percentuale' => 'float',
        'provvigione_unitaria' => 'float',
        // 'qta_evasa' => 'float',
    ];

    protected $appends = [
        'prezzo_unitario_corrente',
        'sconto_unitario_corrente',
        'max_qta',
        'spesa',
        'imponibile',
        'sconto',
        'provvigione',
        'totale_imponibile',
        'iva',
        'totale',
    ];

    protected $hidden = [
        'document',
        'aliquota',
    ];

    public function getIvaIndetraibileAttribute()
    {
        return $this->iva / 100 * $this->aliquota->indetraibile;
    }

    public function aliquota()
    {
        return $this->belongsTo(Aliquota::class, 'idiva');
    }

    public function getIvaAttribute()
    {
        return ($this->iva_unitaria - $this->sconto_iva_unitario) * $this->qta;
    }

    /**
     * Imposta il prezzo unitario secondo le informazioni indicate per valore e tipologia (UNT o PRC).
     */
    public function setPrezzoUnitario($prezzo_unitario, $id_iva)
    {
        // Salva il valore dello sconto esistente prima di cambiare l'aliquota IVA
        $sconto_esistente = $this->sconto_unitario;

        $this->id_iva = $id_iva;

        // Gestione IVA incorporata
        if ($this->incorporaIVA()) {
            $this->prezzo_unitario_ivato = $prezzo_unitario;
        } else {
            $this->prezzo_unitario = $prezzo_unitario;
        }

        // Ricalcola l'IVA dello sconto esistente con la nuova aliquota
        if ($sconto_esistente > 0) {
            $this->sconto_unitario = $sconto_esistente;
        }
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
     * Restituisce l'imponibile dell'elemento (prezzo unitario senza IVA * qta).
     *
     * @return float
     */
    public function getImponibileAttribute()
    {
        return $this->prezzo_unitario * $this->qta;
    }

    /**
     * Restituisce il tipo di sconto della riga corrente.
     *
     * @return float
     */
    public function getTipoScontoAttribute()
    {
        return $this->sconto_percentuale ? 'PRC' : ($this->sconto ? 'UNT' : 'PRC');
    }

    /**
     * Restituisce il margine totale (imponibile - spesa) relativo all'elemento.
     *
     * @return float
     */
    public function getMargineAttribute()
    {
        return $this->totale_imponibile - $this->spesa - $this->provvigione;
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
     * Restituisce il totale imponibile dell'elemento (imponibile - sconto unitario senza IVA).
     *
     * @return float
     */
    public function getTotaleImponibileAttribute()
    {
        return $this->imponibile - $this->sconto;
    }

    /**
     * Imposta il sconto unitario ivato (con IVA) per la riga corrente.
     */
    public function setScontoUnitarioIvatoAttribute($value)
    {
        $this->attributes['sconto_unitario_ivato'] = $value;
        $percentuale_iva = floatval($this->aliquota->percentuale) / 100;

        $this->attributes['sconto_iva_unitario'] = $value * $percentuale_iva / (1 + $percentuale_iva); // Calcolo IVA
        $this->attributes['sconto_unitario'] = $value - $this->attributes['sconto_iva_unitario'];
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
     * Restituisce l'iva detraibile dell'elemento.
     *
     * @return float
     */
    public function getIvaDetraibileAttribute()
    {
        return $this->iva - $this->iva_indetraibile;
    }

    /**
     * Restituisce l'iva unitaria con lo sconto applicato.
     *
     * @return float
     */
    public function getIvaUnitariaScontataAttribute()
    {
        return $this->iva_unitaria - $this->sconto_iva_unitario;
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
     * Imposta lo sconto secondo le informazioni indicate per valore e tipologia (UNT o PRC).
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
     * Imposta la provvigione secondo le informazioni indicate per valore e tipologia (UNT o PRC).
     */
    public function setProvvigione($value, $type)
    {
        $provvigione_unitaria = 0;

        if ($type == 'PRC') {
            $this->provvigione_percentuale = $value;
            $provvigione_unitaria = ($this->prezzo_unitario - $this->sconto_unitario) / 100 * floatval($value);
            $this->provvigione_unitaria = $provvigione_unitaria;
        } else {
            $this->provvigione_percentuale = 0;
            $provvigione_unitaria = $value;
            $this->provvigione_unitaria = $provvigione_unitaria;
        }
    }

    public function incorporaIVA()
    {
        return $this->getDocument()->direzione == 'entrata' && setting('Utilizza prezzi di vendita comprensivi di IVA');
    }

    /**
     * Imposta il prezzo unitario (senza IVA) per la riga corrente.
     */
    public function setPrezzoUnitarioAttribute($value)
    {
        $this->attributes['prezzo_unitario'] = $value;
        $percentuale_iva = floatval($this->aliquota->percentuale) / 100;

        $this->attributes['iva_unitaria'] = $value * $percentuale_iva; // Calcolo IVA
        $this->attributes['prezzo_unitario_ivato'] = $value + $this->attributes['iva_unitaria'];
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
     * Restituisce la provvigione della riga corrente in euro.
     *
     * @return float
     */
    public function getProvvigioneAttribute()
    {
        return $this->qta * $this->provvigione_unitaria;
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
     * Restituisce il margine percentuale del documento.
     *
     * @return float
     */
    public function getMarginePercentualeAttribute()
    {
        return (1 - (($this->spesa + $this->provvigione) / ($this->totale_imponibile ?: 1))) * 100;
    }

    /**
     * Restituisce il ricarico percentuale del documento.
     *
     * @return float
     */
    public function getRicaricoPercentualeAttribute()
    {
        return ($this->totale_imponibile && ($this->spesa || $this->provvigione)) ? (($this->totale_imponibile / ($this->spesa + $this->provvigione)) - 1) * 100 : 0;
    }

    /**
     * Imposta il prezzo unitario ivato (con IVA) per la riga corrente.
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
     */
    public function setScontoUnitarioAttribute($value)
    {
        $this->attributes['sconto_unitario'] = $value;
        $percentuale_iva = floatval($this->aliquota->percentuale) / 100;

        $this->attributes['sconto_iva_unitario'] = $value * $percentuale_iva; // Calcolo IVA
        $this->attributes['sconto_unitario_ivato'] = $value + $this->attributes['sconto_iva_unitario'];
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

    public function getSubtotaleAttribute()
    {
        return $this->imponibile;
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
        $this->fixProvvigione();

        $this->fixIva();

        return parent::save($options);
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

        $iva = Aliquota::Find($this->idiva);
        if (database()->isConnected() && database()->tableExists('co_iva_lang')) {
            $this->attributes['desc_iva'] = $iva ? $iva->getTranslation('title') : '';
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
        $this->attributes['tipo_sconto'] = $this->sconto_percentuale ? 'PRC' : ($this->sconto ? 'UNT' : 'PRC');
    }

    /**
     * Effettua i conti per lo sconto totale.
     */
    protected function fixProvvigione()
    {
        $this->attributes['provvigione'] = $this->provvigione;
        $this->attributes['tipo_provvigione'] = $this->provvigione_percentuale ? 'PRC' : 'UNT';
    }

    protected static function boot()
    {
        parent::boot();

        // Pre-caricamento Aliquota IVA
        static::addGlobalScope('aliquota', function (Builder $builder) {
            $builder->with('aliquota');
        });
    }
}
