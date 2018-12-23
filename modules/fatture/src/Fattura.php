<?php

namespace Modules\Fatture;

use Common\Document;
use Util\Generator;
use Traits\RecordTrait;
use Modules\Anagrafiche\Anagrafica;

class Fattura extends Document
{
    use RecordTrait;

    protected $table = 'co_documenti';

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'bollo' => 'float',
    ];

    public function getModuleAttribute()
    {
        return $this->tipo->dir == 'entrata' ? 'Fatture di vendita' : 'Fatture di acquisto';
    }

    /**
     * Crea una nuova fattura.
     *
     * @param Anagrafica $anagrafica
     * @param Tipo       $tipo_documento
     * @param string     $data
     * @param int        $id_segment
     *
     * @return self
     */
    public static function make(Anagrafica $anagrafica, Tipo $tipo_documento, $data, $id_segment)
    {
        $model = parent::make();

        $stato_documento = Stato::where('descrizione', 'Bozza')->first();

        $id_anagrafica = $anagrafica->id;
        $direzione = $tipo_documento->dir;

        $database = database();

        if ($direzione == 'entrata') {
            $id_conto = setting('Conto predefinito fatture di vendita');
            $conto = 'vendite';
        } else {
            $id_conto = setting('Conto predefinito fatture di acquisto');
            $conto = 'acquisti';
        }

        // Tipo di pagamento e banca predefinite dall'anagrafica
        $pagamento = $database->fetchOne('SELECT id, (SELECT idbanca_'.$conto.' FROM an_anagrafiche WHERE idanagrafica = ?) AS idbanca FROM co_pagamenti WHERE id = (SELECT idpagamento_'.$conto.' AS pagamento FROM an_anagrafiche WHERE idanagrafica = ?)', [
            $id_anagrafica,
            $id_anagrafica,
        ]);
        $id_pagamento = $pagamento['id'];
        $id_banca = $pagamento['idbanca'];

        // Se la fattura è di vendita e non è stato associato un pagamento predefinito al cliente leggo il pagamento dalle impostazioni
        if ($direzione == 'entrata' && empty($id_pagamento)) {
            $id_pagamento = setting('Tipo di pagamento predefinito');
        }

        // Se non è impostata la banca dell'anagrafica, uso quella del pagamento.
        if (empty($id_banca)) {
            $id_banca = $database->fetchOne('SELECT id FROM co_banche WHERE id_pianodeiconti3 = (SELECT idconto_'.$conto.' FROM co_pagamenti WHERE id = :id_pagamento)', [
                ':id_pagamento' => $id_pagamento,
            ])['id'];
        }

        $id_sede = $database->selectOne('an_anagrafiche', 'idsede_fatturazione', ['idanagrafica' => $id_anagrafica])['idsede_fatturazione'];

        $model->anagrafica()->associate($anagrafica);
        $model->tipo()->associate($tipo_documento);
        $model->stato()->associate($stato_documento);

        $model->save();

        // Salvataggio delle informazioni
        $model->data = $data;
        $model->id_segment = $id_segment;

        $model->idconto = $id_conto;
        $model->idsede = $id_sede;

        if (!empty($id_pagamento)) {
            $model->idpagamento = $id_pagamento;
        }
        if (!empty($id_banca)) {
            $model->idbanca = $id_banca;
        }
        $model->save();

        return $model;
    }

    /**
     * Imposta il sezionale relativo alla fattura e calcola il relativo numero.
     * **Attenzione**: la data deve inserita prima!
     *
     * @param int $value
     */
    public function setIdSegmentAttribute($value)
    {
        $previous = $this->id_segment;

        $this->attributes['id_segment'] = $value;

        // Calcolo dei numeri fattura
        if ($value != $previous) {
            $direzione = $this->tipo->dir;
            $data = $this->data;

            $this->numero = static::getNextNumero($data, $direzione, $value);
            $this->numero_esterno = static::getNextNumeroSecondario($data, $direzione, $value);
        }
    }

    /**
     * Calcola il nuovo numero di fattura.
     *
     * @param string $data
     * @param string $direzione
     * @param int    $id_segment
     *
     * @return string
     */
    public static function getNextNumero($data, $direzione, $id_segment)
    {
        if ($direzione == 'entrata') {
            return '';
        }

        $database = database();

        // Recupero maschera per questo segmento
        $maschera = Generator::getMaschera($id_segment);

        $ultima_fattura = $database->fetchOne('SELECT numero FROM co_documenti WHERE YEAR(data) = :year AND id_segment = :id_segment '.Generator::getMascheraOrder($maschera, 'numero'), [
            ':year' => date('Y', strtotime($data)),
            ':id_segment' => $id_segment,
        ]);

        $numero = Generator::generate($maschera, $ultima_fattura['numero'], 1, Generator::dateToPattern($data));

        return $numero;
    }

    /**
     * Calcola il nuovo numero secondario di fattura.
     *
     * @param string $data
     * @param string $direzione
     * @param int    $id_segment
     *
     * @return string
     */
    public static function getNextNumeroSecondario($data, $direzione, $id_segment)
    {
        if ($direzione == 'uscita') {
            return '';
        }

        $database = database();

        // Recupero maschera per questo segmento
        $maschera = Generator::getMaschera($id_segment);

        $ultima_fattura = $database->fetchOne('SELECT numero_esterno FROM co_documenti WHERE YEAR(data) = :year AND id_segment = :id_segment '.Generator::getMascheraOrder($maschera, 'numero_esterno'), [
            ':year' => date('Y', strtotime($data)),
            ':id_segment' => $id_segment,
        ]);

        $numero_esterno = Generator::generate($maschera, $ultima_fattura['numero_esterno'], 1, Generator::dateToPattern($data));

        return $numero_esterno;
    }

    /**
     * Calcola il netto a pagare della fattura.
     *
     * @return float
     */
    public function getNettoAttribute()
    {
        return parent::getNettoAttribute() + $this->bollo;
    }

    /**
     * Restituisce l'elenco delle note di credito collegate.
     *
     * @return array
     */
    public function getNoteDiAccredito()
    {
        return database()->fetchArray("SELECT co_documenti.id, IF(numero_esterno != '', numero_esterno, numero) AS numero, data FROM co_documenti WHERE idtipodocumento IN (SELECT id FROM co_tipidocumento WHERE reversed = 1) AND ref_documento = :id", [
            ':id' => $this->id,
        ]);
    }

    /**
     * Controlla se la fattura è una nota di credito.
     *
     * @return bool
     */
    public function isNotaDiAccredito()
    {
        return $this->tipo->reversed == 1;
    }

    public function updateSconto()
    {
        // Aggiornamento sconto
        aggiorna_sconto([
            'parent' => 'co_documenti',
            'row' => 'co_righe_documenti',
        ], [
            'parent' => 'id',
            'row' => 'iddocumento',
        ], $this->id);
    }

    public function anagrafica()
    {
        return $this->belongsTo(Anagrafica::class, 'idanagrafica');
    }

    public function tipo()
    {
        return $this->belongsTo(Tipo::class, 'idtipodocumento');
    }

    public function stato()
    {
        return $this->belongsTo(Stato::class, 'idstatodocumento');
    }

    public function articoli()
    {
        return $this->hasMany(Articolo::class, 'iddocumento');
    }

    public function righe()
    {
        return $this->hasMany(Riga::class, 'iddocumento');
    }

    public function descrizioni()
    {
        return $this->hasMany(Descrizione::class, 'iddocumento');
    }

    public function scontoGlobale()
    {
        return $this->hasOne(Sconto::class, 'iddocumento');
    }
}
