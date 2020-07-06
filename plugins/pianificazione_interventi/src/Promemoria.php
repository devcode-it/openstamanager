<?php

namespace Plugins\PianificazioneInterventi;

use Common\Document;
use Modules\Contratti\Contratto;
use Modules\Interventi\Intervento;
use Modules\TipiIntervento\Tipo as TipoSessione;
use Traits\RecordTrait;

class Promemoria extends Document
{
    use RecordTrait;

    protected $table = 'co_promemoria';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'data_richiesta',
    ];

    /**
     * Crea un nuovo promemoria.
     *
     * @param string $data_richiesta
     *
     * @return self
     */
    public static function build(Contratto $contratto, TipoSessione $tipo, $data_richiesta)
    {
        $model = parent::build();

        $model->contratto()->associate($contratto);
        $model->tipo()->associate($tipo);

        $model->data_richiesta = $data_richiesta;
        $model->idsede = $contratto->idsede;

        // Salvataggio delle informazioni
        $model->save();

        return $model;
    }

    public function getPluginAttribute()
    {
        return 'Pianificazione interventi';
    }

    public function getDirezioneAttribute()
    {
        return 'entrata';
    }

    public function pianifica(Intervento $intervento)
    {
        $this->intervento()->associate($intervento); // Collego l'intervento ai promemoria
        $this->save();

        // Copia delle righe
        $righe = $this->getRighe();
        foreach ($righe as $riga) {
            $copia = $riga->copiaIn($intervento, $riga->qta);
        }

        // Copia degli allegati
        $allegati = $this->uploads();
        foreach ($allegati as $allegato) {
            $allegato->copia([
                'id_module' => $intervento->getModule()->id,
                'id_record' => $intervento->id,
            ]);
        }

        // Collego gli impianti del promemoria all'intervento
        $database = database();
        if (!empty($this->idimpianti)) {
            $impianti = explode(',', $this->idimpianti);
            foreach ($impianti as $impianto) {
                $database->query('INSERT INTO my_impianti_interventi (idintervento, idimpianto) VALUES ('.prepare($intervento->id).', '.prepare($impianto).' )');
            }
        }
    }

    public function anagrafica()
    {
        return $this->contratto->anagrafica();
    }

    public function contratto()
    {
        return $this->belongsTo(Contratto::class, 'idcontratto');
    }

    public function intervento()
    {
        return $this->belongsTo(Intervento::class, 'idintervento');
    }

    public function tipo()
    {
        return $this->belongsTo(TipoSessione::class, 'idtipointervento');
    }

    public function articoli()
    {
        return $this->hasMany(Components\Articolo::class, 'id_promemoria');
    }

    public function righe()
    {
        return $this->hasMany(Components\Riga::class, 'id_promemoria');
    }

    public function sconti()
    {
        return $this->hasMany(Components\Sconto::class, 'id_promemoria');
    }

    public function descrizioni()
    {
        return $this->hasMany(Components\Descrizione::class, 'id_promemoria');
    }

    public function getModuleAttribute()
    {
        // TODO: Implement getModuleAttribute() method.
    }

    public function getReferenceName()
    {
        // TODO: Implement getReferenceName() method.
    }

    public function getReferenceNumber()
    {
        // TODO: Implement getReferenceNumber() method.
    }

    public function getReferenceDate()
    {
        // TODO: Implement getReferenceDate() method.
    }

    public function getReference()
    {
        // TODO: Implement getReference() method.
    }
}
