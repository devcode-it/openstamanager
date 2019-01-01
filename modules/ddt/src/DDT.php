<?php

namespace Modules\DDT;

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
    public static function make(Anagrafica $anagrafica, Tipo $tipo_documento, $data)
    {
        $model = parent::make();

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
        return $this->tipo->dir == 'entrata' ? 'Ddt di vendita' : 'DDT di acquisto';
    }

    public function updateSconto()
    {
        // Aggiornamento sconto
        aggiorna_sconto([
            'parent' => 'dt_ddt',
            'row' => 'dt_righe_ddt',
        ], [
            'parent' => 'id',
            'row' => 'idddt',
        ], $this->id);
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

    public function descrizioni()
    {
        return $this->hasMany(Components\Descrizione::class, 'idddt');
    }

    public function scontoGlobale()
    {
        return $this->hasOne(Components\Sconto::class, 'idddt');
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
        $database = database();

        $rs = $database->fetchOne("SELECT IFNULL(MAX(numero), '0') AS max_numero FROM dt_ddt WHERE YEAR(data) = :year AND idtipoddt IN(SELECT id FROM dt_tipiddt WHERE dir = :direction) ORDER BY CAST(numero AS UNSIGNED) DESC", [
            ':year' => date('Y', strtotime($data)),
            ':direction' => $direzione,
        ]);

        return intval($rs['max_numero']) + 1;
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

        $database = database();

        $maschera = setting('Formato numero secondario ddt');

        $ultimo_ddt = $database->fetchOne('SELECT numero_esterno FROM dt_ddt WHERE YEAR(data) = :year AND idtipoddt IN (SELECT id FROM dt_tipiddt WHERE dir = :direction) '.Generator::getMascheraOrder($maschera, 'numero_esterno'), [
            ':year' => date('Y', strtotime($data)),
            ':direction' => $direzione,
        ]);

        $numero_esterno = Generator::generate($maschera, $ultimo_ddt['numero_esterno'], 1, Generator::dateToPattern($data));

        return $numero_esterno;
    }
}
