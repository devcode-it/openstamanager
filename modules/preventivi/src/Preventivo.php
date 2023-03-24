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

    protected $info = [];

    /**
     * Crea un nuovo preventivo.
     *
     * @param string $nome
     *
     * @return self
     */
    public static function build(Anagrafica $anagrafica, TipoSessione $tipo_sessione, $nome, $data_bozza, $id_sede, $id_segment = null)
    {
        $model = new static();

        $stato_documento = Stato::where('descrizione', 'Bozza')->first();

        $id_agente = $anagrafica->idagente;
        $id_pagamento = $anagrafica->idpagamento_vendite;
        $id_segment = $id_segment ?: getSegmentPredefined($model->getModule()->id);

        $id_agente = $anagrafica->idagente;

        $id_iva = setting('Iva predefinita');
        if (empty($id_pagamento)) {
            $id_pagamento = setting('Tipo di pagamento predefinito');
        }

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

        if (!empty($id_sede)) {
            $model->idsede = $id_sede;
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
        if (!empty($id_agente)) {
            $model->idagente = $id_agente;
        }
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
            $sessioni = collect();

            $interventi = $this->interventi;
            foreach ($interventi as $intervento) {
                $sessioni = $sessioni->merge($intervento->sessioni);
            }

            $this->info['ore_interventi'] = $sessioni->sum('ore');
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
        return !empty($this->validita) && !empty($this->tipo_validita) && !empty($this->data_accettazione);
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

    public function fixBudget()
    {
        $this->budget = $this->totale_imponibile ?: 0;
    }

    public function fixDataConclusione()
    {
        // Calcolo della data di conclusione in base alla validità
        if ($this->isDataConclusioneAutomatica()) {
            $intervallo = CarbonInterval::make($this->validita.' '.$this->tipo_validita);
            $this->data_conclusione = Carbon::make($this->data_accettazione)->add($intervallo);
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

        $stato_attuale = $this->stato;

        // Impostazione del nuovo stato
        if ($qta_evasa == 0) {
            $descrizione = 'In lavorazione';
            $codice_intervento = 'OK';
        } elseif ($trigger->getDocument() instanceof Fattura) {
            $descrizione = $parziale ? 'Parzialmente fatturato' : 'Fatturato';
            $codice_intervento = 'FAT';
        } else {
            $descrizione = $this->stato->descrizione;
            $codice_intervento = 'OK';
        }

        $stato = Stato::where('descrizione', $descrizione)->first();
        $this->stato()->associate($stato);
        $this->save();

        //cambio stato agli interventi solo se sto fatturando il preventivo
        if ($trigger->getDocument() instanceof Fattura){
            // Trasferimento degli interventi collegati
            $interventi = $this->interventi;
            $stato_intervento = \Modules\Interventi\Stato::where('codice', $codice_intervento)->first();
            foreach ($interventi as $intervento) {
                if ($intervento->stato->is_completato==1) {
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
        $maschera = Generator::getMaschera($id_segment);

        if (strpos($maschera, 'm') !== false) {
            $ultimo = Generator::getPreviousFrom($maschera, 'co_preventivi', 'numero', [
                'YEAR(data_bozza) = '.prepare(date('Y', strtotime($data))),
                'MONTH(data_bozza) = '.prepare(date('m', strtotime($data))),
            ]);
        } elseif ((strpos($maschera, 'YYYY') !== false) or (strpos($maschera, 'yy') !== false)) {
            $ultimo = Generator::getPreviousFrom($maschera, 'co_preventivi', 'numero', [
                'YEAR(data_bozza) = '.prepare(date('Y', strtotime($data))),
            ]);
        } else {
            $ultimo = Generator::getPreviousFrom($maschera, 'co_preventivi', 'numero');
        }

        $numero = Generator::generate($maschera, $ultimo);

        return $numero;
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
        $revisioni = Preventivo::where('master_revision', '=', $this->master_revision)->get()->pluck('id')->toArray();

        return $revisioni;
    }

    public function getUltimaRevisioneAttribute()
    {
        return Preventivo::selectRaw('MAX(numero_revision) AS revisione')->where('master_revision', $this->master_revision)->get()->toArray()[0]['revisione'];
    }
}
