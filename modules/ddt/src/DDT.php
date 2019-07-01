<?php

namespace Modules\DDT;

use Auth;
use Common\Document;
use Modules\Anagrafiche\Anagrafica;
use Traits\RecordTrait;
use Util\Generator;

class DDT extends Document
{
    use RecordTrait;

    protected $table = 'dt_ddt';

    /**
     * Crea un nuovo ddt.
     *
     * @param Anagrafica $anagrafica
     * @param Tipo       $tipo_documento
     * @param string     $data
     *
     * @return self
     */
    public static function build(Anagrafica $anagrafica, Tipo $tipo_documento, $data)
    {
        $model = parent::build();

        $user = Auth::user();

        $stato_documento = Stato::where('descrizione', 'Bozza')->first();

        $id_anagrafica = $anagrafica->id;
        $direzione = $tipo_documento->dir;

        $database = database();

        if ($direzione == 'entrata') {
            $conto = 'vendite';
        } else {
            $conto = 'acquisti';
        }

        // Tipo di pagamento e banca predefinite dall'anagrafica
        $id_pagamento = $database->fetchOne('SELECT id FROM co_pagamenti WHERE id = :id_pagamento', [
            ':id_pagamento' => $anagrafica['idpagamento_'.$conto],
        ])['id'];

        // Se il ddt è un ddt cliente e non è stato associato un pagamento predefinito al cliente leggo il pagamento dalle impostazioni
        if ($direzione == 'entrata' && empty($id_pagamento)) {
            $id_pagamento = setting('Tipo di pagamento predefinito');
        }

        $model->anagrafica()->associate($anagrafica);
        $model->tipo()->associate($tipo_documento);
        $model->stato()->associate($stato_documento);

        $model->save();

        // Salvataggio delle informazioni
        $model->data = $data;

        if (!empty($id_pagamento)) {
            $model->idpagamento = $id_pagamento;
        }

        $model->numero = static::getNextNumero($data, $direzione);
        $model->numero_esterno = static::getNextNumeroSecondario($data, $direzione);

        // Imposto, come sede aziendale, la prima sede disponibile come utente
        if ($direzione == 'entrata') {
            $model->idsede_partenza = $user->sedi[0];
        } else {
            $model->idsede_destinazione = $user->sedi[0];
        }

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
        return $this->direzione == 'entrata' ? 'Ddt di vendita' : 'DDT di acquisto';
    }

    public function getDirezioneAttribute()
    {
        return $this->tipo->dir;
    }

    public function anagrafica()
    {
        return $this->belongsTo(Anagrafica::class, 'idanagrafica');
    }

    public function tipo()
    {
        return $this->belongsTo(Tipo::class, 'idtipoddt');
    }

    public function stato()
    {
        return $this->belongsTo(Stato::class, 'idstatoddt');
    }

    public function articoli()
    {
        return $this->hasMany(Components\Articolo::class, 'idddt');
    }

    public function righe()
    {
        return $this->hasMany(Components\Riga::class, 'idddt');
    }

    public function sconti()
    {
        return $this->hasMany(Components\Sconto::class, 'idddt');
    }

    public function descrizioni()
    {
        return $this->hasMany(Components\Descrizione::class, 'idddt');
    }

    // Metodi statici

    /**
     * Calcola il nuovo numero di ddt.
     *
     * @param string $data
     * @param string $direzione
     * @param int    $id_segment
     *
     * @return string
     */
    public static function getNextNumero($data, $direzione)
    {
        $maschera = '#';

        $ultimo = Generator::getPreviousFrom($maschera, 'dt_ddt', 'numero', [
            'YEAR(data) = '.prepare(date('Y', strtotime($data))),
            'idtipoddt IN (SELECT id FROM dt_tipiddt WHERE dir = '.prepare($direzione).')',
        ]);
        $numero = Generator::generate($maschera, $ultimo);

        return $numero;
    }

    /**
     * Calcola il nuovo numero secondario di ddt.
     *
     * @param string $data
     * @param string $direzione
     *
     * @return string
     */
    public static function getNextNumeroSecondario($data, $direzione)
    {
        if ($direzione == 'uscita') {
            return '';
        }

        $maschera = setting('Formato numero secondario ddt');

        $ultimo = Generator::getPreviousFrom($maschera, 'dt_ddt', 'numero_esterno', [
            'YEAR(data) = '.prepare(date('Y', strtotime($data))),
            'idtipoddt IN (SELECT id FROM dt_tipiddt WHERE dir = '.prepare($direzione).')',
        ]);
        $numero = Generator::generate($maschera, $ultimo, 1, Generator::dateToPattern($data));

        return $numero;
    }
}
