<?php

namespace Plugins\PianificazioneFatturazione;

use Common\Document;
use Modules\Contratti\Contratto;
use Modules\Fatture\Fattura;

class Pianificazione extends Document
{
    protected $table = 'co_fatturazione_contratti';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'data_scadenza',
    ];

    /**
     * Crea un nuovo promemoria.
     *
     * @param string $data_richiesta
     *
     * @return self
     */
    public static function build(Contratto $contratto, $data_scadenza)
    {
        $model = parent::build();

        $model->contratto()->associate($contratto);

        $model->data_scadenza = $data_scadenza;

        // Salvataggio delle informazioni
        $model->save();

        return $model;
    }

    public function getPluginAttribute()
    {
        return 'Pianificazione fatturazione';
    }

    public function getDirezioneAttribute()
    {
        return 'entrata';
    }

    public function anagrafica()
    {
        return $this->contratto->anagrafica();
    }

    public function contratto()
    {
        return $this->belongsTo(Contratto::class, 'idcontratto');
    }

    public function fattura()
    {
        return $this->belongsTo(Fattura::class, 'iddocumento');
    }

    public function getRighe()
    {
        $righe = $this->contratto->getRighe();
        $pianificazioni = $this->contratto->pianificazioni;
        $numero_righe = $righe->count() / $pianificazioni->count();

        $p = $this;
        $index = $pianificazioni->search(function ($item) use ($p) {
            return $item->id == $p->id;
        });

        return $righe->splice($index * $numero_righe, $numero_righe);
    }

    public function articoli()
    {
        return $this->contratto->articoli();
    }

    public function righe()
    {
        return $this->contratto->righe();
    }

    public function sconti()
    {
        return $this->contratto->sconti();
    }

    public function descrizioni()
    {
        return $this->contratto->descrizioni();
    }
}
