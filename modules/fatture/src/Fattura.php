<?php

namespace Modules\Fatture;

use Common\Model;
use Util\Generator;
use Modules\Anagrafiche\Anagrafica;

class Fattura extends Model
{
    protected $table = 'co_documenti';

    /** @var array Conti rilevanti della fattura */
    protected $conti = [];

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
     * Calcola l'imponibile della fattura (totale delle righe - sconto).
     *
     * @return float
     */
    public function getImponibile()
    {
        if (!isset($this->conti['imponibile'])) {
            $result = database()->fetchOne('SELECT SUM(co_righe_documenti.subtotale - co_righe_documenti.sconto) AS imponibile FROM co_righe_documenti WHERE iddocumento = :id', [
                ':id' => $this->id,
            ]);

            $this->conti['imponibile'] = $result['imponibile'];
        }

        return $this->conti['imponibile'];

        return $result['imponibile'];
    }

    /**
     * Calcola il totale della fattura (imponibile + iva).
     *
     * @return float
     */
    public function getTotale()
    {
        if (!isset($this->conti['totale'])) {
            // Sommo l'iva di ogni riga al totale
            $iva = $this->righe()->sum('iva');

            $iva_rivalsainps = database()->fetchArray('SELECT SUM(rivalsainps / 100 * percentuale) AS iva_rivalsainps FROM co_righe_documenti INNER JOIN co_iva ON co_iva.id = co_righe_documenti.idiva WHERE iddocumento = :id', [
                ':id' => $this->id,
            ])['iva_rivalsainps'];

            $totale = sum([
                $this->getImponibile(),
                $this->rivalsainps,
                $iva,
                $iva_rivalsainps,
            ]);

            $this->conti['totale'] = $totale;
        }

        return $this->conti['totale'];
    }

    /**
     * Calcola il netto a pagare della fattura (totale - ritenute - bolli).
     *
     * @return float
     */
    public function getNetto($iddocumento)
    {
        if (!isset($this->conti['netto'])) {
            $netto = sum([
                $this->getTotale(),
                $this->bollo,
                -$this->ritenutaacconto,
            ]);

            $this->conti['netto'] = $netto;
        }

        return $this->conti['netto'];
    }

    /**
     * Calcola l'iva detraibile della fattura.
     *
     * @return float
     */
    public function getIvaDetraibile()
    {
        if (!isset($this->conti['iva_detraibile'])) {
            $this->conti['iva_detraibile'] = $this->righe()->sum('iva') - $this->getIvaIndetraibile();
        }

        return $this->conti['iva_detraibile'];
    }

    /**
     * Calcolo l'iva indetraibile della fattura.
     *
     * @return float
     */
    public function getIvaIndetraibile()
    {
        if (!isset($this->conti['iva_indetraibile'])) {
            $this->conti['iva_indetraibile'] = $this->righe()->sum('iva_indetraibile');
        }

        return $this->conti['iva_indetraibile'];
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
