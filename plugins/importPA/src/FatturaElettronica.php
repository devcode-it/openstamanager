<?php

namespace Plugins\ImportPA;

use Modules\Fatture\Fattura;
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

    public function __construct($content)
    {
        $xml = simplexml_load_string($content, "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($xml);
        $array = json_decode($json, true);

        $this->xml = $array;
    }

    public function getFattura()
    {
        return $this->fattura;
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
            return $id_anagrafica;
        }

        $ragione_sociale = $xml['DatiAnagrafici']['Anagrafica']['Denominazione'] ?: $xml['DatiAnagrafici']['Anagrafica']['Nome'].' '.$xml['DatiAnagrafici']['Anagrafica']['Cognome'];
        $anagrafica = Anagrafica::create([
            'ragione_sociale' => $ragione_sociale,
            'tipologie' => [
                TipoAnagrafica::where('descrizione', 'Fornitore')->first()->id
            ],
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

    public function saveRighe($articoli)
    {
        $righe = $this->getRighe();
    }

    public function getRighe()
    {
        return $this->getBody()['DatiBeniServizi']['DettaglioLinee'];
    }

    public static function existsFattura($id_anagrafica, $data, $numero, $id_tipo)
    {
        return database()->fetchOne('SELECT `id` FROM `co_documenti` WHERE idanagrafica = '.prepare($id_anagrafica) .' AND idtipodocumento = '.prepare($id_tipo).' AND data = '.prepare($data).' AND numero = '.prepare($numero));
    }

    public function saveAllegati($directory)
    {
        $allegati = $this->getBody()['Allegati'];

        if (!isset($allegati[0])) {
            $allegati = [$allegati];
        }

        foreach ($allegati as $allegato) {
            $content = base64_decode($allegato['Attachment']);
            $filename = $directory.'/'.$allegato['NomeAttachment'].'.'.strtolower($allegato['FormatoAttachment']);

            file_put_contents($filename, $content);

            Uploads::register([
                'original' => $allegato['NomeAttachment'],
                'category' => tr('Fattura elettronica'),
                'id_module' => Modules::get('Fatture di acquisto')['id'],
                'id_record' => $this->fattura->id,
            ]);
        }
    }

    /**
     * Registra la fattura elettronica come fattura del gestionale.
     *
     * @param int $id_segment
     * @return int
     */
    public function saveFattura($id_segment)
    {
        $id_anagrafica = static::createAnagrafica($this->getHeader()['CedentePrestatore']);

        $dati_generali = $this->getBody()['DatiGenerali']['DatiGeneraliDocumento'];
        $data = $dati_generali['Data'];
        $numero = $dati_generali['Numero'];

        $tipo = empty($this->getBody()['DatiGenerali']['DatiTrasporto']) ? TipoFattura::where('descrizione', 'Fattura immediata di acquisto') : TipoFattura::where('descrizione', 'Fattura accompagnatoria di acquisto');
        $id_tipo = $tipo->first()->id;

        $result = self::existsFattura($id_anagrafica, $data, $numero, $id_tipo);
        // Fattura già inserita
        if (!empty($result)) {
            $this->fattura =  Fattura::find($result['id']);

            return $result['id'];
        }

        $fattura = Fattura::create([
            'idanagrafica' => $id_anagrafica,
            'data' => $data,
            'id_segment' => $id_segment,
            'tipo' => $id_tipo,
        ]);

        $fattura->numero = $numero;

        $stato_documento = StatoFattura::where('descrizione', 'Emessa')->first();
        $fattura->stato()->associate($stato_documento);
        $fattura->save();

        $this->fattura = $fattura;

        return $fattura->id;
    }
}
