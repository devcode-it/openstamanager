<?php

namespace Modules\Fatture;

use Auth;
use Carbon\Carbon;
use Common\Components\Description;
use Common\Document;
use Modules\Anagrafiche\Anagrafica;
use Modules\Fatture\Components\Riga;
use Modules\Pagamenti\Pagamento;
use Modules\PrimaNota\Movimento;
use Modules\RitenuteContributi\RitenutaContributi;
use Modules\Scadenzario\Scadenza;
use Plugins\DichiarazioniIntento\Dichiarazione;
use Plugins\ExportFE\FatturaElettronica;
use Traits\RecordTrait;
use Util\Generator;

class Fattura extends Document
{
    use RecordTrait;

    protected $table = 'co_documenti';

    protected $casts = [
        'bollo' => 'float',
    ];

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
    public static function build(Anagrafica $anagrafica, Tipo $tipo_documento, $data, $id_segment)
    {
        $model = parent::build();

        $user = Auth::user();

        $stato_documento = Stato::where('descrizione', 'Bozza')->first();
        $direzione = $tipo_documento->dir;

        $database = database();

        if ($direzione == 'entrata') {
            $id_conto = setting('Conto predefinito fatture di vendita');
            $conto = 'vendite';
        } else {
            $id_conto = setting('Conto predefinito fatture di acquisto');
            $conto = 'acquisti';
        }

        $model->anagrafica()->associate($anagrafica);
        $model->tipo()->associate($tipo_documento);
        $model->stato()->associate($stato_documento);

        $model->save();

        // Salvataggio delle informazioni
        $model->data = $data;
        $model->data_registrazione = $data;
        $model->data_competenza = $data;
        $model->id_segment = $id_segment;

        $model->idconto = $id_conto;

        // Imposto, come sede aziendale, la prima sede disponibile come utente
        if ($direzione == 'entrata') {
            $model->idsede_destinazione = $user->sedi[0];
        } else {
            $model->idsede_partenza = $user->sedi[0];
        }
        $model->addebita_bollo = setting('Addebita marca da bollo al cliente');

        $id_ritenuta_contributi = ($tipo_documento->dir == 'entrata') ? setting('Ritenuta contributi') : null;
        $model->id_ritenuta_contributi = $id_ritenuta_contributi ?: null;

        // Tipo di pagamento e banca predefinite dall'anagrafica
        $id_pagamento = $database->fetchOne('SELECT id FROM co_pagamenti WHERE id = :id_pagamento', [
            ':id_pagamento' => $anagrafica['idpagamento_'.$conto],
        ])['id'];
        $id_banca = $anagrafica['idbanca_'.$conto];

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

        if (!empty($id_pagamento)) {
            $model->idpagamento = $id_pagamento;
        }
        if (!empty($id_banca)) {
            $model->idbanca = $id_banca;
        }

        // Split Payment
        $split_payment = $anagrafica->split_payment;
        if (!empty($split_payment)) {
            $model->split_payment = $split_payment;
        }

        // Dichiarazione d'Intento
        $now = new Carbon();
        $dichiarazione = $anagrafica->dichiarazioni()
            ->where('massimale', '>', 'totale')
            ->where('data_inizio', '<', $now)
            ->where('data_fine', '>', $now)
            ->first();
        if (!empty($dichiarazione)) {
            $model->dichiarazione()->associate($dichiarazione);

            $model->note = tr("Operazione non imponibile come da vostra dichiarazione d'intento nr _PROT_ del _PROT_DATE_ emessa in data _RELEASE_DATE_, da noi registrata al nr _ID_ del _DATE_", [
                '_PROT_' => $dichiarazione->numero_protocollo,
                '_PROT_DATE_' => $dichiarazione->data_protocollo,
                '_RELEASE_DATE_' => $dichiarazione->data_emissione,
                '_ID_' => $dichiarazione->id,
                '_DATE_' => $dichiarazione->data,
            ]).'.';
        }

        $model->save();

        return $model;
    }

    // Attributi Eloquent

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

            if (!empty($previous)) {
                $this->numero_esterno = static::getNextNumeroSecondario($data, $direzione, $value);
            }
        }
    }

    /**
     * Restituisce il nome del modulo a cui l'oggetto è collegato.
     *
     * @return string
     */
    public function getModuleAttribute()
    {
        return $this->direzione == 'entrata' ? 'Fatture di vendita' : 'Fatture di acquisto';
    }

    public function getDirezioneAttribute()
    {
        return $this->tipo->dir;
    }

    // Calcoli

    /**
     * Calcola il netto a pagare della fattura.
     *
     * @return float
     */
    public function getNettoAttribute()
    {
        return $this->calcola('netto');
    }

    /**
     * Calcola la rivalsa INPS totale della fattura.
     *
     * @return float
     */
    public function getRivalsaINPSAttribute()
    {
        return $this->calcola('rivalsa_inps');
    }

    /**
     * Calcola l'IVA totale della fattura.
     *
     * @return float
     */
    public function getIvaAttribute()
    {
        return $this->calcola('iva', 'iva_rivalsa_inps');
    }

    /**
     * Calcola l'iva della rivalsa INPS totale della fattura.
     *
     * @return float
     */
    public function getIvaRivalsaINPSAttribute()
    {
        return $this->calcola('iva_rivalsa_inps');
    }

    /**
     * Calcola la ritenuta d'acconto totale della fattura.
     *
     * @return float
     */
    public function getRitenutaAccontoAttribute()
    {
        return $this->calcola('ritenuta_acconto');
    }

    public function getTotaleRitenutaContributiAttribute()
    {
        return $this->calcola('ritenuta_contributi');
    }

    /**
     * Restituisce i dati aggiuntivi per la fattura elettronica dell'elemento.
     *
     * @return array
     */
    public function getDatiAggiuntiviFEAttribute()
    {
        $result = json_decode($this->attributes['dati_aggiuntivi_fe'], true);

        return (array) $result;
    }

    /**
     * Imposta i dati aggiuntivi per la fattura elettronica dell'elemento.
     */
    public function setDatiAggiuntiviFEAttribute($values)
    {
        $values = (array) $values;
        $dati = array_deep_clean($values);

        $this->attributes['dati_aggiuntivi_fe'] = json_encode($dati);
    }

    // Relazioni Eloquent

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

    public function pagamento()
    {
        return $this->belongsTo(Pagamento::class, 'idpagamento');
    }

    public function dichiarazione()
    {
        return $this->belongsTo(Dichiarazione::class, 'id_dichiarazione_intento');
    }

    public function statoFE()
    {
        return $this->belongsTo(StatoFE::class, 'codice_stato_fe');
    }

    public function articoli()
    {
        return $this->hasMany(Components\Articolo::class, 'iddocumento');
    }

    public function righe()
    {
        return $this->hasMany(Components\Riga::class, 'iddocumento');
    }

    public function sconti()
    {
        return $this->hasMany(Components\Sconto::class, 'iddocumento');
    }

    public function descrizioni()
    {
        return $this->hasMany(Components\Descrizione::class, 'iddocumento');
    }

    public function ritenutaContributi()
    {
        return $this->belongsTo(RitenutaContributi::class, 'id_ritenuta_contributi');
    }

    public function rigaBollo()
    {
        return $this->hasOne(Components\Riga::class, 'iddocumento')->where('id', $this->id_riga_bollo);
    }

    public function scadenze()
    {
        return $this->hasMany(Scadenza::class, 'iddocumento');
    }

    public function movimentiContabili()
    {
        return $this->hasMany(Movimento::class, 'iddocumento')->where('primanota', 1);
    }

    // Metodi generali

    public function triggerComponent(Description $trigger)
    {
        parent::triggerComponent($trigger);

        // Correzione del totale della dichiarazione d'intento
        $dichiarazione = $this->dichiarazione;
        if (!empty($dichiarazione)) {
            $dichiarazione->fixTotale();
            $dichiarazione->save();
        }
    }

    /**
     * Restituisce i contenuti della fattura elettronica relativa al documento.
     *
     * @return false|string
     */
    public function getXML()
    {
        if (empty($this->progressivo_invio) && $this->module == 'Fatture di acquisto') {
            $fe = new FatturaElettronica($this->id);

            return $fe->toXML();
        }

        $file = $this->uploads()->where('name', 'Fattura Elettronica')->first();

        return file_get_contents($file->filepath);
    }

    /**
     * Controlla se la fattura di acquisto è elettronica.
     *
     * @return bool
     */
    public function isFE()
    {
        $file = $this->uploads()->where('name', 'Fattura Elettronica')->first();

        return !empty($this->progressivo_invio) and file_exists($file->filepath);
    }

    /**
     * Registra le scadenze della fattura elettronica collegata al documento.
     *
     * @param bool $is_pagato
     *
     * @return bool
     */
    public function registraScadenzeFE($is_pagato = false)
    {
        $xml = \Util\XML::read($this->getXML());

        $pagamenti = $xml['FatturaElettronicaBody']['DatiPagamento']['DettaglioPagamento'];
        if (!empty($pagamenti)) {
            $rate = isset($pagamenti[0]) ? $pagamenti : [$pagamenti];

            foreach ($rate as $rata) {
                $scadenza = $rata['DataScadenzaPagamento'] ?: $this->data;
                $importo = ($this->isNota()) ? $rata['ImportoPagamento'] : -$rata['ImportoPagamento'];

                self::registraScadenza($this, $importo, $scadenza, $is_pagato);
            }
        }

        return !empty($pagamenti);
    }

    /**
     * Registra le scadenze tradizionali del gestionale.
     *
     * @param bool $is_pagato
     */
    public function registraScadenzeTradizionali($is_pagato = false)
    {
        $rate = $this->pagamento->calcola($this->netto, $this->data);
        $direzione = $this->tipo->dir;

        foreach ($rate as $rata) {
            $scadenza = $rata['scadenza'];
            $importo = $direzione == 'uscita' ? -$rata['importo'] : $rata['importo'];

            self::registraScadenza($this, $importo, $scadenza, $is_pagato);
        }
    }

    /**
     * Registra una specifica scadenza nel database.
     *
     * @param Fattura $fattura
     * @param float   $importo
     * @param string  $data_scadenza
     * @param bool    $is_pagato
     * @param string  $type
     */
    public static function registraScadenza(Fattura $fattura, $importo, $data_scadenza, $is_pagato, $type = 'fattura')
    {
        $numero = $fattura->numero_esterno ?: $fattura->numero;
        $descrizione = $fattura->tipo->descrizione.' numero '.$numero;

        $scadenza = Scadenza::build($descrizione, $importo, $data_scadenza, $type, $is_pagato);

        $scadenza->documento()->associate($fattura);
        $scadenza->data_emissione = $fattura->data;

        $scadenza->save();
    }

    /**
     * Registra le scadenze della fattura.
     *
     * @param bool $is_pagato
     * @param bool $ignora_fe
     */
    public function registraScadenze($is_pagato = false, $ignora_fe = false)
    {
        $this->rimuoviScadenze();

        if (!$ignora_fe && $this->module == 'Fatture di acquisto' && $this->isFE()) {
            $scadenze_fe = $this->registraScadenzeFE($is_pagato);
        }

        if (empty($scadenze_fe)) {
            $this->registraScadenzeTradizionali($is_pagato);
        }

        $direzione = $this->tipo->dir;
        $ritenuta_acconto = $this->ritenuta_acconto;

        // Se c'è una ritenuta d'acconto, la aggiungo allo scadenzario
        if ($direzione == 'uscita' && $ritenuta_acconto > 0) {
            $data = $this->data;
            $scadenza = date('Y-m', strtotime($data.' +1 month')).'-15';
            $importo = -$ritenuta_acconto;

            self::registraScadenza($this, $importo, $scadenza, $is_pagato, 'ritenutaacconto');
        }
    }

    /**
     * Elimina le scadenze della fattura.
     */
    public function rimuoviScadenze()
    {
        database()->delete('co_scadenziario', ['iddocumento' => $this->id]);
    }

    /**
     * Salva la fattura, impostando i campi dipendenti dai singoli parametri.
     *
     * @param array $options
     *
     * @return bool
     */
    public function save(array $options = [])
    {
        // Fix dei campi statici
        $this->manageRigaMarcaDaBollo();

        $this->attributes['ritenutaacconto'] = $this->ritenuta_acconto;
        $this->attributes['iva_rivalsainps'] = $this->iva_rivalsa_inps;
        $this->attributes['rivalsainps'] = $this->rivalsa_inps;
        $this->attributes['ritenutaacconto'] = $this->ritenuta_acconto;

        // Informazioni sul cambio dei valori
        $stato_precedente = Stato::find($this->original['idstatodocumento']);
        $dichiarazione_precedente = Dichiarazione::find($this->original['id_dichiarazione_intento']);

        // Generazione numero fattura se non presente
        if ($stato_precedente->descrizione == 'Bozza' && $this->stato['descrizione'] == 'Emessa' && empty($this->numero_esterno)) {
            $this->numero_esterno = self::getNextNumeroSecondario($this->data, $this->direzione, $this->id_segment);
        }

        // Salvataggio effettivo
        $result = parent::save($options);

        // Operazioni al cambiamento di stato
        if (
            in_array($stato_precedente->descrizione, ['Bozza', 'Annullata'])
            && !in_array($this->stato['descrizione'], ['Bozza', 'Annullata'])
        ) {
            // Registrazione scadenze
            $this->registraScadenze($this->stato['descrizione'] == 'Pagato');

            // Registrazione movimenti
            aggiungi_movimento($this->id, $this->direzione);
        } elseif (in_array($this->stato['descrizione'], ['Bozza', 'Annullata'])) {
            $this->rimuoviScadenze();

            elimina_movimenti($this->id, 1);
            elimina_movimenti($this->id);
        }

        // Operazioni sulla dichiarazione d'intento
        if (!empty($dichiarazione_precedente) && $dichiarazione_precedente->id != $this->id_dichiarazione_intento) {
            // Correzione dichiarazione precedente
            $dichiarazione_precedente->fixTotale();
            $dichiarazione_precedente->save();

            // Correzione nuova dichiarazione
            $dichiarazione = Dichiarazione::find($this->id_dichiarazione_intento);
            if (!empty($dichiarazione)) {
                $dichiarazione->fixTotale();
                $dichiarazione->save();
            }
        }

        return $result;
    }

    public function delete()
    {
        $result = parent::delete();

        $this->rimuoviScadenze();
        elimina_movimenti($this->id);

        return $result;
    }

    /**
     * Restituisce l'elenco delle note di credito collegate.
     *
     * @return iterable
     */
    public function getNoteDiAccredito()
    {
        return self::where('ref_documento', $this->id)->get();
    }

    /**
     * Restituisce l'elenco delle note di credito collegate.
     *
     * @return self
     */
    public function getFatturaOriginale()
    {
        return self::find($this->ref_documento);
    }

    /**
     * Controlla se la fattura è una nota di credito.
     *
     * @return bool
     */
    public function isNota()
    {
        return $this->tipo->reversed == 1;
    }

    /**
     * Restituisce i dati bancari in base al pagamento.
     *
     * @return array
     */
    public function getBanca()
    {
        $result = [];
        $riba = database()->fetchOne('SELECT riba FROM co_pagamenti WHERE id ='.prepare($this->idpagamento));

        if ($riba['riba'] == 1) {
            $result = database()->fetchOne('SELECT codiceiban, appoggiobancario, bic FROM an_anagrafiche WHERE idanagrafica ='.prepare($this->idanagrafica));
        } else {
            $result = database()->fetchOne('SELECT iban AS codiceiban, nome AS appoggiobancario, bic FROM co_banche WHERE id='.prepare($this->idbanca));
        }

        return $result;
    }

    /**
     * Metodo per calcolare automaticamente il bollo da applicare al documento.
     *
     * @return float
     */
    public function getBollo()
    {
        if (isset($this->bollo)) {
            return $this->bollo;
        }

        $righe_bollo = $this->getRighe()->filter(function ($item, $key) {
            return $item->aliquota != null && in_array($item->aliquota->codice_natura_fe, ['N1', 'N2', 'N3', 'N4']);
        });
        $importo_righe_bollo = $righe_bollo->sum('netto');

        // Leggo la marca da bollo se c'è e se il netto a pagare supera la soglia
        $bollo = ($this->direzione == 'uscita') ? $this->bollo : setting('Importo marca da bollo');

        $marca_da_bollo = 0;
        if (abs($bollo) > 0 && abs($importo_righe_bollo) > setting("Soglia minima per l'applicazione della marca da bollo")) {
            $marca_da_bollo = $bollo;
        }

        // Se l'importo è negativo può essere una nota di credito, quindi cambio segno alla marca da bollo
        $marca_da_bollo = abs($marca_da_bollo);

        return $marca_da_bollo;
    }

    /**
     * Metodo per aggiornare ed eventualemente aggiungere la marca da bollo al documento.
     */
    public function manageRigaMarcaDaBollo()
    {
        $riga = $this->rigaBollo;

        $addebita_bollo = $this->addebita_bollo;
        $marca_da_bollo = $this->getBollo();

        // Rimozione riga bollo se nullo
        if (empty($addebita_bollo) || empty($marca_da_bollo)) {
            if (!empty($riga)) {
                $this->id_riga_bollo = null;

                $riga->delete();
            }

            return;
        }

        // Creazione riga bollo se non presente
        if (empty($riga)) {
            $riga = Components\Riga::build($this);
            $riga->save();

            $this->id_riga_bollo = $riga->id;
        }

        $riga->prezzo_unitario_vendita = $marca_da_bollo;
        $riga->qta = 1;
        $riga->descrizione = setting('Descrizione addebito bollo');
        $riga->id_iva = setting('Iva da applicare su marca da bollo');
        $riga->idconto = setting('Conto predefinito per la marca da bollo');

        $riga->save();
    }

    // Metodi statici

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

        // Recupero maschera per questo segmento
        $maschera = Generator::getMaschera($id_segment);

        $ultimo = Generator::getPreviousFrom($maschera, 'co_documenti', 'numero', [
            'YEAR(data) = '.prepare(date('Y', strtotime($data))),
            'id_segment = '.prepare($id_segment),
        ]);
        $numero = Generator::generate($maschera, $ultimo, 1, Generator::dateToPattern($data));

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

        // Recupero maschera per questo segmento
        $maschera = Generator::getMaschera($id_segment);

        $ultimo = Generator::getPreviousFrom($maschera, 'co_documenti', 'numero_esterno', [
            'YEAR(data) = '.prepare(date('Y', strtotime($data))),
            'id_segment = '.prepare($id_segment),
        ], $data);
        $numero = Generator::generate($maschera, $ultimo, 1, Generator::dateToPattern($data));

        return $numero;
    }
}
