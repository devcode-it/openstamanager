<?php

namespace Modules\Interventi;

use Common\Document;
use Modules\Anagrafiche\Anagrafica;
use Modules\Interventi\Components\Articolo;
use Modules\Interventi\Components\Riga;
use Modules\Preventivi\Preventivo;
use Util\Generator;

class Intervento extends Document
{
    protected $table = 'in_interventi';

    /**
     * Crea un nuovo preventivo.
     *
     * @param Anagrafica   $anagrafica
     * @param TipoSessione $tipo_sessione
     * @param Stato        $stato
     * @param string       $data_richiesta
     *
     * @return self
     */
    public static function build(Anagrafica $anagrafica, TipoSessione $tipo_sessione, Stato $stato, $data_richiesta)
    {
        $model = parent::build();

        $model->anagrafica()->associate($anagrafica);
        $model->stato()->associate($stato);
        $model->tipoSessione()->associate($tipo_sessione);

        $model->codice = static::getNextCodice();
        $model->data_richiesta = $data_richiesta;

        $model->save();

        return $model;
    }

    public function anagrafica()
    {
        return $this->belongsTo(Anagrafica::class, 'idanagrafica');
    }

    public function preventivo()
    {
        return $this->hasOne(Preventivo::class, 'id_preventivo');
    }

    public function stato()
    {
        return $this->belongsTo(Stato::class, 'idstatointervento');
    }

    public function tipoSessione()
    {
        return $this->belongsTo(TipoSessione::class, 'idtipointervento');
    }

    public function articoli()
    {
        return $this->hasMany(Articolo::class, 'idintervento');
    }

    public function righe()
    {
        return $this->hasMany(Riga::class, 'idintervento');
    }

    public function descrizioni()
    {
        return null;
    }

    public function scontoGlobale()
    {
        return null;
    }

    // Metodi statici

    /**
     * Calcola il nuovo codice di intervento.
     *
     * @return string
     */
    public static function getNextCodice()
    {
        $maschera = setting('Formato codice intervento');

        $ultimo = Generator::getPreviousFrom($maschera, 'in_interventi', 'codice');
        $numero = Generator::generate($maschera, $ultimo);

        return $numero;
    }
}
