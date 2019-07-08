<?php

namespace Modules\TipiIntervento;

use Common\Model;
use Modules\Anagrafiche\Anagrafica;

class Tipo extends Model
{
    public $incrementing = false;
    protected $table = 'in_tipiintervento';
    protected $primaryKey = 'idtipointervento';

    /**
     * Crea un nuovo tipo di intervento.
     *
     * @param string $codice
     * @param string $descrizione
     * @param string $tempo_standard
     *
     * @return self
     */
    public static function build($codice, $descrizione, $tempo_standard = null)
    {
        $model = parent::build();

        $model->idtipointervento = $codice;
        $model->descrizione = $descrizione;
        $model->tempo_standard = $tempo_standard;

        // Salvataggio delle informazioni
        $model->save();

        $model->fixTecnici();

        return $model;
    }

    public function fixTecnici()
    {
        // Fix per le relazioni con i tecnici
        $tecnici = Anagrafica::fromTipo('Tecnico')->get();
        foreach ($tecnici as $tecnico) {
            Anagrafica::fixTecnico($tecnico);
        }
    }

    /**
     * Restituisce l'identificativo.
     *
     * @return string
     */
    public function getIdAttribute()
    {
        return $this->idtipointervento;
    }

    /**
     * Imposta il tempo stamdard per il tipo di intervento.
     *
     * @param string $value
     */
    public function setTempoStandardAttribute($value)
    {
        $result = round(($value / 2.5), 1) * 2.5;

        $this->attributes['tempo_standard'] = $result;
    }

    public function preventivi()
    {
        return $this->hasMany(Preventivo::class, 'idtipointervento');
    }

    public function interventi()
    {
        return $this->hasMany(Intervento::class, 'idtipointervento');
    }
}
