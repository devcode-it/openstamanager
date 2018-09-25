<?php

namespace Plugins\ImportPA;

use Modules\Fatture\Fattura;
use Modules\Fatture\Riga;
use Modules\Fatture\Articolo;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Fatture\Stato as StatoFattura;
use Modules\Fatture\Tipo as TipoFattura;
use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\Tipo as TipoAnagrafica;
use Modules\Anagrafiche\Nazione;
use Uploads;
use Modules;

/**
 * Classe per la gestione della fatturazione elettronica in XML.
 *
 * @since 2.4.2
 */
class FatturaElettronica
{
    /** @var array XML della fattura */
    protected $xml = null;

    /** @var Fattura Fattura collegata */
    protected $fattura = null;
    protected $id_sezionale = null;

    public function __construct($content, $id_sezionale)
    {
        $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
        $json = json_encode($xml);
        $array = json_decode($json, true);

        $this->xml = $array;
        $this->id_sezionale = $id_sezionale;

        // Individuazione fattura pre-esistente
        $dati_generali = $this->getBody()['DatiGenerali']['DatiGeneraliDocumento'];
        $data = $dati_generali['Data'];
        $numero = $dati_generali['Numero'];

        $fattura = Fattura::where([
            'id_segment' => $id_sezionale,
            'data' => $data,
            'numero' => $numero,
        ])->first();

        if (!empty($fattura)) {
            throw new \UnexpectedValueException();
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
        $anagrafica = Anagrafica::new($ragione_sociale, [
            TipoAnagrafica::where('descrizione', 'Fornitore')->first()->id,
        ]);

        // Informazioni sull'anagrafica
        $REA = $xml['IscrizioneREA'];
        if (!empty($REA)) {
            $anagrafica->codicerea = $REA['Ufficio'].'-'.$REA['NumeroREA'];

            if (!empty($REA['CapitaleSociale'])) {
                $anagrafica->capitale_sociale = $REA['CapitaleSociale'];
            }
        }

        $anagrafica->save();

        // Informazioni sulla sede
        $info = $xml['Sede'];
        $sede = $anagrafica->sedeLegale();

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

        return $anagrafica->id;
    }

    public function getRighe()
    {
        $result = $this->getBody()['DatiBeniServizi']['DettaglioLinee'];

        $result = isset($result[0]) ? $result : [$result];

        return $result;
    }

    public function saveRighe($articoli, $iva)
    {
        $righe = $this->getRighe();

        foreach ($righe as $key => $riga) {
            $articolo = ArticoloOriginale::find($articoli[$key]);

            if (!empty($articolo)) {
                $obj = Articolo::new($this->getFattura(), $articolo);
            } else {
                $obj = Riga::new($this->getFattura());
            }

            $obj->descrizione = $riga['Descrizione'];
            $obj->setSubtotale($riga['PrezzoUnitario'], $riga['Quantita']);
            /*
            $obj->qta = $riga['Quantita'];
            $obj->prezzo = $riga['PrezzoUnitario'];
            */
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

            $obj->id_iva = $iva[$key];

            $obj->save();
        }
    }

    public function getAllegati()
    {
        $result = $this->getBody()['Allegati'];

        $result = isset($result[0]) ? $result : [$result];

        return $result;
    }

    public function saveAllegati($directory)
    {
        $allegati = $this->getAllegati();

        $module = Modules::get('Fatture di acquisto');

        foreach ($allegati as $allegato) {
            $content = base64_decode($allegato['Attachment']);
            $original = $allegato['NomeAttachment'].'.'.strtolower($allegato['FormatoAttachment']);
            $filename = Uploads::getName($original, [
                'id_module' => $module['id'],
            ]);

            file_put_contents($directory.'/'.$filename, $content);

            Uploads::register([
                'filename' => $filename,
                'original' => $original,
                'category' => tr('Fattura elettronica'),
                'id_module' => $module['id'],
                'id_record' => $this->fattura->id,
            ]);
        }
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
    public function saveFattura($id_pagamento)
    {
        $anagrafica = static::createAnagrafica($this->getHeader()['CedentePrestatore']);

        $dati_generali = $this->getBody()['DatiGenerali']['DatiGeneraliDocumento'];
        $data = $dati_generali['Data'];
        $numero = $dati_generali['Numero'];

        $descrizione_tipo = empty($this->getBody()['DatiGenerali']['DatiTrasporto']) ? 'Fattura immediata di acquisto' : 'Fattura accompagnatoria di acquisto';
        $tipo = TipoFattura::where('descrizione', $descrizione_tipo)->first();

        $fattura = Fattura::new($anagrafica, $tipo, $data, $this->id_sezionale);
        $this->fattura = $fattura;

        $fattura->numero = $numero;
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

        $fattura->save();

        return $fattura->id;
    }
}
