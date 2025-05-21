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

namespace Modules\Interventi;

use Common\Document;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Anagrafiche\Anagrafica;
use Modules\Contratti\Contratto;
use Modules\Preventivi\Preventivo;
use Modules\TipiIntervento\Tipo as TipoSessione;
use Traits\RecordTrait;
use Traits\ReferenceTrait;
use Util\Generator;

class Intervento extends Document
{
    use ReferenceTrait;
    // use SoftDeletes;

    use RecordTrait;

    protected $table = 'in_interventi';

    protected $info = [];

    protected $casts = [
        'data_richiesta' => 'date',
        'data_scadenza' => 'date',
    ];

    protected static $translated_fields = [];

    /**
     * Crea un nuovo intervento.
     *
     * @param string $data_richiesta
     *
     * @return self
     */
    public static function build(Anagrafica $anagrafica, TipoSessione $tipo_sessione, Stato $stato, $data_richiesta, $id_segment = null)
    {
        $model = new static();

        $id_segment = $id_segment ?: getSegmentPredefined($model->getModule()->id);

        $model->anagrafica()->associate($anagrafica);
        $model->stato()->associate($stato);
        $model->tipo()->associate($tipo_sessione);

        $model->codice = static::getNextCodice($data_richiesta, $id_segment);
        $model->data_richiesta = $data_richiesta;
        $model->id_segment = $id_segment;
        $model->idagente = $anagrafica->idagente;
        $model->idpagamento = setting('Tipo di pagamento predefinito');

        $user = \Auth::user();
        $id_sede = null;
        foreach ($user->sedi as $sede) {
            if ($sede != 0 || count($user->sedi) == 1) {
                $id_sede = $sede;
                break;
            }
        }

        if ($id_sede === null && !empty($user->sedi)) {
            $id_sede = $user->sedi[0];
        }

        $model->idsede_partenza = $id_sede;
        $model->save();

        return $model;
    }

    public function getOreTotaliAttribute()
    {
        if (!isset($this->info['ore_totali'])) {
            $sessioni = $this->sessioni()->leftJoin('in_tipiintervento', 'in_interventi_tecnici.idtipointervento', 'in_tipiintervento.id')->where('non_conteggiare', 0);

            $this->info['ore_totali'] = $sessioni->sum('ore');
        }

        return $this->info['ore_totali'];
    }

    public function getKmTotaliAttribute()
    {
        if (!isset($this->info['km_totali'])) {
            $sessioni = $this->sessioni;

            $this->info['km_totali'] = $sessioni->sum('km');
        }

        return $this->info['km_totali'];
    }

    public function getInizioAttribute()
    {
        if (!isset($this->info['inizio'])) {
            $sessioni = $this->sessioni;

            $this->info['inizio'] = $sessioni->min('orario_inizio');
        }

        return $this->info['inizio'] ?: $this->data_richiesta;
    }

    public function getFineAttribute()
    {
        if (!isset($this->info['fine'])) {
            $sessioni = $this->sessioni;

            $this->info['fine'] = $sessioni->max('orario_fine');
        }

        return $this->info['fine'] ?: $this->data_richiesta;
    }

    public function getModuleAttribute()
    {
        return 'Interventi';
    }

    public function getDirezioneAttribute()
    {
        return 'entrata';
    }

    /**
     * Restituisce la collezione di righe e articoli con valori rilevanti per i conti.
     *
     * @return iterable
     */
    public function getRigheContabili()
    {
        $results = parent::getRigheContabili();

        return $this->mergeCollections($results, $this->sessioni);
    }

    // Relazioni Eloquent

    public function anagrafica()
    {
        return $this->belongsTo(Anagrafica::class, 'idanagrafica');
    }

    public function preventivo()
    {
        return $this->belongsTo(Preventivo::class, 'id_preventivo');
    }

    public function contratto()
    {
        return $this->belongsTo(Contratto::class, 'id_contratto');
    }

    public function stato()
    {
        return $this->belongsTo(Stato::class, 'idstatointervento');
    }

    public function tipo()
    {
        return $this->belongsTo(TipoSessione::class, 'idtipointervento');
    }

    public function articoli()
    {
        return $this->hasMany(Components\Articolo::class, 'idintervento');
    }

    public function righe()
    {
        return $this->hasMany(Components\Riga::class, 'idintervento');
    }

    public function sconti()
    {
        return $this->hasMany(Components\Sconto::class, 'idintervento');
    }

    public function descrizioni()
    {
        return $this->hasMany(Components\Descrizione::class, 'idintervento');
    }

    public function sessioni()
    {
        return $this->hasMany(Components\Sessione::class, 'idintervento');
    }

    public function toArray()
    {
        $array = parent::toArray();

        $result = array_merge($array, [
            'ore_totali' => $this->ore_totali,
            'km_totali' => $this->km_totali,
        ]);

        return $result;
    }

    // Metodi statici

    /**
     * Calcola il nuovo codice di intervento.
     *
     * @param string $data
     *
     * @return string
     */
    public static function getNextCodice($data, $id_segment)
    {
        $maschera = Generator::getMaschera($id_segment);

        // $ultimo = Generator::getPreviousFrom($maschera, 'in_interventi', 'codice');

        if (str_contains($maschera, 'YYYY') || str_contains($maschera, 'yy')) {
            $ultimo = Generator::getPreviousFrom($maschera, 'in_interventi', 'codice', [
                'YEAR(data_richiesta) = '.prepare(date('Y', strtotime($data))),
            ], $data);
        } else {
            $ultimo = Generator::getPreviousFrom($maschera, 'in_interventi', 'codice');
        }

        $numero = Generator::generate($maschera, $ultimo, $quantity = 1, $values = [], $data);

        return $numero;
    }

    // Opzioni di riferimento

    public function getReferenceName()
    {
        return 'Attività';
    }

    public function getReferenceNumber()
    {
        return $this->codice;
    }

    public function getReferenceDate()
    {
        return $this->fine;
    }

    public function getReferenceRagioneSociale()
    {
        return $this->anagrafica->ragione_sociale;
    }

    public static function getTranslatedFields()
    {
        return self::$translated_fields;
    }
}
