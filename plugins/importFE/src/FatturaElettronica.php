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

    public function __construct($file)
    {
        $this->file = static::getImportDirectory().'/'.$file;
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

    public static function isValid($file)
    {
        try {
            new static($file);

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
        $sede->indirizzo = $info['Indirizzo'];
        $sede->nazione()->associate(Nazione::where('iso2', $info['Nazione'])->first());
        if (!empty($info['Provincia'])) {
            $sede->provincia = $info['Provincia'];
        }

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

    public function saveRighe($articoli, $iva, $conto)
    {
        $righe = $this->getRighe();

        foreach ($righe as $key => $riga) {
            $articolo = ArticoloOriginale::find($articoli[$key]);

            if (!empty($articolo)) {
                $obj = Articolo::build($this->getFattura(), $articolo);
            } else {
                $obj = Riga::build($this->getFattura());
            }

            $obj->descrizione = $riga['Descrizione'];
            $obj->id_iva = $iva[$key];
            $obj->idconto = $conto[$key];
            $obj->prezzo_unitario_vendita = $riga['PrezzoUnitario'];
            $obj->qta = $riga['Quantita'] ?: 1;

            if (!empty($riga['UnitaMisura'])) {
                $obj->um = $riga['UnitaMisura'];
            }

            $sconto = $riga['ScontoMaggiorazione'];
            if (!empty($sconto)) {
                $tipo = !empty($sconto['Percentuale']) ? 'PRC' : 'EUR';
                $unitario = $sconto['Percentuale'] ?: $sconto['Importo'];

                $unitario = ($sconto['Tipo'] == 'SC') ? $unitario : -$unitario;

                $obj->sconto_unitario = $unitario;
                $obj->tipo_sconto = $tipo;
            }

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
    public function saveFattura($id_pagamento, $id_sezionale)
    {
        $anagrafica = static::createAnagrafica($this->getHeader()['CedentePrestatore']);

        $dati_generali = $this->getBody()['DatiGenerali']['DatiGeneraliDocumento'];
        $data = $dati_generali['Data'];
        $numero_esterno = $dati_generali['Numero'];
        $progressivo_invio = $this->getHeader()['DatiTrasmissione']['ProgressivoInvio'];

        $descrizione_tipo = empty($this->getBody()['DatiGenerali']['DatiTrasporto']) ? 'Fattura immediata di acquisto' : 'Fattura accompagnatoria di acquisto';
        $tipo = TipoFattura::where('descrizione', $descrizione_tipo)->first();

        $fattura = Fattura::build($anagrafica, $tipo, $data, $id_sezionale);
        $this->fattura = $fattura;

        $fattura->progressivo_invio = $progressivo_invio;
        $fattura->numero_esterno = $numero_esterno;
        $fattura->idpagamento = $id_pagamento;

        $stato_documento = StatoFattura::where('descrizione', 'Emessa')->first();
        $fattura->stato()->associate($stato_documento);

        // Sconto globale
        $sconto = $dati_generali['ScontoMaggiorazione'];
        if (!empty($sconto)) {
            $tipo = !empty($sconto['Percentuale']) ? 'PRC' : 'EUR';
            $unitario = $sconto['Percentuale'] ?: $sconto['Importo'];

            $unitario = ($sconto['Tipo'] == 'SC') ? $unitario : -$unitario;

            $fattura->sconto_globale = $unitario;
            $fattura->tipo_sconto_globale = $tipo;
        }

        // Ritenuta d'Acconto
        $ritenuta = $dati_generali['DatiRitenuta'];
        if (!empty($ritenuta)) {
            $percentuale = $ritenuta['AliquotaRitenuta'];
            $importo = $ritenuta['ImportoRitenuta'];

            // TODO: salvare in fattura
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
