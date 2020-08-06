<?php

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
    //use SoftDeletes;

    use RecordTrait;

    protected $table = 'in_interventi';

    protected $info = [];

    protected $dates = [
        'data_richiesta',
        'data_scadenza',
    ];

    /**
     * Crea un nuovo intervento.
     *
     * @param string $data_richiesta
     *
     * @return self
     */
    public static function build(Anagrafica $anagrafica, TipoSessione $tipo_sessione, Stato $stato, $data_richiesta)
    {
        $model = parent::build();

        $model->anagrafica()->associate($anagrafica);
        $model->stato()->associate($stato);
        $model->tipoSessione()->associate($tipo_sessione);

        $model->codice = static::getNextCodice($data_richiesta);
        $model->data_richiesta = $data_richiesta;

        $model->save();

        return $model;
    }

    public function getOreTotaliAttribute()
    {
        if (!isset($this->info['ore_totali'])) {
            $sessioni = $this->sessioni;

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

        return $this->info['inizio'];
    }

    public function getFineAttribute()
    {
        if (!isset($this->info['fine'])) {
            $sessioni = $this->sessioni;

            $this->info['fine'] = $sessioni->max('orario_fine');
        }

        return $this->info['fine'];
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
        return $this->belongsTo(Tipo::class, 'idtipointervento');
    }

    public function tipoSessione()
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
        return $this->righe()->where('is_descrizione', 1);
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
    public static function getNextCodice($data)
    {
        $maschera = setting('Formato codice attività');

        //$ultimo = Generator::getPreviousFrom($maschera, 'in_interventi', 'codice');

        if ((strpos($maschera, 'YYYY') !== false) or (strpos($maschera, 'yy') !== false)) {
            $ultimo = Generator::getPreviousFrom($maschera, 'in_interventi', 'codice', [
                'YEAR(data_richiesta) = '.prepare(date('Y', strtotime($data))),
            ]);
        } else {
            $ultimo = Generator::getPreviousFrom($maschera, 'in_interventi', 'codice');
        }

        $numero = Generator::generate($maschera, $ultimo);

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
        return $this->data_richiesta;
    }
}
