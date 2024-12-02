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

namespace Modules\Contratti;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Common\Components\Component;
use Common\Document;
use Modules\Anagrafiche\Anagrafica;
use Modules\Interventi\Intervento;
use Modules\TipiIntervento\Tipo as TipoSessione;
use Plugins\PianificazioneFatturazione\Pianificazione;
use Plugins\PianificazioneInterventi\Promemoria;
use Traits\RecordTrait;
use Traits\ReferenceTrait;
use Util\Generator;

class Contratto extends Document
{
    use ReferenceTrait;
    use RecordTrait;

    /**
     * @var bool Disabilita movimentazione automatica
     */
    public static $movimenta_magazzino = false;

    protected $table = 'co_contratti';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'data_bozza',
        'data_conclusione',
        'data_accettazione',
        'data_rifiuto',
    ];

    /**
     * Crea un nuovo contratto.
     *
     * @param string $nome
     *
     * @return self
     */
    public static function build(Anagrafica $anagrafica, $nome, $id_segment = null)
    {
        $model = new static();

        $stato_documento = Stato::where('name', 'Bozza')->first()->id;

        $id_agente = $anagrafica->idagente;
        $id_segment = $id_segment ?: getSegmentPredefined($model->getModule()->id);

        $id_pagamento = $anagrafica->idpagamento_vendite ?: setting('Tipo di pagamento predefinito');

        $model->anagrafica()->associate($anagrafica);
        $model->stato()->associate($stato_documento);

        $model->nome = $nome;
        $model->data_bozza = Carbon::now();
        $model->numero = static::getNextNumero($model->data_bozza, $id_segment);
        $model->id_segment = $id_segment;

        if (!empty($id_agente)) {
            $model->idagente = $id_agente;
        }

        if (!empty($id_pagamento)) {
            $model->idpagamento = $id_pagamento;
        }
        $model->condizioni_fornitura = setting('Condizioni generali di fornitura contratti');

        // Salvataggio delle informazioni
        $model->save();

        return $model;
    }

    public function fixTipiSessioni()
    {
        $database = database();

        $presenti = $database->fetchArray('SELECT idtipointervento FROM co_contratti_tipiintervento WHERE idcontratto = '.prepare($this->id));

        // Aggiunta associazioni costi unitari al contratto
        $tipi = TipoSessione::whereNotIn('id', array_column($presenti, 'idtipointervento'))->get();

        foreach ($tipi as $tipo) {
            $database->insert('co_contratti_tipiintervento', [
                'idcontratto' => $this->id,
                'idtipointervento' => $tipo->id,
                'costo_ore' => $tipo->costo_orario,
                'costo_km' => $tipo->costo_km,
                'costo_dirittochiamata' => $tipo->costo_diritto_chiamata,
                'costo_ore_tecnico' => $tipo->costo_orario_tecnico,
                'costo_km_tecnico' => $tipo->costo_km_tecnico,
                'costo_dirittochiamata_tecnico' => $tipo->costo_diritto_chiamata_tecnico,
            ]);
        }
    }

    public function setTipoValiditaAttribute($value)
    {
        $this->attributes['tipo_validita'] = $value == 'manual' ? null : $value;
    }

    /**
     * Controlla se la data di conclusione del documento deve essere calcolata in modo automatico.
     *
     * @return bool
     */
    public function isDataConclusioneAutomatica()
    {
        return !empty($this->validita) && !empty($this->tipo_validita) && !empty($this->data_accettazione);
    }

    /**
     * Restituisce il nome del modulo a cui l'oggetto è collegato.
     *
     * @return string
     */
    public function getModuleAttribute()
    {
        return 'Contratti';
    }

    public function getDirezioneAttribute()
    {
        return 'entrata';
    }

    public function anagrafica()
    {
        return $this->belongsTo(Anagrafica::class, 'idanagrafica');
    }

    public function stato()
    {
        return $this->belongsTo(Stato::class, 'idstato');
    }

    public function articoli()
    {
        return $this->hasMany(Components\Articolo::class, 'idcontratto');
    }

    public function righe()
    {
        return $this->hasMany(Components\Riga::class, 'idcontratto');
    }

    public function sconti()
    {
        return $this->hasMany(Components\Sconto::class, 'idcontratto');
    }

    public function descrizioni()
    {
        return $this->hasMany(Components\Descrizione::class, 'idcontratto');
    }

    public function interventi()
    {
        return $this->hasMany(Intervento::class, 'id_contratto');
    }

    public function promemoria()
    {
        return $this->hasMany(Promemoria::class, 'idcontratto');
    }

    public function pianificazioni()
    {
        return $this->hasMany(Pianificazione::class, 'idcontratto');
    }

    public function fixBudget()
    {
        $this->budget = $this->totale_imponibile ?: 0;
    }

    public function fixDataConclusione()
    {
        // Calcolo della data di conclusione in base alla validità
        if ($this->isDataConclusioneAutomatica() && !empty($this->data_accettazione)) {
            $intervallo = CarbonInterval::make($this->validita.' '.$this->tipo_validita);
            $data = Carbon::make($this->data_accettazione)->add($intervallo);
            $this->data_conclusione = $data->subDays(1);
        }
    }

    public function save(array $options = [])
    {
        $this->fixBudget();
        $this->fixDataConclusione();

        $result = parent::save($options);

        $this->fixTipiSessioni();

        return $result;
    }

    /**
     * Effettua un controllo sui campi del documento.
     * Viene richiamato dalle modifiche alle righe del documento.
     */
    public function triggerEvasione(Component $trigger)
    {
        parent::triggerEvasione($trigger);

        if (setting('Cambia automaticamente stato contratti fatturati')) {
            $righe = $this->getRighe();
            $qta_evasa = $righe->sum('qta_evasa');
            $qta = $righe->sum('qta');
            $parziale = $qta != $qta_evasa;

            // Impostazione del nuovo stato
            if ($qta_evasa == 0) {
                $descrizione = 'In lavorazione';
                $codice_intervento = 'OK';
            } else {
                $descrizione = $parziale ? 'Parzialmente fatturato' : 'Fatturato';
                $codice_intervento = 'FAT';
            }

            $stato = Stato::where('name', $descrizione)->first()->id;
            $this->stato()->associate($stato);
            $this->save();

            // Trasferimento degli interventi collegati
            $interventi = $this->interventi;
            $stato_intervento = \Modules\Interventi\Stato::where('codice', $codice_intervento)->first();
            foreach ($interventi as $intervento) {
                if ($intervento->stato->is_completato == 1) {
                    $intervento->stato()->associate($stato_intervento);
                    $intervento->save();
                }
            }
        }
    }

    // Metodi statici

    /**
     * Calcola il nuovo numero di contratto.
     *
     * @return string
     */
    public static function getNextNumero($data, $id_segment)
    {
        $maschera = Generator::getMaschera($id_segment);

        if (str_contains($maschera, 'm')) {
            $ultimo = Generator::getPreviousFrom($maschera, 'co_contratti', 'numero', [
                'YEAR(data_bozza) = '.prepare(date('Y', strtotime((string) $data))),
                'MONTH(data_bozza) = '.prepare(date('m', strtotime((string) $data))),
            ]);
        } elseif (str_contains($maschera, 'YYYY') or str_contains($maschera, 'yy')) {
            $ultimo = Generator::getPreviousFrom($maschera, 'co_contratti', 'numero', [
                'YEAR(data_bozza) = '.prepare(date('Y', strtotime((string) $data))),
            ]);
        } else {
            $ultimo = Generator::getPreviousFrom($maschera, 'co_contratti', 'numero');
        }

        $numero = Generator::generate($maschera, $ultimo);

        return $numero;
    }

    // Opzioni di riferimento

    public function getReferenceName()
    {
        return 'Contratto';
    }

    public function getReferenceNumber()
    {
        return $this->numero;
    }

    public function getReferenceDate()
    {
        return $this->data_bozza;
    }

    public function getReferenceRagioneSociale()
    {
        return $this->anagrafica->ragione_sociale;
    }
}
