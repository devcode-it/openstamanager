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

namespace Modules\Fatture\Components;

use Illuminate\Database\Eloquent\Builder;
use Modules\Fatture\Fattura;
use Modules\Ritenute\RitenutaAcconto;
use Modules\Rivalse\RivalsaINPS;

trait RelationTrait
{
    public function getDocumentID()
    {
        return 'iddocumento';
    }

    public function document()
    {
        return $this->belongsTo(Fattura::class, $this->getDocumentID());
    }

    public function fattura()
    {
        return $this->document();
    }

    public function getNettoAttribute()
    {
        $result = $this->totale - $this->ritenuta_acconto - $this->ritenuta_contributi;

        if ($this->getDocument()->split_payment) {
            $result = $result - $this->iva;
        }

        return $result;
    }

    /**
     * Restituisce i dati aggiuntivi per la fattura elettronica dell'elemento.
     *
     * @return array
     */
    public function getDatiAggiuntiviFEAttribute()
    {
        $result = json_decode($this->attributes['dati_aggiuntivi_fe'], true);

        return (array) $result;
    }

    /**
     * Imposta i dati aggiuntivi per la fattura elettronica dell'elemento.
     */
    public function setDatiAggiuntiviFEAttribute($values)
    {
        $values = (array) $values;
        $dati = array_deep_clean($values);

        $this->attributes['dati_aggiuntivi_fe'] = json_encode($dati);
    }

    /**
     * Restituisce il totale (imponibile + iva + rivalsa_inps + iva_rivalsainps) dell'elemento.
     *
     * @return float
     */
    public function getTotaleAttribute()
    {
        return $this->totale_imponibile + $this->iva + $this->rivalsa_inps + $this->iva_rivalsa_inps;
    }

    public function getRivalsaINPSAttribute()
    {
        return $this->totale_imponibile / 100 * $this->rivalsa->percentuale;
    }

    public function getIvaRivalsaINPSAttribute()
    {
        return $this->rivalsa_inps / 100 * $this->aliquota->percentuale;
    }

    public function getRitenutaAccontoAttribute()
    {
        $result = $this->totale_imponibile;

        if ($this->calcolo_ritenuta_acconto == 'IMP+RIV') {
            $result += $this->rivalsainps;
        }

        $ritenuta = $this->ritenuta;
        $result = $result * $ritenuta->percentuale_imponibile / 100;

        return $result / 100 * $ritenuta->percentuale;
    }

    public function getRitenutaContributiAttribute()
    {
        if ($this->attributes['ritenuta_contributi']) {
            $result = $this->totale_imponibile;
            $ritenuta = $this->getDocument()->ritenutaContributi;

            $result = $result * $ritenuta->percentuale_imponibile / 100;

            return $result / 100 * $ritenuta->percentuale;
        }

        return 0;
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

    public function getIdContoAttribute()
    {
        return $this->attributes['idconto'];
    }

    public function setIdContoAttribute($value)
    {
        $this->attributes['idconto'] = $value;
    }

    public function rivalsa()
    {
        return $this->belongsTo(RivalsaINPS::class, 'idrivalsainps');
    }

    public function ritenuta()
    {
        return $this->belongsTo(RitenutaAcconto::class, 'idritenutaacconto');
    }

    /**
     * Salva la riga, impostando i campi dipendenti dai parametri singoli.
     *
     * @return bool
     */
    public function save(array $options = [])
    {
        $this->fixRitenutaAcconto();
        $this->fixRivalsaINPS();

        return parent::save($options);
    }

    public function delete()
    {
        $result = parent::delete();

        if (!empty($this->idintervento)) {
            database()->query("UPDATE in_interventi SET idstatointervento = (SELECT idstatointervento FROM in_statiintervento WHERE codice = 'OK') WHERE id=".prepare($this->idintervento));
        }

        return $result;
    }

    /**
     * Modifica la quantità del componente.
     * Se la fattura è una Nota di credito/debito, risale al secondo livello di origine del componente e corregge di conseguenza le quantità evase.
     *
     * @param float $value
     *
     * @return float
     */
    public function setQtaAttribute($value)
    {
        list($qta, $diff) = $this->parseQta($value);
        parent::setQtaAttribute($value);

        // Individuazione fattura corrente (fix in caso di creazione diretta)
        $fattura = $this->fattura;
        if (isset($fattura) && $fattura->isNota() && $this->hasOriginalComponent()) {
            $source = $this->getOriginalComponent();

            // Aggiornamento della quantità evasa di origine
            if ($source->hasOriginalComponent()) {
                $target = $source->getOriginalComponent();

                $target->qta_evasa -= $diff;
                $target->save();
            }
        }

        return $diff;
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

    protected static function boot($bypass = false)
    {
        parent::boot($bypass);

        // Precaricamento Rivalsa INPS
        static::addGlobalScope('rivalsa', function (Builder $builder) {
            $builder->with('rivalsa');
        });

        // Precaricamento Ritenuta d'Acconto
        static::addGlobalScope('ritenuta', function (Builder $builder) {
            $builder->with('ritenuta');
        });
    }
}
