<?php

namespace Modules\Articoli;

use Illuminate\Database\Eloquent\Model;
use Modules\Articoli\Articolo as ArticoloIntervento;

class Articolo extends Model
{
    protected $table = 'mg_articoli';

    /**
     * Funzione per inserire i movimenti di magazzino.
     */
    public function movimenta($qta, $descrizone = null, $data = null, $manuale = false, $array = [])
    {
        $this->registra($qta, $descrizone, $data, $manuale, $array);

        if ($this->servizio == 0) {
            $this->qta += $qta;

            $this->save();
        }

        return true;
    }

    /**
     * Funzione per registrare i movimenti di magazzino.
     */
    public function registra($qta, $descrizone = null, $data = null, $manuale = false, $array = [])
    {
        if (empty($qta)) {
            return false;
        }

        // Movimento il magazzino solo se l'articolo non è un servizio
        if ($this->servizio == 0) {
            // Registrazione della movimentazione
            database()->insert('mg_movimenti', array_merge($array, [
                'idarticolo' => $this->id,
                'qta' => $qta,
                'movimento' => $descrizone,
                'data' => $data,
                'manuale' => $manuale,
            ]));
        }

        return true;
    }

    public function articolo()
    {
        return $this->hasMany(ArticoloIntervento::class, 'idarticolo');
    }
}
