<?php

namespace Modules\Fatture;

use Common\Model;
use Util\Generator;
use Modules\Anagrafiche\Anagrafica;

class Fattura extends Model
{
    protected $table = 'co_documenti';

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
     * @param [type] $value
     */
    public function setIdSegmentAttribute($value)
    {
        $previous = $this->id_segment;

        $this->attributes['id_segment'] = $value;

        // Calcolo dei numeri fattura
        if ($value != $previous) {
            $direzione = $this->tipo->dir;
            $data = $this->data;

            $this->numero = static::getNumero($data, $direzione, $value);
            $this->numero_esterno = static::getNumeroSecondario($data, $direzione, $value);
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
    public static function getNumero($data, $direzione, $id_segment)
    {
        $database = database();

        //$maschera = $direzione == 'uscita' ? static::getMaschera($id_segment) : '#';
        // Recupero maschera per questo segmento
        $maschera = static::getMaschera($id_segment);

        $ultima_fattura = $database->fetchOne('SELECT numero FROM co_documenti WHERE YEAR(data) = :year AND id_segment = :id_segment '.static::getMascheraOrder($maschera, 'numero'), [
            ':year' => date('Y', strtotime($data)),
            ':id_segment' => $id_segment,
        ]);

        $numero = Generator::generate($maschera, $ultima_fattura['numero']);

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
    public static function getNumeroSecondario($data, $direzione, $id_segment)
    {
        if ($direzione == 'uscita') {
            return '';
        }

        $database = database();

        // Recupero maschera per questo segmento
        $maschera = static::getMaschera($id_segment);

        $ultima_fattura = $database->fetchOne('SELECT numero_esterno FROM co_documenti WHERE YEAR(data) = :year AND id_segment = :id_segment '.static::getMascheraOrder($maschera, 'numero_esterno'), [
            ':year' => date('Y', strtotime($data)),
            ':id_segment' => $id_segment,
        ]);

        $numero_esterno = Generator::generate($maschera, $ultima_fattura['numero_esterno']);

        return $numero_esterno;
    }

    /**
     * Restituisce la maschera specificata per il segmento indicato.
     *
     * @param int $id_segment
     *
     * @return string
     */
    protected static function getMaschera($id_segment)
    {
        $database = database();

        $maschera = $database->fetchOne('SELECT pattern FROM zz_segments WHERE id = :id_segment', [
            ':id_segment' => $id_segment,
        ])['pattern'];

        return $maschera;
    }

    /**
     * Metodo per l'individuazione del tipo di ordine da impostare per la corretta interpretazione della maschera.
     * Esempi:
     * - maschere con testo iniziale (FT-####-YYYY) necessitano l'ordinamento alfabetico
     * - maschere di soli numeri (####-YYYY) è necessario l'ordinamento numerico forzato.
     *
     * @param string $maschera
     *
     * @return string
     */
    protected static function getMascheraOrder($maschera, $order_by)
    {
        // Estraggo blocchi di caratteri standard
        preg_match('/[#]+/', $maschera, $m1);
        //preg_match('/[Y]+/', $maschera, $m2);

        $pos1 = strpos($maschera, $m1[0]);
        if ($pos1 == 0) {
            $query = 'ORDER BY CAST('.$order_by.' AS UNSIGNED) DESC';
        } else {
            $query = 'ORDER BY '.$order_by.' DESC';
        }

        return $query;
    }

    /**
     * Restituisce la collezione di righe e articoli con valori rilevanti per i conti.
     *
     * @return iterable
     */
    protected function getRighe()
    {
        return $this->righe->merge($this->articoli);
    }

    /**
     * Calcola l'imponibile della fattura.
     *
     * @return float
     */
    public function getImponibile()
    {
        return $this->getRighe()->sum('imponibile');
    }

    /**
     * Calcola lo sconto totale della fattura.
     *
     * @return float
     */
    public function getSconto()
    {
        return $this->getRighe()->sum('sconto');
    }

    /**
     * Calcola l'imponibile scontato della fattura.
     *
     * @return float
     */
    public function getImponibileScontato()
    {
        return $this->getRighe()->sum('imponibile_scontato');
    }

    /**
     * Calcola l'IVA totale della fattura.
     *
     * @return float
     */
    public function getIva()
    {
        return $this->getRighe()->sum('iva');
    }

    /**
     * Calcola la rivalsa INPS totale della fattura.
     *
     * @return float
     */
    public function getRivalsaINPS()
    {
        return $this->getRighe()->sum('rivalsa_inps');
    }

    /**
     * Calcola la ritenuta d'acconto totale della fattura.
     *
     * @return float
     */
    public function getRitenutaAcconto()
    {
        return $this->getRighe()->sum('ritenuta_acconto');
    }

    /**
     * Calcola il totale della fattura.
     *
     * @return float
     */
    public function getTotale()
    {
        return $this->getRighe()->sum('totale');
    }

    /**
     * Calcola il netto a pagare della fattura.
     *
     * @return float
     */
    public function getNetto()
    {
        return $this->getRighe()->sum('netto') + $this->bollo;
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
}
