<?php

namespace Plugins\ImportFE;

use Modules;
use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\Nazione;
use Modules\Anagrafiche\Tipo as TipoAnagrafica;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Fatture\Components\Articolo;
use Modules\Fatture\Components\Riga;
use Modules\Fatture\Fattura;
use Modules\Fatture\Stato as StatoFattura;
use Modules\Fatture\Tipo as TipoFattura;
use UnexpectedValueException;
use Uploads;
use Util\XML;

/**
 * Classe per la gestione della fatturazione elettronica in XML.
 *
 * @since 2.4.2
 */
class FatturaElettronica
{
    protected static $directory = null;

    /** @var array Percorso del file XML */
    protected $file = null;

    /** @var array XML della fattura */
    protected $xml = null;

    /** @var Fattura Fattura collegata */
    protected $fattura = null;

    public function __construct($name)
    {
        $this->file = static::getImportDirectory().'/'.$name;
        $this->xml = XML::readFile($this->file);

        // Individuazione fattura pre-esistente
        $dati_generali = $this->getBody()['DatiGenerali']['DatiGeneraliDocumento'];
        $data = $dati_generali['Data'];
        $numero = $dati_generali['Numero'];
        $progressivo_invio = $this->getHeader()['DatiTrasmissione']['ProgressivoInvio'];

        $fattura = Fattura::where([
            'progressivo_invio' => $progressivo_invio,
            'numero_esterno' => $numero,
            'data' => $data,
        ])->first();

        if (!empty($fattura)) {
            $this->delete();

            throw new UnexpectedValueException();
        }
    }

    public static function getImportDirectory()
    {
        if (!isset(self::$directory)) {
            $module = Modules::get('Fatture di acquisto');

            $plugin = $module->plugins->first(function ($value, $key) {
                return $value->name = 'Fatturazione Elettronica';
            });

            self::$directory = DOCROOT.'/'.$plugin->upload_directory;
        }

        return self::$directory;
    }

    public static function store($filename, $content)
    {
        $directory = static::getImportDirectory();
        $file = $directory.'/'.$filename;

        directory($directory);
        file_put_contents($file, $content);

        return $filename;
    }

    public static function isValid($name)
    {
        try {
            new static($name);

            return true;
        } catch (UnexpectedValueException $e) {
            return false;
        }
    }

    public function getHeader()
    {
        return $this->xml['FatturaElettronicaHeader'];
    }

    public function getBody()
    {
        return $this->xml['FatturaElettronicaBody'];
    }

    public static function createAnagrafica($xml, $type = 'Fornitore')
    {
        $database = database();

        $where = [];

        $partita_iva = $xml['DatiAnagrafici']['IdFiscaleIVA']['IdCodice'];
        if (!empty($partita_iva)) {
            $where[] = '`piva` = '.prepare($partita_iva);
        }

        $codice_fiscale = $xml['DatiAnagrafici']['CodiceFiscale'];
        if (!empty($codice_fiscale)) {
            $where[] = '`codice_fiscale` = '.prepare($codice_fiscale);
        }

        $id_anagrafica = $database->fetchOne('SELECT `an_anagrafiche`.`idanagrafica` FROM `an_anagrafiche`
        INNER JOIN `an_tipianagrafiche_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `an_tipianagrafiche_anagrafiche`.`idanagrafica`
        INNER JOIN `an_tipianagrafiche` ON `an_tipianagrafiche`.`idtipoanagrafica` = `an_tipianagrafiche_anagrafiche`.`idtipoanagrafica`
        WHERE `an_tipianagrafiche`.`descrizione` = '.prepare($type).' AND ('.implode(' OR ', $where).')')['idanagrafica'];
        if (!empty($id_anagrafica)) {
            return Anagrafica::find($id_anagrafica);
        }

        $ragione_sociale = $xml['DatiAnagrafici']['Anagrafica']['Denominazione'] ?: $xml['DatiAnagrafici']['Anagrafica']['Nome'].' '.$xml['DatiAnagrafici']['Anagrafica']['Cognome'];
        $anagrafica = Anagrafica::build($ragione_sociale, [
            TipoAnagrafica::where('descrizione', 'Fornitore')->first()->id,
        ]);

        // Informazioni sull'anagrafica
        $REA = $xml['IscrizioneREA'];
        if (!empty($REA)) {
            if (!empty($REA['Ufficio']) and !empty($REA['NumeroREA'])) {
                $anagrafica->codicerea = $REA['Ufficio'].'-'.$REA['NumeroREA'];
            }

            if (!empty($REA['CapitaleSociale'])) {
                $anagrafica->capitale_sociale = $REA['CapitaleSociale'];
            }
        }

        $anagrafica->save();

        // Informazioni sulla sede
        $info = $xml['Sede'];
        $sede = $anagrafica->sedeLegale;

        if (!empty($partita_iva)) {
            $sede->partita_iva = $partita_iva;
        }

        if (!empty($codice_fiscale)) {
            $sede->codice_fiscale = $codice_fiscale;
        }

        $sede->indirizzo = $info['Indirizzo'];
        $sede->cap = $info['CAP'];
        $sede->citta = $info['Comune'];
        if (!empty($info['Provincia'])) {
            $sede->provincia = $info['Provincia'];
        }
        $sede->nazione()->associate(Nazione::where('iso2', $info['Nazione'])->first());

        $contatti = $xml['Contatti'];
        if (!empty($contatti)) {
            if (!empty($contatti['Telefono'])) {
                $sede->telefono = $contatti['Telefono'];
            }

            if (!empty($contatti['Fax'])) {
                $sede->fax = $contatti['Fax'];
            }

            if (!empty($contatti['email'])) {
                $sede->email = $contatti['email'];
            }
        }
        $sede->save();

        return $anagrafica;
    }

    public function getRighe()
    {
        $result = $this->getBody()['DatiBeniServizi']['DettaglioLinee'];

        $result = isset($result[0]) ? $result : [$result];

        return $result;
    }

    public function saveRighe($articoli, $iva, $conto, $movimentazione = true)
    {
        $righe = $this->getRighe();
        $fattura = $this->getFattura();

        foreach ($righe as $key => $riga) {
            $articolo = ArticoloOriginale::find($articoli[$key]);

            $riga['PrezzoUnitario'] = floatval($riga['PrezzoUnitario']);
            $riga['Quantita'] = floatval($riga['Quantita']);

            if (!empty($articolo)) {
                $obj = Articolo::build($fattura, $articolo);

                $obj->movimentazione($movimentazione);
            } else {
                $obj = Riga::build($fattura);
            }

            $obj->descrizione = $riga['Descrizione'];
            $obj->id_iva = $iva[$key];
            $obj->idconto = $conto[$key];

            // Nel caso il prezzo sia negativo viene gestito attraverso l'inversione della quantità (come per le note di credito)
            // TODO: per migliorare la visualizzazione, sarebbe da lasciare negativo il prezzo e invertire gli sconti.
            $prezzo = $riga['PrezzoUnitario'];
            $prezzo = $riga['PrezzoUnitario'] < 0 ? -$prezzo : $prezzo;
            $qta = $riga['Quantita'] ?: 1;
            $qta = $riga['PrezzoUnitario'] < 0 ? -$qta : $qta;

            // Prezzo e quantità
            $obj->prezzo_unitario_vendita = $prezzo;
            $obj->qta = $qta;

            if (!empty($riga['UnitaMisura'])) {
                $obj->um = $riga['UnitaMisura'];
            }

            // Sconti e maggiorazioni
            $sconti = $riga['ScontoMaggiorazione'];
            if (!empty($sconti)) {
                $sconti = $sconti[0] ? $sconti : [$sconti];
                $tipo = !empty($sconti[0]['Percentuale']) ? 'PRC' : 'UNT';

                $lista = [];
                foreach ($sconti as $sconto) {
                    $unitario = $sconto['Percentuale'] ?: $sconto['Importo'];

                    // Sconto o Maggiorazione
                    $lista[] = ($sconto['Tipo'] == 'SC') ? $unitario : -$unitario;
                }

                if ($tipo == 'PRC') {
                    $elenco = implode('+', $lista);
                    $sconto = calcola_sconto([
                        'sconto' => $elenco,
                        'prezzo' => $obj->prezzo_unitario_vendita,
                        'tipo' => 'PRC',
                        'qta' => $obj->qta,
                    ]);

                    /*
                     * Trasformazione di sconti multipli in sconto percentuale combinato.
                     * Esempio: 40% + 30% è uno sconto del 42%.
                     */
                    $sconto_unitario = $sconto * 100 / $obj->imponibile;
                } else {
                    $sconto_unitario = sum($lista);
                }

                $obj->sconto_unitario = $sconto_unitario;
                $obj->tipo_sconto = $tipo;
            }

            $obj->save();
        }

        // Arrotondamenti differenti nella fattura XML
        $totali_righe = array_column($righe, 'PrezzoTotale');
        $totale_righe = sum($totali_righe);

        $dati_generali = $this->getBody()['DatiGenerali']['DatiGeneraliDocumento'];
        $totale_documento = $dati_generali['ImportoTotaleDocumento'];

        $diff = $totale_documento ? $totale_documento - $fattura->totale : $totale_righe - $fattura->imponibile_scontato;
        if (!empty($diff)) {
            // Rimozione dell?IVA calcolata automaticamente dal gestionale
            $iva_arrotondamento = database()->fetchOne('SELECT * FROM co_iva WHERE id='.prepare($iva[0]));
            $diff = $diff * 100 / (100 + $iva_arrotondamento['percentuale']);

            $obj = Riga::build($fattura);

            $obj->descrizione = tr('Arrotondamento calcolato in automatico');
            $obj->id_iva = $iva[0];
            $obj->idconto = $conto[0];
            $obj->prezzo_unitario_vendita = round($diff, 4);
            $obj->qta = 1;

            $obj->save();
        }
    }

    public function getAllegati()
    {
        $result = $this->getBody()['Allegati'];

        $result = isset($result[0]) ? $result : [$result];

        return array_clean($result);
    }

    public function saveAllegati()
    {
        $allegati = $this->getAllegati();

        $module = Modules::get('Fatture di acquisto');

        $info = [
            'category' => tr('Fattura Elettronica'),
            'id_module' => $module->id,
            'id_record' => $this->fattura->id,
        ];

        foreach ($allegati as $allegato) {
            $content = base64_decode($allegato['Attachment']);

            $extension = '';
            if (!empty($allegato['FormatoAttachment'])) {
                $extension = '.'.strtolower($allegato['FormatoAttachment']);
            }

            $original = $allegato['NomeAttachment'].$extension;
            $filename = Uploads::getName($original, [
                'id_module' => $module['id'],
            ]);

            file_put_contents($module->upload_directory.'/'.$filename, $content);

            Uploads::register(array_merge($info, [
                'filename' => $filename,
                'original' => $original,
            ]));
        }

        // Registrazione XML come allegato
        $filename = Uploads::upload($this->file, array_merge($info, [
            'name' => tr('Fattura Elettronica'),
            'original' => basename($this->file),
        ]));
    }

    public function getFattura()
    {
        return $this->fattura;
    }

    /**
     * Registra la fattura elettronica come fattura del gestionale.
     *
     * @return int
     */
    public function saveFattura($id_pagamento, $id_sezionale, $id_tipo)
    {
        $anagrafica = static::createAnagrafica($this->getHeader()['CedentePrestatore']);

        $dati_generali = $this->getBody()['DatiGenerali']['DatiGeneraliDocumento'];
        $data = $dati_generali['Data'];

        $numero_esterno = $dati_generali['Numero'];
        $progressivo_invio = $this->getHeader()['DatiTrasmissione']['ProgressivoInvio'];

        $tipo = TipoFattura::where('id', $id_tipo)->first();

        $fattura = Fattura::build($anagrafica, $tipo, $data, $id_sezionale);
        $this->fattura = $fattura;

        $fattura->progressivo_invio = $progressivo_invio;
        $fattura->numero_esterno = $numero_esterno;
        $fattura->idpagamento = $id_pagamento;
		
		//Per il destinatario, la data di ricezione della fattura assume grande rilievo ai fini IVA, poiché determina la decorrenza dei termini per poter esercitare il diritto alla detrazione.
		//La data di ricezione della fattura è contenuta all’interno della “ricevuta di consegna” visibile al trasmittente della stessa.
		$fattura->data_ricezione = $dati_generali['Data'];

        $stato_documento = StatoFattura::where('descrizione', 'Emessa')->first();
        $fattura->stato()->associate($stato_documento);

        // Nodo ScontoMaggiorazione generale per il documento ignorato (issue #542)
        /*
        // Sconto globale
        $sconto = $dati_generali['ScontoMaggiorazione'];
        if (!empty($sconto)) {
            $tipo = !empty($sconto['Percentuale']) ? 'PRC' : 'UNT';
            $unitario = $sconto['Percentuale'] ?: $sconto['Importo'];

            $unitario = ($sconto['Tipo'] == 'SC') ? $unitario : -$unitario;

            $fattura->sconto_globale = $unitario;
            $fattura->tipo_sconto_globale = $tipo;
        }*/

        // Ritenuta d'Acconto
        $ritenuta = $dati_generali['DatiRitenuta'];
        if (!empty($ritenuta)) {
            $percentuale = $ritenuta['AliquotaRitenuta'];
            $importo = $ritenuta['ImportoRitenuta'];

            // TODO: salvare in fattura
        }

        $causali = $dati_generali['Causale'];
        if (count($causali) > 0) {
            foreach ($causali as $causale) {
                $note .= $causale;
            }
            $fattura->note = $note;
        }

        // Bollo
        $bollo = $dati_generali['DatiBollo'];
        if (!empty($bollo)) {
            $fattura->bollo = $bollo['ImportoBollo'];
        }

        $fattura->save();

        return $fattura->id;
    }

    public function delete()
    {
        delete($this->file);
    }
}
