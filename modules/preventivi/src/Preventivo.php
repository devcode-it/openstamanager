<?php

namespace Modules\Preventivi;

use Common\Document;
use Modules\Anagrafiche\Anagrafica;
use Modules\Interventi\TipoSessione;
use Traits\RecordTrait;
use Util\Generator;
use Carbon\Carbon;

class Preventivo extends Document
{
    use RecordTrait;

    protected $table = 'co_preventivi';

    /**
     * Crea un nuovo preventivo.
     *
     * @param Anagrafica $anagrafica
     * @param TipoIntervento $tipo_sessione
     * @param string     $nome
     *
     * @return self
     */
    public static function build(Anagrafica $anagrafica, TipoSessione $tipo_sessione, $nome)
    {
        $model = parent::build();

        $stato_documento = Stato::where('descrizione', 'Bozza')->first();

        $id_anagrafica = $anagrafica->id;
        $id_agente = $anagrafica->idagente;
        $id_pagamento = $anagrafica->idpagamento_vendite;

        $costo_orario = $tipo_sessione['costo_orario'];
        $costo_diritto_chiamata = $tipo_sessione['costo_diritto_chiamata'];

        $id_iva = setting('Iva predefinita');
        if (empty($id_pagamento)) {
            $id_pagamento = setting('Tipo di pagamento predefinito');
        }

        $model->anagrafica()->associate($anagrafica);
        $model->stato()->associate($stato_documento);
        $model->tipoSessione()->associate($tipo_sessione);

        $model->numero = static::getNextNumero();

        // Salvataggio delle informazioni
        $model->nome = $nome;
        $model->data_bozza = Carbon::now();
        $model->data_conclusione = Carbon::now()->addMonth();

        if (!empty($id_agente)) {
            $model->idagente = $id_agente;
        }

        if (!empty($id_iva)) {
            $model->idiva = $id_iva;
        }
        if (!empty($id_pagamento)) {
            $model->idpagamento = $id_pagamento;
        }

        $model->save();

        // Gestione delle revisioni
        $model->master_revision = $model->id;
        $model->default_revision = 1;

        $model->save();

        return $model;
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

    public function updateSconto()
    {
        // Aggiornamento sconto
        aggiorna_sconto([
            'parent' => 'co_preventivi',
            'row' => 'co_righe_preventivi',
        ], [
            'parent' => 'id',
            'row' => 'idpreventivo',
        ], $this->id);
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

    public function descrizioni()
    {
        return $this->hasMany(Components\Descrizione::class, 'idpreventivo');
    }

    public function scontoGlobale()
    {
        return $this->hasOne(Components\Sconto::class, 'idpreventivo');
    }

    // Metodi statici

    /**
     * Calcola il nuovo numero di preventivo.
     *
     * @return string
     */
    public static function getNextNumero()
    {
        $database = database();

        $maschera = setting('Formato codice preventivi');

        $ultimo_preventivo = $database->fetchOne('SELECT numero FROM co_preventivi WHERE numero=(SELECT MAX(CAST(numero AS SIGNED)) FROM co_preventivi) AND numero LIKE('.prepare(Generator::complete($maschera)).') ORDER BY numero DESC');

        $numero = Generator::generate($maschera, $ultimo_preventivo['numero']);

        if (!is_numeric($numero)) {
            $ultimo_preventivo = $database->fetchOne('SELECT numero FROM co_preventivi WHERE numero LIKE('.prepare(Generator::complete($maschera)).') ORDER BY numero DESC');

            $numero = Generator::generate($maschera, $ultimo_preventivo['numero']);
        }

        return $numero;
    }
}
