<?php

/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Modules\Fatture;

use Carbon\Carbon;
use Common\Components\Component;
use Common\Document;
use Illuminate\Database\Eloquent\Builder;
use Models\Upload;
use Modules\Anagrafiche\Anagrafica;
use Modules\Banche\Banca;
use Modules\Fatture\Gestori\Bollo as GestoreBollo;
use Modules\Fatture\Gestori\Movimenti as GestoreMovimenti;
use Modules\Fatture\Gestori\Scadenze as GestoreScadenze;
use Modules\Pagamenti\Pagamento;
use Modules\PrimaNota\Movimento;
use Modules\RitenuteContributi\RitenutaContributi;
use Modules\Scadenzario\Scadenza;
use Plugins\DichiarazioniIntento\Dichiarazione;
use Plugins\ExportFE\FatturaElettronica;
use Traits\RecordTrait;
use Traits\ReferenceTrait;
use Util\Generator;

class Fattura extends Document
{
    use RecordTrait;
    use ReferenceTrait;

    protected $table = 'co_documenti';

    protected $casts = [
        'bollo' => 'float',
        'peso' => 'float',
        'volume' => 'float',

        'sconto_finale' => 'float',
        'sconto_finale_percentuale' => 'float',
    ];

    protected $with = [
        'tipo',
    ];

    protected $dates = [
        'data',
    ];

    /** @var GestoreScadenze */
    protected $gestoreScadenze;
    /** @var GestoreMovimenti */
    protected $gestoreMovimenti;
    /** @var GestoreBollo */
    protected $gestoreBollo;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Inizializzazione gestori relativi
        $this->gestoreScadenze = new GestoreScadenze($this);
        $this->gestoreMovimenti = new GestoreMovimenti($this);
        $this->gestoreBollo = new GestoreBollo($this);
    }

    /**
     * Crea una nuova fattura.
     *
     * @param string $data
     * @param int    $id_segment
     *
     * @return self
     */
    public static function build(Anagrafica $anagrafica, Tipo $tipo_documento, $data, $id_segment, $numero_esterno = null, $data_registrazione = null)
    {
        $model = new static();

        $user = \Auth::user();
        $database = database();

        // Individuazione dello stato predefinito per il documento
        $id_stato_attuale_documento = Stato::where('name', 'Bozza')->first()->id;
        $direzione = $tipo_documento->dir;

        // Conto predefinito sulla base del flusso di denaro
        if ($direzione == 'entrata') {
            $id_conto = setting('Conto predefinito fatture di vendita');
            $conto = 'vendite';
        } else {
            $id_conto = setting('Conto predefinito fatture di acquisto');
            $conto = 'acquisti';
        }

        // Informazioni di base
        $model->anagrafica()->associate($anagrafica);
        $model->tipo()->associate($tipo_documento);
        $model->stato()->associate($id_stato_attuale_documento);

        // Salvataggio delle informazioni
        $model->data = $data;
        $model->data_registrazione = $data_registrazione ?: $data;
        $model->data_competenza = $data;
        $model->id_segment = $id_segment;
        $model->idconto = $id_conto;
        if ($numero_esterno) {
            $model->numero_esterno = $numero_esterno;
        }
        $model->idagente = $anagrafica->idagente ?: '';

        // Sede aziendale scelta tra le sedi disponibili per l'utente
        $id_sede = $user->sedi[0];
        if ($direzione == 'entrata') {
            $model->idsede_destinazione = $id_sede;
        } else {
            $model->idsede_partenza = $id_sede;
        }

        // Ritenuta contributi predefinita
        $id_ritenuta_contributi = ($tipo_documento->dir == 'entrata') ? setting('Ritenuta previdenziale predefinita') : null;
        $model->id_ritenuta_contributi = $id_ritenuta_contributi ?: null;

        // Banca predefinita per l'anagrafica controparte
        // $model->id_banca_controparte = ;

        // Tipo di pagamento dall'anagrafica controparte
        $id_pagamento = $anagrafica['idpagamento_'.$conto];

        // Per Fatture di Vendita senza pagamento predefinito per il Cliente, si utilizza il pagamento predefinito dalle Impostazioni
        if ($direzione == 'entrata' && empty($id_pagamento)) {
            $id_pagamento = setting('Tipo di pagamento predefinito');
        }

        // Salvataggio del pagamento
        if (!empty($id_pagamento)) {
            $model->idpagamento = $id_pagamento;
        }

        // Banca predefinita per l'azienda, con ricerca della banca impostata per il pagamento
        $id_banca_azienda = $anagrafica->{'idbanca_'.$conto};
        if (empty($id_banca_azienda)) {
            $azienda = Anagrafica::find(setting('Azienda predefinita'));
            $id_banca_azienda = $database->fetchOne('SELECT `id` FROM `co_banche` WHERE `deleted_at` IS NULL AND `predefined`=1 AND `id_pianodeiconti3` = (SELECT idconto_'.$conto.' FROM `co_pagamenti` WHERE `id` = :id_pagamento) AND `id_anagrafica` = :id_anagrafica', [
                ':id_pagamento' => $id_pagamento,
                ':id_anagrafica' => $azienda->id,
            ])['id'];
            if (empty($id_banca_azienda)) {
                $id_banca_azienda = $database->fetchOne('SELECT `id` FROM `co_banche` WHERE `deleted_at` IS NULL AND `id_pianodeiconti3` = (SELECT idconto_'.$conto.' FROM `co_pagamenti` WHERE `id` = :id_pagamento) AND `id_anagrafica` = :id_anagrafica', [
                    ':id_pagamento' => $id_pagamento,
                    ':id_anagrafica' => $azienda->id,
                ])['id'];
            }
            if (empty($id_banca_azienda)) {
                $id_banca_azienda = $azienda->{'idbanca_'.$conto};
            }
        }

        $model->id_banca_azienda = $id_banca_azienda;

        // Gestione dello Split Payment sulla base dell'anagrafica Controparte
        $split_payment = $anagrafica->split_payment;
        if (!empty($split_payment)) {
            $model->split_payment = $split_payment;
        }

        // Gestione della Dichiarazione d'Intento associata all'anagrafica Controparte
        $now = new Carbon();
        $dichiarazione = $anagrafica->dichiarazioni()
            ->where('massimale', '>', 'totale')
            ->where('data_inizio', '<', $now)
            ->where('data_fine', '>', $now)
            ->where('id', $anagrafica->id_dichiarazione_intento_default)
            ->first();
        $dichiarazione = $dichiarazione ?: $anagrafica->dichiarazioni()
            ->where('massimale', '>', 'totale')
            ->where('data_inizio', '<', $now)
            ->where('data_fine', '>', $now)
            ->first();

        $notes = [];
        if (!empty($dichiarazione)) {
            $model->dichiarazione()->associate($dichiarazione);

            // Registrazione dell'operazione nelle note
            $notes[] = tr("Operazione non imponibile come da vostra dichiarazione d'intento nr _PROT_ del _PROT_DATE_ emessa in data _RELEASE_DATE_", [
                '_PROT_' => $dichiarazione->numero_protocollo,
                '_PROT_DATE_' => \Translator::dateToLocale($dichiarazione->data_protocollo),
                '_RELEASE_DATE_' => \Translator::dateToLocale($dichiarazione->data_emissione),
            ]).'.';
        }

        $dicitura_fissa = database()->selectOne('zz_segments', 'dicitura_fissa', ['id' => $id_segment])['dicitura_fissa'];
        if ($dicitura_fissa) {
            $notes[] = $dicitura_fissa;
        }

        $model->note = implode("\n", $notes);

        if ($tipo_documento->getTranslation('title') == 'Fattura accompagnatoria di vendita') {
            $porto = database()->fetchOne('SELECT `id` FROM `dt_porto` WHERE `predefined` = 1')['id'];
            $model->idporto = $porto ?: '';
            $causalet = database()->fetchOne('SELECT `id` FROM `dt_causalet` WHERE `predefined` = 1')['id'];
            $model->idcausalet = $causalet ?: '';
            $spedizione = database()->fetchOne('SELECT `id` FROM `dt_spedizione` WHERE `predefined` = 1')['id'];
            $model->idspedizione = $spedizione ?: '';
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
            $data = $this->data_competenza;

            $this->numero = static::getNextNumero($data, $direzione, $value);

            if ($this->stato->getTranslation('title') == 'Bozza') {
                $this->numero_esterno = '';
            } elseif (!empty($previous)) {
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

    /**
     * Restituisce il peso calcolato sulla base degli articoli del documento.
     *
     * @return float
     */
    public function getPesoCalcolatoAttribute()
    {
        $righe = $this->getRighe();

        $peso_lordo = $righe->sum(fn ($item) => $item->isArticolo() ? $item->articolo->peso_lordo * $item->qta : 0);

        return $peso_lordo;
    }

    /**
     * Restituisce il volume calcolato sulla base degli articoli del documento.
     *
     * @return float
     */
    public function getVolumeCalcolatoAttribute()
    {
        $righe = $this->getRighe();

        $volume = $righe->sum(fn ($item) => $item->isArticolo() ? $item->articolo->volume * $item->qta : 0);

        return $volume;
    }

    // Calcoli

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
     * Calcola l'IVA INDETRAIBILE totale della fattura.
     *
     * @return float
     */
    public function getIvaIndetraibileAttribute()
    {
        return $this->calcola('iva_indetraibile');
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
        return $this->getRigheContabili()->sum('ritenuta_acconto');
    }

    public function getTotaleRitenutaContributiAttribute()
    {
        return $this->getRigheContabili()->sum('ritenuta_contributi');
    }

    /**
     * Restituisce i dati aggiuntivi per la fattura elettronica dell'elemento.
     *
     * @return array
     */
    public function getDatiAggiuntiviFEAttribute()
    {
        $result = ($this->attributes['dati_aggiuntivi_fe'] ? json_decode((string) $this->attributes['dati_aggiuntivi_fe'], true) : '');

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

    public function rigaSpeseIncasso()
    {
        return $this->hasOne(Components\Riga::class, 'iddocumento')->where('id', $this->id_riga_spese_incasso);
    }

    public function scadenze()
    {
        return $this->hasMany(Scadenza::class, 'iddocumento')->orderBy('scadenza');
    }

    public function movimentiContabili()
    {
        return $this->hasMany(Movimento::class, 'iddocumento')->where('primanota', 1);
    }

    // Metodi generali

    public function triggerComponent(Component $trigger)
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

        $file = $this->uploads()->where('name', '=', 'Fattura Elettronica')->first();
        if (empty($file)) {
            throw new \InvalidArgumentException('Fattura Elettronica non trovata');
        }

        return $file->get_contents();
    }

    /**
     * Restituisce le ricevute della fattura elettronica relativa al documento.
     *
     * @return iterable
     */
    public function getRicevute()
    {
        $nome = 'Ricevuta';

        return $this->uploads()->filter(fn ($item) => str_contains((string) $item->getTranslation('title'), $nome))->sortBy('created_at');
    }

    /**
     * Restituisce la ricevuta principale, impostata attraverso il campo aggiuntivo id_ricevuta_principale.
     *
     * @return Upload|null
     */
    public function getRicevutaPrincipale()
    {
        if (empty($this->id_ricevuta_principale)) {
            return null;
        }

        return $this->getModule()
            ->files($this->id)
            ->where('id', $this->id_ricevuta_principale)
            ->first();
    }

    /**
     * Restituisce la fattura elettronica registrata come allegato.
     *
     * @return Upload|null
     */
    public function getFatturaElettronica()
    {
        return $this->uploads()
            ->where('name', '=', 'Fattura Elettronica')
            ->first();
    }

    /**
     * Controlla se la fattura di acquisto è elettronica.
     *
     * @return bool
     */
    public function isFE()
    {
        $file = $this->getFatturaElettronica();

        return !empty($this->progressivo_invio) && $file->directory && file_exists('files/'.$file->directory);
    }

    /**
     * Registra le scadenze della fattura.
     *
     * @param bool $is_pagato
     * @param bool $ignora_fe
     */
    public function registraScadenze($is_pagato = false, $ignora_fe = false)
    {
        $this->gestoreScadenze->registra($is_pagato, $ignora_fe);
    }

    /**
     * Elimina le scadenze della fattura.
     */
    public function rimuoviScadenze()
    {
        $this->gestoreScadenze->rimuovi();
    }

    /**
     * Salva la fattura, impostando i campi dipendenti dai singoli parametri.
     *
     * @return bool
     */
    public function save(array $options = [])
    {
        // Informazioni sul cambio dei valori
        $id_stato_precedente = $this->original['idstatodocumento'];
        $id_stato_attuale = $this->stato['id'];

        $id_stato_bozza = Stato::where('name', 'Bozza')->first()->id;
        $id_stato_emessa = Stato::where('name', 'Emessa')->first()->id;
        $id_stato_annullata = Stato::where('name', 'Annullata')->first()->id;
        $id_stato_non_valida = Stato::where('name', 'Non valida')->first()->id;
        $id_stato_pagato = Stato::where('name', 'Pagato')->first()->id;

        $dichiarazione_precedente = Dichiarazione::find($this->original['id_dichiarazione_intento']);
        $is_fiscale = $this->isFiscale();

        $this->attributes['ritenutaacconto'] = $this->ritenuta_acconto;
        $this->attributes['iva_rivalsainps'] = $this->iva_rivalsa_inps;
        $this->attributes['rivalsainps'] = $this->rivalsa_inps;
        $this->attributes['ritenutaacconto'] = $this->ritenuta_acconto;

        // Fix dei campi statici
        $this->id_riga_bollo = $this->gestoreBollo->manageRigaMarcaDaBollo();
        $this->id_riga_spese_incasso = $this->manageRigaSpeseIncasso();

        // Generazione numero fattura se non presente (Bozza -> Emessa)
        if ((($id_stato_precedente == $id_stato_bozza && $id_stato_attuale == $id_stato_emessa) or (!$is_fiscale)) && empty($this->numero_esterno)) {
            $this->numero_esterno = self::getNextNumeroSecondario($this->data, $this->direzione, $this->id_segment);
        }

        // Salvataggio effettivo
        parent::save($options);

        // Operazioni al cambiamento di stato
        // Bozza o Annullato -> Stato diverso da Bozza o Annullato
        if (
            (in_array($id_stato_precedente, [$id_stato_bozza, $id_stato_annullata])
            && !in_array($id_stato_attuale, [$id_stato_bozza, $id_stato_annullata, $id_stato_non_valida]))
            || $options[0] == 'forza_emissione'
        ) {
            // Registrazione scadenze
            $this->registraScadenze($id_stato_attuale == $id_stato_pagato);

            // Registrazione movimenti
            $this->gestoreMovimenti->registra();
        } // Stato qualunque -> Bozza o Annullato
        elseif (in_array($id_stato_attuale, [$id_stato_bozza, $id_stato_annullata, $id_stato_non_valida])) {
            // Rimozione delle scadenza
            $this->rimuoviScadenze();

            // Rimozione dei movimenti
            $this->gestoreMovimenti->rimuovi();

            // Rimozione dei movimenti contabili (Prima nota)
            $this->movimentiContabili()->delete();
        }

        if ($this->changes['data_competenza'] && !in_array($id_stato_attuale, [$id_stato_bozza, $id_stato_annullata, $id_stato_non_valida])) {
            $movimenti = Movimento::where('iddocumento', $this->id)->where('primanota', 0)->get();
            foreach ($movimenti as $movimento) {
                $movimento->data = $this->data_competenza;
                $movimento->save();
            }
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

        // Operazioni automatiche per le Fatture Elettroniche
        if ($this->direzione == 'entrata' && $id_stato_precedente == $id_stato_bozza && $id_stato_attuale == $id_stato_emessa) {
            $stato_fe = StatoFE::find($this->codice_stato_fe);
            $abilita_genera = empty($this->codice_stato_fe) || intval($stato_fe['is_generabile']);

            // Generazione automatica della Fattura Elettronica
            $checks = FatturaElettronica::controllaFattura($this);
            $fattura_elettronica = new FatturaElettronica($this->id);
            if ($abilita_genera && empty($checks)) {
                $fattura_elettronica->save();

                if (!$fattura_elettronica->isValid()) {
                    $errors = $fattura_elettronica->getErrors();

                    $result[$this->numero_esterno][0][0] = $errors;
                }
            } elseif (!empty($checks)) {
                // Rimozione eventuale fattura generata erronamente
                // Fix per la modifica di dati interni su fattura già generata
                if ($abilita_genera) {
                    $fattura_elettronica->delete();
                }
                foreach ($checks as $check) {
                    $errors = [];
                    foreach ($check['errors'] as $error) {
                        if (!empty($error)) {
                            $errors = $error;
                        }
                    }
                    if (!empty($errors)) {
                        $result[$this->numero_esterno][$check['name']][$check['link']] = $check['errors'];
                    }
                }
            }
        }

        return $result;
    }

    public function delete()
    {
        $result = parent::delete();

        // Rimozione delle scadenza
        $this->rimuoviScadenze();

        // Rimozione dei movimenti
        $this->gestoreMovimenti->rimuovi();

        // Rimozione dei movimenti contabili (Prima nota)
        $this->movimentiContabili()->delete();

        return $result;
    }

    public function replicate(?array $except = null)
    {
        $new = parent::replicate($except);
        $now = Carbon::now();

        // In fase di duplicazione di una fattura non deve essere calcolato il numero progressivo ma questo deve
        // essere generato in fase di emissione della stessa.
        $new->numero_esterno = '';
        $new->numero = Fattura::getNextNumero($now, $new->direzione, $new->id_segment);

        // Rimozione informazioni di Fattura Elettronica
        $new->hook_send = false;
        $new->codice_stato_fe = null;
        $new->progressivo_invio = null;
        $new->data_stato_fe = null;
        $new->data = $now;
        $new->data_registrazione = $now;
        $new->data_competenza = $now;
        $new->descrizione_ricevuta_fe = null;
        $new->id_ricevuta_principale = null;

        // Spostamento dello stato
        $id_stato_attuale = Stato::where('name', 'Bozza')->first()->id;
        $new->stato()->associate($id_stato_attuale);

        return $new;
    }

    public function manageRigaSpeseIncasso()
    {
        $riga = $this->rigaSpeseIncasso;
        $first_riga_fattura = $this->getRighe()->where('id', '!=', $riga->id)->where('is_descrizione', '0')->first();

        // Elimino la riga se non c'è più la descrizione dell'incasso o se la fattura non ha righe
        if (!$this->pagamento->descrizione_incasso || !$first_riga_fattura) {
            if (!empty($riga)) {
                $riga->delete();
            }

            return null;
        }

        // Creazione riga se non presente
        if (empty($riga)) {
            $riga = Components\Riga::build($this);
        }

        $prezzo_unitario = $this->pagamento->importo_fisso_incasso;
        if ($this->pagamento->importo_percentuale_incasso && ($this->totale - $riga->totale)) {
            $prezzo_unitario += ($this->totale - $riga->totale) * $this->pagamento->importo_percentuale_incasso / 100;
        }

        if ($riga->tipo_sconto == 'PRC') {
            $sconto = $riga->sconto_percentuale ?: 0;
        } else {
            $sconto = $riga->sconto_unitario;
        }

        $riga->qta = 1;
        $riga->descrizione = $this->pagamento->descrizione_incasso;
        $riga->id_iva = $first_riga_fattura->idiva;
        $riga->idconto = setting("Conto predefinito per le spese d'incasso");
        $riga->setPrezzoUnitario($prezzo_unitario, $first_riga_fattura->idiva);
        $riga->setSconto($sconto, $riga->tipo_sconto);
        $riga->save();

        return $riga->id;
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
     * Controlla se la fattura è una nota di credito.
     *
     * @return bool
     */
    public function isAutofattura()
    {
        return in_array($this->tipo->codice_tipo_documento_fe, ['TD16', 'TD17', 'TD18', 'TD19',
            'TD20', 'TD21', 'TD28', ]);
    }

    /**
     * Controlla se la fattura è fiscale.
     *
     * @return bool
     */
    public function isFiscale()
    {
        $result = database()->fetchOne('SELECT `is_fiscale` FROM `zz_segments` WHERE `id` ='.prepare($this->id_segment))['is_fiscale'];

        return $result;
    }

    /**
     * Scope per l'inclusione delle sole fatture con valore contabile.
     *
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeContabile($query)
    {
        return $query->whereHas('stato', function (Builder $query) {
            $query->whereIn('name', ['Emessa', 'Parzialmente pagato', 'Pagato']);
        });
    }

    /**
     * Restituisce i dati bancari in base al pagamento.
     *
     * @return array
     */
    public function getBanca()
    {
        $pagamento = $this->pagamento;

        if ($pagamento->isRiBa()) {
            $banca = Banca::find($this->id_banca_controparte) ?: Banca::where('id_anagrafica', $this->idanagrafica)->where('predefined', 1)->first();
        } else {
            $banca = Banca::find($this->id_banca_azienda);
        }

        return $banca;
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
            'YEAR(data_competenza) = '.prepare(date('Y', strtotime($data))),
            'id_segment = '.prepare($id_segment),
        ]);
        $numero = Generator::generate($maschera, $ultimo, 1, Generator::dateToPattern($data));

        return $numero;
    }

    /**
     * Scope per l'inclusione delle fatture di vendita.
     *
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeVendita($query)
    {
        return $query->whereHas('tipo', function (Builder $query) {
            $query->where('dir', 'entrata');
        });
    }

    /**
     * Scope per l'inclusione delle fatture di acquisto.
     *
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeAcquisto($query)
    {
        return $query->whereHas('tipo', function (Builder $query) {
            $query->where('dir', 'uscita');
        });
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

    // Opzioni di riferimento

    public function getReferenceName()
    {
        return $this->tipo;
    }

    public function getReferenceNumber()
    {
        return $this->numero_esterno ?: $this->numero;
    }

    public function getReferenceDate()
    {
        return $this->data;
    }

    public function getReferenceRagioneSociale()
    {
        return $this->anagrafica->ragione_sociale;
    }

    public function getTotaleCSVAttribute()
    {
        $totale = $this->totale_imponibile + $this->iva + $this->rivalsa_inps + $this->iva_rivalsa_inps;
        if ($this->isNota()) {
            return $totale * (-1);
        } else {
            return $totale;
        }
    }
}
