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

    /**
     * Restituisce i dati aggiuntivi per la fattura elettronica dell'elemento.
     *
     * @return array
     */
    public function getDatiAggiuntiviFEAttribute()
    {
        $result = $this->attributes['dati_aggiuntivi_fe'] ? json_decode((string) $this->attributes['dati_aggiuntivi_fe'], true) : '';

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

        if (!empty($this->idintervento) && $this->fattura->getRighe()->where('idintervento', $this->idintervento)->count() == 1) {
            database()->query("UPDATE `in_interventi` SET `idstatointervento` = (SELECT `id` FROM `in_statiintervento` WHERE `codice` = 'OK') WHERE `id`=".prepare($this->idintervento));
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
        [$qta, $diff] = $this->parseQta($value);
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
     * Azione personalizzata per la copia dell'oggetto (dopo la copia).
     * Aggiunge la descrizione aggiuntiva personalizzata se impostata.
     */
    protected function customAfterDataCopiaIn($original)
    {
        // Chiama il metodo parent per mantenere la compatibilità
        parent::customAfterDataCopiaIn($original);

        // Verifica se l'impostazione "Descrizione aggiuntiva personalizzata in fatturazione" è configurata
        $descrizione_aggiuntiva = setting('Descrizione aggiuntiva personalizzata in fatturazione');

        if ($descrizione_aggiuntiva) {
            // Sostituisce i placeholder con i valori effettivi dalla riga originale
            $descrizione_personalizzata = $this->replacePlaceholders($descrizione_aggiuntiva, $original);

            // Aggiunge la descrizione personalizzata alla descrizione esistente
            if ($descrizione_personalizzata) {
                $this->descrizione = $this->descrizione."\n".$descrizione_personalizzata;
            }
        }
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

    /**
     * Sostituisce i placeholder nella descrizione con i valori dalla riga originale.
     *
     * @param string $text     Testo con placeholder da sostituire
     * @param mixed  $original Riga originale da cui prendere i valori
     *
     * @return string Testo con placeholder sostituiti
     */
    private function replacePlaceholders($text, $original)
    {
        // Trova tutti i placeholder nel formato {campo}
        preg_match_all('/\{([^}]+)\}/', $text, $matches);

        if (empty($matches[1])) {
            return $text;
        }

        $result = $text;

        foreach ($matches[1] as $field) {
            $placeholder = '{'.$field.'}';
            $value = '';

            // Prova ad accedere al campo dalla riga originale
            if (isset($original->$field)) {
                $value = $original->$field;
            }
            // Se il campo non esiste direttamente, prova con getAttributes()
            elseif (method_exists($original, 'getAttributes')) {
                $attributes = $original->getAttributes();
                if (isset($attributes[$field])) {
                    $value = $attributes[$field];
                }
            }

            // Formatta il valore se è una data
            if ($value) {
                $value = $this->formatFieldValue($field, $value);
            }

            // Sostituisce il placeholder con il valore trovato
            $result = str_replace($placeholder, $value, $result);
        }

        return $result;
    }

    /**
     * Formatta il valore del campo in base al tipo.
     * Se il campo è una data, la formatta usando Translator.
     *
     * @param string $field Nome del campo
     * @param mixed  $value Valore del campo
     *
     * @return string Valore formattato
     */
    private function formatFieldValue($field, $value)
    {
        if ($this->isDateFormat($value)) {
            // Usa Translator per formattare la data
            return \Translator::dateToLocale($value);
        }

        return $value;
    }

    /**
     * Verifica se un valore ha il formato di una data.
     *
     * @param mixed $value Valore da verificare
     *
     * @return bool True se il valore è una data
     */
    private function isDateFormat($value)
    {
        if (!is_string($value) || empty($value)) {
            return false;
        }

        // Verifica formati data comuni: YYYY-MM-DD, YYYY-MM-DD HH:MM:SS
        return preg_match('/^\d{4}-\d{2}-\d{2}(\s\d{2}:\d{2}:\d{2})?$/', $value) === 1;
    }
}
