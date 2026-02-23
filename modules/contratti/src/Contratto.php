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

class Contratto extends Document
{
    use ReferenceTrait;
    use RecordTrait;

    /**
     * @var bool Disabilita movimentazione automatica
     */
    public static $movimenta_magazzino = false;

    /**
     * @var bool Flag per evitare ricorsioni durante il salvataggio
     */
    protected $is_saving = false;

    protected $table = 'co_contratti';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $casts = [
        'data_bozza' => 'date',
        'data_conclusione' => 'date',
        'data_accettazione' => 'date',
        'data_rifiuto' => 'date',
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
        // Ottieni i tipi di intervento già associati al contratto
        $presenti = database()->fetchArray('SELECT idtipointervento FROM co_contratti_tipiintervento WHERE idcontratto = '.prepare($this->id));
        $id_presenti = array_column($presenti, 'idtipointervento');

        // Aggiunta associazioni costi unitari al contratto per i tipi non presenti
        $tipi = TipoSessione::whereNull('deleted_at')
            ->whereNotIn('id', $id_presenti)
            ->get(['id', 'costo_orario', 'costo_km', 'costo_diritto_chiamata', 'costo_orario_tecnico', 'costo_km_tecnico', 'costo_diritto_chiamata_tecnico']);

        if ($tipi->isEmpty()) {
            return;
        }

        // Costruisci l'array di inserimento in un'unica operazione
        $database = database();
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
        // Evita ricorsioni
        if ($this->is_saving) {
            return parent::save($options);
        }

        $this->is_saving = true;

        try {
            $this->fixBudget();
            $this->fixDataConclusione();

            $result = parent::save($options);

            $this->fixTipiSessioni();
        } finally {
            $this->is_saving = false;
        }

        return $result;
    }

    /**
     * Effettua un controllo sui campi del documento.
     * Viene richiamato dalle modifiche alle righe del documento.
     */
    public function triggerEvasione(Component $trigger)
    {
        parent::triggerEvasione($trigger);

        if (!setting('Cambia automaticamente stato contratti fatturati')) {
            return;
        }

        // Non modificare lo stato se il contratto è già in uno stato bloccato, non fatturabile e non pianificabile
        if ($this->stato && $this->stato->is_bloccato && !$this->stato->is_fatturabile && !$this->stato->is_pianificabile) {
            return;
        }

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

        // Ottieni il nuovo stato
        $stato = Stato::where('name', $descrizione)->first();
        if (!$stato) {
            return;
        }

        // Aggiorna lo stato solo se è diverso
        if ($this->idstato != $stato->id) {
            $this->idstato = $stato->id;
            $this->saveQuietly();
        }

        // Ottieni lo stato intervento e trasferisci agli interventi collegati
        $stato_intervento = \Modules\Interventi\Stato::where('codice', $codice_intervento)->first();
        if ($stato_intervento) {
            // Carica solo gli interventi con stato bloccato
            $interventi = $this->interventi()->with('stato')->get();
            foreach ($interventi as $intervento) {
                if ($intervento->stato && $intervento->stato->is_bloccato == 1 && $intervento->idstato != $stato_intervento->id) {
                    $intervento->idstato = $stato_intervento->id;
                    $intervento->saveQuietly();
                }
            }
        }
    }

    /**
     * Salva il modello senza eseguire i trigger e le operazioni automatiche.
     * Utile per evitare ricorsioni durante il salvataggio.
     */
    public function saveQuietly(array $options = [])
    {
        return parent::save($options);
    }

    // Metodi statici

    /**
     * Calcola il nuovo numero di contratto.
     *
     * @return string
     */
    public static function getNextNumero($data, $id_segment)
    {
        return getNextNumeroProgressivo('co_contratti', 'numero', $data, $id_segment, [
            'data_field' => 'data_bozza',
        ]);
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

    public function getReferenceSecondaryNumber()
    {
        return null;
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
