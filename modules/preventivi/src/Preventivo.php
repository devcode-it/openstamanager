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

namespace Modules\Preventivi;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Common\Components\Component;
use Common\Document;
use Modules\Anagrafiche\Anagrafica;
use Modules\Banche\Banca;
use Modules\Fatture\Fattura;
use Modules\Interventi\Intervento;
use Modules\TipiIntervento\Tipo as TipoSessione;
use Traits\RecordTrait;
use Traits\ReferenceTrait;
use Util\Generator;

class Preventivo extends Document
{
    use ReferenceTrait;
    use RecordTrait;

    /**
     * @var bool Disabilita movimentazione automatica
     */
    public static $movimenta_magazzino = false;

    protected $table = 'co_preventivi';

    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = ['anagrafica', 'stato', 'pagamento'];

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

    protected $info = [];

    /**
     * Crea un nuovo preventivo.
     *
     * @param string $nome
     *
     * @return self
     */
    public static function build(Anagrafica $anagrafica, TipoSessione $tipo_sessione, $nome, $data_bozza, $idsede_destinazione, $id_segment = null)
    {
        $model = new static();

        $stato_documento = Stato::where('name', 'Bozza')->first()->id;

        $id_agente = $anagrafica->idagente;
        $id_pagamento = $anagrafica->idpagamento_vendite ?: setting('Tipo di pagamento predefinito');
        $id_segment = $id_segment ?: getSegmentPredefined($model->getModule()->id);

        $id_iva = setting('Iva predefinita');

        $model->anagrafica()->associate($anagrafica);
        $model->stato()->associate($stato_documento);
        $model->tipoSessione()->associate($tipo_sessione);

        $model->numero = static::getNextNumero($data_bozza, $id_segment);

        // Salvataggio delle informazioni
        $model->nome = $nome;
        if (empty($data_bozza)) {
            $model->data_bozza = Carbon::now();
        } else {
            $model->data_bozza = $data_bozza;
        }

        if (!empty($idsede_destinazione)) {
            $model->idsede_destinazione = $idsede_destinazione;
        }

        if (!empty($id_agente)) {
            $model->idagente = $id_agente;
        }

        if (!empty($id_iva)) {
            $model->idiva = $id_iva;
        }
        if (!empty($id_pagamento)) {
            $model->idpagamento = $id_pagamento;
        }

        // Banca predefinita per l'anagrafica controparte (cliente)
        $banca_controparte = Banca::where('id_anagrafica', $anagrafica->id)
            ->where('predefined', 1)
            ->first();

        $model->id_banca_controparte = $banca_controparte?->id;

        // Banca predefinita per l'azienda, con ricerca della banca impostata per il pagamento
        $azienda = Anagrafica::find(setting('Azienda predefinita'));

        // Logica unificata per la ricerca della banca dell'azienda
        $id_banca_azienda = getBancaAzienda($azienda, $id_pagamento, 'vendite', 'entrata', $anagrafica);

        $model->id_banca_azienda = $id_banca_azienda;

        $model->condizioni_fornitura = setting('Condizioni generali di fornitura preventivi');
        $model->id_segment = $id_segment;

        $model->save();

        // Gestione delle revisioni
        $model->master_revision = $model->id;
        $model->default_revision = 1;

        $model->save();

        return $model;
    }

    // Attributi Eloquent

    public function getOreInterventiAttribute()
    {
        if (!isset($this->info['ore_interventi'])) {
            $this->info['ore_interventi'] = $this->interventi()
                ->with('sessioni')
                ->get()
                ->pluck('sessioni')
                ->flatten()
                ->sum('ore');
        }

        return $this->info['ore_interventi'];
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
        return !empty($this->validita) && !empty($this->tipo_validita) && !empty($this->data_bozza);
    }

    /**
     * Restituisce il nome del modulo a cui l'oggetto è collegato.
     *
     * @return string
     */
    public function getModuleAttribute()
    {
        return 'Preventivi';
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

    public function tipoSessione()
    {
        return $this->belongsTo(TipoSessione::class, 'idtipointervento');
    }

    public function pagamento()
    {
        return $this->belongsTo(\Modules\Pagamenti\Pagamento::class, 'idpagamento');
    }

    public function articoli()
    {
        return $this->hasMany(Components\Articolo::class, 'idpreventivo');
    }

    public function righe()
    {
        return $this->hasMany(Components\Riga::class, 'idpreventivo');
    }

    public function sconti()
    {
        return $this->hasMany(Components\Sconto::class, 'idpreventivo');
    }

    public function descrizioni()
    {
        return $this->hasMany(Components\Descrizione::class, 'idpreventivo');
    }

    public function interventi()
    {
        return $this->hasMany(Intervento::class, 'id_preventivo');
    }

    public function bancaAzienda()
    {
        return $this->belongsTo(Banca::class, 'id_banca_azienda');
    }

    public function bancaControparte()
    {
        return $this->belongsTo(Banca::class, 'id_banca_controparte');
    }

    /**
     * Restituisce i dati bancari in base al pagamento.
     *
     * @return \Modules\Banche\Banca|null
     */
    public function getBanca()
    {
        // Eager loading del pagamento per evitare query aggiuntive
        $pagamento = $this->pagamento;

        if ($pagamento && $pagamento->isRiBa()) {
            // Prima cerca la banca controparte specificata, altrimenti cerca quella predefinita
            $banca = $this->id_banca_controparte 
                ? Banca::find($this->id_banca_controparte)
                : Banca::where('id_anagrafica', $this->idanagrafica)
                    ->where('predefined', 1)
                    ->whereNull('deleted_at')
                    ->first();
        } else {
            $banca = Banca::find($this->id_banca_azienda);
        }

        return $banca;
    }

    public function fixBudget()
    {
        $this->budget = $this->totale_imponibile ?: 0;
    }

    public function fixDataConclusione()
    {
        // Calcolo della data di conclusione in base alla validità
        if ($this->isDataConclusioneAutomatica()) {
            $intervallo = CarbonInterval::make($this->validita.' '.$this->tipo_validita);
            $this->data_conclusione = Carbon::make($this->data_bozza)->add($intervallo);
        }
    }

    public function save(array $options = [])
    {
        $this->fixBudget();
        $this->fixDataConclusione();

        return parent::save($options);
    }

    public function delete()
    {
        $this->interventi()->update(['id_preventivo' => null]);
        $revision = $this->master_revision;

        $result = parent::delete();

        self::where('master_revision', $revision)->delete();

        return $result;
    }

    /**
     * Effettua un controllo sui campi del documento.
     * Viene richiamato dalle modifiche alle righe del documento.
     */
    public function triggerEvasione(Component $trigger)
    {
        parent::triggerEvasione($trigger);

        $righe = $this->getRighe();

        $qta_evasa = $righe->sum('qta_evasa');
        $qta = $righe->sum('qta');
        $parziale = $qta != $qta_evasa;
        $stato = $this->stato;

        // Impostazione del nuovo stato
        if ($qta_evasa == 0) {
            $descrizione = 'In lavorazione';
            $codice_intervento = 'OK';
        } elseif ($trigger->getDocument() instanceof Fattura) {
            $descrizione = $parziale ? 'Parzialmente fatturato' : 'Fatturato';
            $codice_intervento = 'FAT';
        } else {
            $descrizione = $stato->getTranslation('title', \Models\Locale::getPredefined()->id);
            $codice_intervento = 'OK';
        }

        $stato = Stato::where('name', $descrizione)->first()->id;
        $this->stato()->associate($stato);
        $this->save();

        // cambio stato agli interventi solo se sto fatturando il preventivo
        if ($trigger->getDocument() instanceof Fattura) {
            // Trasferimento degli interventi collegati con eager loading dello stato
            $interventi = $this->interventi()->with('stato')->get();
            $stato_intervento = \Modules\Interventi\Stato::where('codice', $codice_intervento)->first();
            foreach ($interventi as $intervento) {
                if ($intervento->stato->is_bloccato == 1) {
                    $intervento->stato()->associate($stato_intervento);
                    $intervento->save();
                }
            }
        }
    }

    // Metodi statici

    /**
     * Calcola il nuovo numero di preventivo.
     *
     * @return string
     */
    public static function getNextNumero($data, $id_segment)
    {
        return getNextNumeroProgressivo('co_preventivi', 'numero', $data, $id_segment, [
            'data_field' => 'data_bozza',
        ]);
    }

    // Opzioni di riferimento

    public function getReferenceName()
    {
        return 'Preventivo';
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

    public function getRevisioniAttribute()
    {
        // Ottimizzazione: usa pluck() direttamente sulla query invece di caricare tutti i modelli
        return Preventivo::where('master_revision', '=', $this->master_revision)
            ->pluck('id')
            ->toArray();
    }

    public function getUltimaRevisioneAttribute()
    {
        // Ottimizzazione: usa value() invece di toArray() per ottenere solo il valore
        return Preventivo::where('master_revision', $this->master_revision)
            ->max('numero_revision');
    }
}
