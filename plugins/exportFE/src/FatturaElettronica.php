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

namespace Plugins\ExportFE;

use FluidXml\FluidXml;
use GuzzleHttp\Client;
use Modules;
use Modules\Anagrafiche\Anagrafica;
use Modules\Fatture\Fattura;
use Modules\Fatture\Gestori\Bollo;
use Prints;
use Translator;
use UnexpectedValueException;
use Uploads;
use Validate;

/**
 * Classe per la gestione della fatturazione elettronica in XML.
 *
 * @since 2.4.2
 */
class FatturaElettronica
{
    /** @var Anagrafica Informazioni sull'anagrafica Azienda */
    protected static $azienda = [];

    /** @var Anagrafica Informazioni sull'anagrafica Cliente del documento */
    protected $cliente = [];

    /** @var Modules\Fatture\Fattura Informazioni sul documento */
    protected $documento = null;

    /** @var Validator Oggetto dedicato alla validazione dell'XML */
    protected $validator = null;

    /** @var array Contratti collegati al documento */
    protected $contratti = [];
    /** @var array Ordini di acquisto collegati al documento */
    protected $ordini = [];
    /** @var array Righe del documento */
    protected $righe = [];

    /** @var array XML della fattura */
    protected $xml = null;

    public function __construct($id_documento)
    {
        // Documento
        $this->documento = Fattura::find($id_documento);

        // Controllo sulla possibilità di creare la fattura elettronica
        // Posso fatturare ai privati utilizzando il codice fiscale
        if ($this->documento->stato->descrizione == 'Bozza') {
            throw new UnexpectedValueException();
        }
    }

    public function __toString()
    {
        return $this->toXML();
    }

    /**
     * @return bool
     */
    public function isGenerated()
    {
        $documento = $this->getDocumento();
        $file = $documento->getFatturaElettronica();

        return !empty($documento['progressivo_invio']) && file_exists(base_dir().'/'.$file->filepath);
    }

    /**
     * Restituisce le informazioni sull'anagrafica azienda.
     *
     * @return Anagrafica
     */
    public static function getAzienda()
    {
        if (empty(static::$azienda)) {
            static::$azienda = Anagrafica::find(setting('Azienda predefinita'));
        }

        return static::$azienda;
    }

    /**
     * Restituisce le informazioni sull'anagrafica cliente legata al documento.
     *
     * @return Anagrafica
     */
    public function getCliente()
    {
        return $this->getDocumento()->anagrafica;
    }

    /**
     * Restituisce le righe del documento.
     *
     * @return array
     */
    public function getRighe()
    {
        if (empty($this->righe)) {
            $this->righe = $this->getDocumento()->getRighe();
        }

        return $this->righe;
    }

    /**
     * Restituisce i contratti collegati al documento (contratti e interventi e ordini).
     *
     * @return array
     */
    public function getContratti()
    {
        if (empty($this->contratti)) {
            $documento = $this->getDocumento();
            $database = database();

            $contratti = $database->fetchArray('SELECT `id_documento_fe` AS id_documento, `num_item`, `codice_cig`, `codice_cup` FROM `co_contratti` INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`idcontratto` = `co_contratti`.`id` WHERE `co_righe_documenti`.`iddocumento` = '.prepare($documento['id']).' AND `id_documento_fe` IS NOT NULL AND `co_righe_documenti`.`idordine` = 0');

            $preventivi = $database->fetchArray('SELECT `id_documento_fe` AS id_documento, `num_item`, `codice_cig`, `codice_cup` FROM `co_preventivi` INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`idpreventivo` = `co_preventivi`.`id` WHERE `co_righe_documenti`.`iddocumento` = '.prepare($documento['id']).' AND `id_documento_fe` IS NOT NULL AND `co_righe_documenti`.`idordine` = 0');

            $interventi = $database->fetchArray('SELECT `id_documento_fe` AS id_documento, `num_item`, `codice_cig`, `codice_cup` FROM `in_interventi` INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`idintervento` = `in_interventi`.`id` WHERE `co_righe_documenti`.`iddocumento` = '.prepare($documento['id']).' AND `id_documento_fe` IS NOT NULL AND `co_righe_documenti`.`idcontratto` = 0 AND `co_righe_documenti`.`idpreventivo` = 0');

            $dati_aggiuntivi = $documento->dati_aggiuntivi_fe;
            $dati = $dati_aggiuntivi['dati_contratto'] ?: [];

            $this->contratti = array_merge($contratti, $preventivi, $interventi, $dati);
        }

        return $this->contratti;
    }

    /**
     * Restituisce gli ordini di acquisto collegati al documento.
     *
     * @return array
     */
    public function getOrdiniAcquisto()
    {
        if (empty($this->ordini)) {
            $documento = $this->getDocumento();
            $database = database();

            $ordini = $database->fetchArray('SELECT `or_ordini`.`numero_cliente` AS id_documento, `or_ordini`.`num_item`, `or_ordini`.`codice_cig`, `or_ordini`.`codice_cup`, `or_ordini`.`codice_commessa`, `or_ordini`.`data_cliente`, `co_righe_documenti`.`order` AS riferimento_linea FROM `or_ordini` INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`idordine` = `or_ordini`.`id` WHERE `co_righe_documenti`.`iddocumento` = '.prepare($documento['id']));

            $dati_aggiuntivi = $documento->dati_aggiuntivi_fe;
            $dati = $dati_aggiuntivi['dati_ordine'] ?: [];

            $this->ordini = array_merge($ordini, $dati);
        }

        return $this->ordini;
    }

    /**
     * Restituisce i ddt collegati al documento.
     *
     * @return array
     */
    public function getDDT()
    {
        if (empty($this->ddt)) {
            $documento = $this->getDocumento();
            $database = database();

            $ddt = $database->fetchArray('SELECT `dt_ddt`.`numero_esterno` AS id_documento, `co_righe_documenti`.`order` AS riferimento_linea, `dt_ddt`.`data` FROM `dt_ddt` INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`idddt` = `dt_ddt`.`id` WHERE `co_righe_documenti`.`iddocumento` = '.prepare($documento['id']));

            $dati_aggiuntivi = $documento->dati_aggiuntivi_fe;
            $dati = $dati_aggiuntivi['dati_ddt'] ?: [];

            $this->ddt = array_merge($ddt, $dati);
        }

        return $this->ddt;
    }

    /**
     * Restituisce le fatture collegate al documento.
     *
     * @return array
     */
    public function getFattureCollegate()
    {
        if (empty($this->fatture_collegate)) {
            $documento = $this->getDocumento();
            $database = database();

            $note_accredito = $database->fetchArray('SELECT numero_esterno AS id_documento, data FROM co_documenti WHERE id='.prepare($documento['ref_documento']));

            $dati_aggiuntivi = $documento->dati_aggiuntivi_fe;
            $dati = $dati_aggiuntivi['dati_fatture'] ?: [];

            $this->fatture_collegate = array_merge($note_accredito, $dati);
        }

        return $this->fatture_collegate;
    }

    /**
     * Restituisce le informazioni relative al documento.
     *
     * @return Fattura
     */
    public function getDocumento()
    {
        return $this->documento;
    }

    /**
     * Restituisce lo stato di validazione interna dell'XML della fattura.
     *
     * @return bool
     */
    public function isValid()
    {
        return empty($this->getErrors());
    }

    /**
     * Restituisce l'elenco delle irregolarità interne all'XML della fattura.
     *
     * @return bool
     */
    public function getErrors()
    {
        if (!isset($this->validator)) {
            $this->toXML();
        }

        return $this->validator->getErrors();
    }

    /**
     * Ottiene il codice destinatario a partire dal database ufficiale indicepa www.indicepa.gov.it.
     *
     * @param $codice_fiscale
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @return string|null
     */
    public static function PA($codice_fiscale)
    {
        $id = setting('Authorization ID Indice PA');

        if (empty($id)) {
            return null;
        }

        // Configurazione per localhost: CURLOPT_SSL_VERIFYPEER
        $client = new Client(['curl' => [CURLOPT_SSL_VERIFYPEER => false]]);

        $response = $client->request('POST', 'https://www.indicepa.gov.it/public-ws/WS01_SFE_CF.php', [
            'form_params' => [
                'AUTH_ID' => $id,
                'CF' => $codice_fiscale,
            ],
        ]);

        $json = json_decode($response->getBody(), true);

        return isset($json['data'][0]['OU'][0]['cod_uni_ou']) ? $json['data'][0]['OU'][0]['cod_uni_ou'] : null;
    }

    public static function getDirectory()
    {
        return Uploads::getDirectory(Modules::get('Fatture di vendita')['id']);
    }

    /**
     * Salva il file XML.
     *
     * @return string Nome del file
     */
    public function save()
    {
        $this->delete();

        $name = 'Fattura Elettronica';
        $data = $this->getUploadData();

        // Generazione nome XML
        $filename = $this->getFilename(true);

        // Rimozione allegato precedente
        $precedente = $this->getDocumento()->getFatturaElettronica();
        if (!empty($precedente)) {
            $precedente->delete();
        }

        // Registrazione come allegato
        Uploads::upload($this->toXML(), array_merge($data, [
            'name' => $name,
            'original_name' => $filename,
        ]));

        // Aggiornamento effettivo
        database()->update('co_documenti', [
            'progressivo_invio' => $this->getDocumento()['progressivo_invio'],
            'codice_stato_fe' => 'GEN',
            'id_ricevuta_principale' => null,
            'data_stato_fe' => date('Y-m-d H:i:s'),
        ], ['id' => $this->getDocumento()['id']]);

        return ($result === false) ? null : $filename;
    }

    /**
     * Rimuove la fattura generata.
     */
    public function delete()
    {
        $previous = $this->getFilename();
        $data = $this->getUploadData();

        Uploads::delete($previous, $data);
    }

    /**
     * Restituisce il nome del file XML per la fattura elettronica.
     *
     * @param bool $new
     *
     * @return string
     */
    public function getFilename($new = false)
    {
        if (!empty(setting('Terzo intermediario'))) {
            $anagrafica = Anagrafica::find(setting('Terzo intermediario'));
        } else {
            $anagrafica = static::getAzienda();
        }

        $prefix = 'IT'.(!empty($anagrafica['codice_fiscale'] and ($anagrafica['codice_fiscale'] != $anagrafica['piva'])) ? $anagrafica['codice_fiscale'] : str_replace($anagrafica->nazione->iso2, '', $anagrafica['piva']));

        if (empty($this->documento['progressivo_invio']) || !empty($new)) {
            $database = database();

            do {
                $code = date('y').secure_random_string(3);
            } while ($database->fetchNum('SELECT `id` FROM `co_documenti` WHERE `progressivo_invio` = '.prepare($code)) != 0);

            // Registrazione
            $this->documento['progressivo_invio'] = $code;
        }

        return $prefix.'_'.$this->documento['progressivo_invio'].'.xml';
    }

    /**
     * Restituisce il codice XML della fattura elettronica.
     *
     * @return string
     */
    public function toXML()
    {
        if (empty($this->xml)) {
            $this->errors = [];

            $cliente = $this->getCliente();

            // Inizializzazione libreria per la generazione della fattura in XML
            $fattura = new FluidXml(null, ['stylesheet' => 'http://www.fatturapa.gov.it/export/fatturazione/sdi/fatturapa/v1.2.1/fatturaPA_v1.2.1.xsl']);

            // Generazione dell'elemento root
            $fattura->namespace('p', 'http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2');
            $root = $fattura->addChild('p:FatturaElettronica', true);
            $rootNode = $root[0];

            // Completamento dei tag
            $attributes = [
                'versione' => ($cliente['tipo'] == 'Ente pubblico') ? 'FPA12' : 'FPR12',
                'xmlns:ds' => 'http://www.w3.org/2000/09/xmldsig#',
                'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                'xsi:schemaLocation' => 'http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2.1 http://www.fatturapa.gov.it/export/fatturazione/sdi/fatturapa/v1.2.1/Schema_del_file_xml_FatturaPA_versione_1.2.1.xsd',
            ];

            // Attributo SistemaEmittente (max 10 caratteri)
            if (empty(setting('Terzo intermediario'))) {
                $attributes['SistemaEmittente'] = 'OSM';
            }

            foreach ($attributes as $key => $value) {
                $rootNode->setAttribute($key, $value);
            }

            // Generazione della fattura elettronica
            $this->validator = new Validator([
                'FatturaElettronicaHeader' => static::getHeader($this),
                'FatturaElettronicaBody' => static::getBody($this),
            ]);
            $xml = $this->validator->validate();
            $fattura->add($xml);

            $this->xml = $fattura->__toString();
        }

        return $this->xml;
    }

    public static function controllaFattura(Fattura $fattura)
    {
        $database = database();
        $errors = [];

        // Controlli sulla fattura stessa
        if ($fattura->stato->descrizione == 'Bozza') {
            $missing = [
                'state' => tr('Stato ("Emessa")'),
            ];
        }

        if (!empty($missing)) {
            $link = Modules::link('Fatture di vendita', $fattura->id);
            $errors[] = [
                'link' => $link,
                'name' => tr('Fattura'),
                'errors' => $missing,
            ];
        }

        // Natura obbligatoria per iva con esenzione
        $iva = $database->fetchArray('SELECT * FROM `co_iva` WHERE `id` IN (SELECT idiva FROM co_righe_documenti WHERE iddocumento = '.prepare($fattura->id).') AND esente = 1');
        $fields = [
            'codice_natura_fe' => 'Natura IVA',
        ];

        foreach ($iva as $data) {
            $missing = [];
            if (!empty($data)) {
                foreach ($fields as $key => $name) {
                    if (empty($data[$key])) {
                        $missing[] = $name;
                    }
                }
            }

            if (!empty($missing)) {
                $link = Modules::link('IVA', $data['id']);
                $errors[] = [
                    'link' => $link,
                    'name' => tr('IVA _DESC_', [
                        '_DESC_' => $data['descrizione'],
                    ]),
                    'errors' => $missing,
                ];
            }
        }

        // Campi obbligatori per il pagamento
        $data = $fattura->pagamento;
        $fields = [
            'codice_modalita_pagamento_fe' => 'Codice modalità pagamento FE',
        ];

        $missing = [];
        if (!empty($data)) {
            foreach ($fields as $key => $name) {
                if (empty($data[$key])) {
                    $missing[] = $name;
                }
            }
        }

        if (!empty($missing)) {
            $link = Modules::link('Pagamenti', $data['id']);
            $errors[] = [
                'link' => $link,
                'name' => tr('Pagamento'),
                'errors' => $missing,
            ];
        }

        // Campi obbligatori per l'anagrafica Azienda
        $data = FatturaElettronica::getAzienda();
        $fields = [
            'piva' => 'Partita IVA',
            // 'codice_fiscale' => 'Codice Fiscale',
            'citta' => 'Città',
            'indirizzo' => 'Indirizzo',
            'cap' => 'C.A.P.',
            'nazione' => 'Nazione',
        ];

        $missing = [];
        if (!empty($data)) {
            foreach ($fields as $key => $name) {
                if (empty($data[$key]) && !empty($name)) {
                    $missing[] = $name;
                }
            }
        }

        if (!empty($missing)) {
            $link = Modules::link('Anagrafiche', $data['id']);
            $errors[] = [
                'link' => $link,
                'name' => tr('Anagrafica Azienda'),
                'errors' => $missing,
            ];
        }

        // Campi obbligatori per l'anagrafica Cliente
        $data = $fattura->anagrafica;
        $fields = [
            // 'piva' => 'Partita IVA',
            // 'codice_fiscale' => 'Codice Fiscale',
            'citta' => 'Città',
            'indirizzo' => 'Indirizzo',
            'cap' => 'C.A.P.',
            'nazione' => 'Nazione',
        ];

        // se privato/pa o azienda
        if ($data['tipo'] == 'Privato' or $data['tipo'] == 'Ente pubblico') {
            // se privato/pa chiedo obbligatoriamente codice fiscale
            $fields['codice_fiscale'] = 'Codice Fiscale';
            // se pa chiedo codice unico ufficio
            $fields['codice_destinatario'] = ($data['tipo'] == 'Ente pubblico' && empty($data['codice_destinatario'])) ? 'Codice unico ufficio' : '';
        } else {
            // se azienda chiedo partita iva
            $fields['piva'] = 'Partita IVA';
            // se italiana e non ho impostato ne il codice destinatario ne indirizzo PEC chiedo la compilazione di almeno uno dei due
            $fields['codice_destinatario'] = (empty($data['codice_destinatario']) and empty($data['pec']) && intval($data['nazione'] == 'IT')) ? 'Codice destinatario o indirizzo PEC' : '';
        }

        $missing = [];
        if (!empty($data)) {
            foreach ($fields as $key => $name) {
                if (empty($data[$key]) && !empty($name)) {
                    $missing[] = $name;
                }
            }
        }

        if (!empty($missing)) {
            $link = Modules::link('Anagrafiche', $data['id']);
            $errors[] = [
                'link' => $link,
                'name' => tr('Anagrafica Cliente'),
                'errors' => $missing,
            ];
        }

        // Campi obbligatori per l'anagrafica di tipo Vettore
        $id_vettore = $fattura['idvettore'];
        if (!empty($id_vettore)) {
            $data = Anagrafica::find($id_vettore);
            $fields = [
                'piva' => 'Partita IVA',
                'nazione' => 'Nazione',
            ];

            $missing = [];
            if (!empty($data)) {
                foreach ($fields as $key => $name) {
                    if (empty($data[$key]) && !empty($name)) {
                        $missing[] = $name;
                    }
                }
            }

            if (!empty($missing)) {
                $link = Modules::link('Anagrafiche', $data['id']);
                $errors[] = [
                    'link' => $link,
                    'name' => tr('Anagrafica Vettore'),
                    'errors' => $missing,
                ];
            }
        }

        return $errors;
    }

    /**
     * Restituisce l'array responsabile per la generazione del tag DatiTrasmission.
     *
     * @return array
     */
    protected static function getDatiTrasmissione($fattura)
    {
        // Se in impostazioni ho definito un terzo intermediario (es. Aruba, Teamsystem)
        if (!empty(setting('Terzo intermediario'))) {
            $anagrafica = Anagrafica::find(setting('Terzo intermediario'));
        } else {
            $anagrafica = static::getAzienda();
        }

        $documento = $fattura->getDocumento();
        $cliente = $fattura->getCliente();

        $sede = database()->fetchOne('SELECT `codice_destinatario` FROM `an_sedi` WHERE `id` = '.prepare($documento['idsede_destinazione']));
        if (!empty($sede)) {
            $codice_destinatario = $sede['codice_destinatario'];
        } else {
            $codice_destinatario = $cliente->codice_destinatario;
        }

        // Se sto fatturando ad un ente pubblico il codice destinatario di default è 99999 (sei nove), in alternativa uso 0000000 (sette zeri)
        $default_code = ($cliente['tipo'] == 'Ente pubblico') ? '999999' : '0000000';
        // Se il mio cliente non ha sede in Italia il codice destinatario di default diventa (XXXXXXX) (sette X)
        // Se il mio cliente non ha sede in Italia ma è un privato il codice destinatario diventa (0000000) (sette 0)
        $default_code = (($cliente->nazione->iso2 != 'IT') && ($cliente['tipo'] == 'Azienda')) ? 'XXXXXXX' : $default_code;

        // Generazione dell'header
        // Se all'Anagrafe Tributaria il trasmittente è censito con il codice fiscale, es. ditte individuali
        $result = [
            'IdTrasmittente' => [
                'IdPaese' => $anagrafica->nazione->iso2,
                'IdCodice' => (!empty($anagrafica['codice_fiscale']) and ($anagrafica['codice_fiscale'] != $anagrafica['piva'])) ? $anagrafica['codice_fiscale'] : str_replace($anagrafica->nazione->iso2, '', $anagrafica['piva']),
            ],
        ];

        $result[] = [
            'ProgressivoInvio' => $documento['progressivo_invio'],
            'FormatoTrasmissione' => ($cliente['tipo'] == 'Ente pubblico') ? 'FPA12' : 'FPR12',
            'CodiceDestinatario' => !empty($codice_destinatario) ? $codice_destinatario : $default_code,
        ];

        // Telefono di contatto
        if (!empty($anagrafica['telefono'])) {
            $result['ContattiTrasmittente']['Telefono'] = $anagrafica['telefono'];
        }

        // Email di contatto
        if (!empty($anagrafica['email'])) {
            $result['ContattiTrasmittente']['Email'] = $anagrafica['email'];
        }

        // Inizializzazione PEC solo se anagrafica azienda e codice destinatario non compilato, per privato e PA la PEC non serve
        if (empty($cliente['codice_destinatario']) && $cliente['tipo'] == 'Azienda' && !empty($cliente['pec'])) {
            $result['PECDestinatario'] = $cliente['pec'];
        }

        return $result;
    }

    /**
     * Restituisce l'array responsabile per la generazione dei tag DatiAnagrafici per Azienda e Cliente.
     *
     * @return array
     */
    protected static function getDatiAnagrafici($anagrafica, $azienda = false)
    {
        $result = [];

        $is_privato_estero = ($anagrafica->nazione->iso2 != 'IT' && $anagrafica->tipo == 'Privato');

        // Partita IVA (obbligatoria se presente)
        if (!empty($anagrafica['piva'])) {
            if (!empty($anagrafica->nazione->iso2)) {
                $result['IdFiscaleIVA']['IdPaese'] = $anagrafica->nazione->iso2;
            }
            //Rimuovo eventuali idicazioni relative alla nazione
            $result['IdFiscaleIVA']['IdCodice'] = str_replace($anagrafica->nazione->iso2, '', $anagrafica['piva']);
        }

        // Codice fiscale
        //TODO: Nella fattura elettronica, emessa nei confronti di soggetti titolari di partita IVA (nodo CessionarioCommittente), non va indicato il codice fiscale se è già presente la partita iva.
        if (!empty($anagrafica['codice_fiscale'])) {
            $result['CodiceFiscale'] = preg_replace('/\s+/', '', $anagrafica['codice_fiscale']);

            //$result['CodiceFiscale'] = str_replace($anagrafica->nazione->iso2, '', $result['CodiceFiscale']);

            //Rimuovo eventuali idicazioni relative all'iso2 della nazione, solo se la stringa inizia con quest'ultima.
            $result['CodiceFiscale'] = preg_replace('/^'.preg_quote($anagrafica->nazione->iso2, '/').'/', '', $anagrafica['codice_fiscale']);
        }

        // Partita IVA: se privato estero non va considerato il codice fiscale ma la partita iva con 9 zeri
        if ($is_privato_estero) {
            $result['IdFiscaleIVA']['IdPaese'] = $anagrafica->nazione->iso2;
            $result['IdFiscaleIVA']['IdCodice'] = '999999999';
            unset( $result['Anagrafica']['CodiceFiscale'] );
        }

        if (!empty($anagrafica['nome']) or !empty($anagrafica['cognome'])) {
            $result['Anagrafica'] = [
                //'Denominazione' => $anagrafica['ragione_sociale'],
                'Nome' => $anagrafica['nome'],
                'Cognome' => $anagrafica['cognome'],
                // TODO: 'Titolo' => $anagrafica['ragione_sociale'],
                // TODO: CodEORI
            ];
        } else {
            $result['Anagrafica'] = [
                'Denominazione' => $anagrafica['ragione_sociale'],
                //'Nome' => $anagrafica['nome'],
                //'Cognome' => $anagrafica['cognome'],
                // TODO: 'Titolo' => $anagrafica['ragione_sociale'],
                // TODO: CodEORI
            ];
        }

        // Informazioni specifiche azienda
        if ($azienda) {
            $result['RegimeFiscale'] = setting('Regime Fiscale');
        }

        return $result;
    }

    /**
     * Restituisce l'array responsabile per la generazione dei tag Sede per Azienda e Cliente.
     *
     * @param array $anagrafica
     *
     * @return array
     */
    protected static function getSede($anagrafica)
    {
        $result = [
            'Indirizzo' => $anagrafica['indirizzo'],
            'CAP' => ($anagrafica->nazione->iso2 == 'IT') ? $anagrafica['cap'] : '00000',
            'Comune' => $anagrafica['citta'],
        ];

        // Provincia impostata e SOLO SE nazione ITALIA
        if (!empty($anagrafica['provincia']) && $anagrafica->nazione->iso2 == 'IT') {
            $result['Provincia'] = strtoupper($anagrafica['provincia']);
        }

        if (!empty($anagrafica->nazione->iso2)) {
            $result['Nazione'] = $anagrafica->nazione->iso2;
        }

        return $result;
    }

    /**
     * Restituisce l'array responsabile per la generazione del tag CedentePrestatore (mia Azienda ovvero il fornitore) (1.2).
     *
     * @return array
     */
    protected static function getCedentePrestatore($fattura)
    {
        $documento = $fattura->getDocumento();

        //Fattura per conto terzi, il cliente diventa il cedente al posto della mia Azienda (fornitore)
        if ($documento['is_fattura_conto_terzi']) {
            $azienda = $fattura->getCliente();
        } else {
            $azienda = static::getAzienda();
        }

        $result = [
            'DatiAnagrafici' => static::getDatiAnagrafici($azienda, true),
            'Sede' => static::getSede($azienda),
        ];

        // IscrizioneREA
        if (!empty($azienda['codicerea'])) {
            $codice = explode('-', clean($azienda['codicerea'], '\-'));

            if (!empty($codice[0]) && !empty($codice[1])) {
                $result['IscrizioneREA'] = [
                    'Ufficio' => strtoupper($codice[0]),
                    'NumeroREA' => $codice[1],
                ];
            }

            if (!empty($azienda['capitale_sociale'])) {
                $result['IscrizioneREA']['CapitaleSociale'] = $azienda['capitale_sociale'];
            }

            $result['IscrizioneREA']['StatoLiquidazione'] = 'LN'; // Non in liquidazione
        }

        // Contatti

        // Telefono
        if (!empty($azienda['telefono'])) {
            $result['Contatti']['Telefono'] = $azienda['telefono'];
        }

        // Fax
        if (!empty($azienda['fax'])) {
            $result['Contatti']['Fax'] = $azienda['fax'];
        }

        // Email
        if (!empty($azienda['email'])) {
            $result['Contatti']['Email'] = $azienda['email'];
        }

        // Riferimento Amministrazione
        if (!empty($azienda['riferimento_amministrazione'])) {
            $result['RiferimentoAmministrazione'] = $azienda['riferimento_amministrazione'];
        }

        return $result;
    }

    /**
     * Restituisce l'array responsabile per la generazione del tag CessionarioCommittente (Cliente) (1.4).
     *
     * @return array
     */
    protected static function getCessionarioCommittente($fattura)
    {
        $documento = $fattura->getDocumento();

        //Fattura per conto terzi, la mia Azienda (fornitore) diventa il cessionario al posto del cliente
        if ($documento['is_fattura_conto_terzi']) {
            $cliente = static::getAzienda();
        } else {
            $cliente = $fattura->getCliente();
        }

        $result = [
            'DatiAnagrafici' => static::getDatiAnagrafici($cliente),
            'Sede' => static::getSede($cliente),
        ];

        return $result;
    }

    /**
     * Restituisce l'array responsabile per la generazione del tag TerzoIntermediarioOSoggettoEmittente (1.5).
     *
     * @return array
     */
    protected static function getTerzoIntermediarioOSoggettoEmittente($fattura)
    {
        $intermediario = Anagrafica::find(setting('Terzo intermediario'));

        $result = [
            'DatiAnagrafici' => static::getDatiAnagrafici($intermediario),
        ];

        return $result;
    }

    protected static function chunkSplit($str, $chunklen)
    {
        $res = [];
        $k = ceil(strlen($str) / $chunklen);
        for ($i = 0; $i < $k; ++$i) {
            $res[] = substr($str, $i * $chunklen, $chunklen);
        }

        return $res;
    }

    /**
     * Restituisce l'array responsabile per la generazione del tag DatiGeneraliDocumento.
     *
     * @return array
     */
    protected static function getDatiGeneraliDocumento($fattura)
    {
        $documento = $fattura->getDocumento();
        $azienda = static::getAzienda();
        $cliente = $fattura->getCliente();

        $result = [
            'TipoDocumento' => $documento->tipo->codice_tipo_documento_fe,
            'Divisa' => 'EUR',
            'Data' => $documento['data'],
            'Numero' => $documento['numero_esterno'],
        ];

        $righe = $fattura->getRighe();

        // Ritenuta d'Acconto
        $id_ritenuta = null;
        $totale_ritenutaacconto = 0;

        // Rivalsa
        $id_rivalsainps = null;
        $totale_rivalsainps = 0;

        foreach ($righe as $riga) {
            if (!empty($riga['idritenutaacconto']) and empty($riga['is_descrizione'])) {
                $id_ritenuta = $riga['idritenutaacconto'];
                $totale_ritenutaacconto += $riga['ritenutaacconto'];
            }

            if (!empty($riga['idrivalsainps']) and empty($riga['is_descrizione'])) {
                $id_rivalsainps = $riga['idrivalsainps'];
                $totale_rivalsainps += $riga['rivalsainps'];
                $aliquota_iva_rivalsainps = $riga['idiva'];
            }
        }

        if (!empty($id_ritenuta)) {
            $percentuale = database()->fetchOne('SELECT percentuale FROM co_ritenutaacconto WHERE id = '.prepare($id_ritenuta))['percentuale'];
            // Con la nuova versione in vigore dal 01/01/2021, questo nodo diventa ripetibile.
            $result['DatiRitenuta'] = [
                'TipoRitenuta' => (Validate::isValidTaxCode($azienda['codice_fiscale']) and $cliente['tipo'] == 'Privato') ? 'RT01' : 'RT02',
                'ImportoRitenuta' =>  $totale_ritenutaacconto,
                'AliquotaRitenuta' => $percentuale,
                'CausalePagamento' => setting("Causale ritenuta d'acconto"),
            ];
        }

        // Bollo (2.1.1.6)
        // ImportoBollo --> con la nuova versione in vigore dal 01/01/2021, la compilazione di questo nodo è diventata facoltativa.
        // considerato che l'importo è noto e può essere solo di 2,00 Euro.
        $bollo = new Bollo($documento);
        if (!empty($bollo->getBollo())) {
            $result['DatiBollo'] = [
                'BolloVirtuale' => 'SI',
            ];
        }

        // Cassa Previdenziale (Rivalsa) (2.1.1.7)
        if (!empty($id_rivalsainps)) {
            $iva = database()->fetchOne('SELECT `percentuale`, `codice_natura_fe` FROM `co_iva` WHERE `id` = '.prepare($aliquota_iva_rivalsainps));
            $percentuale = database()->fetchOne('SELECT percentuale FROM co_rivalse WHERE id = '.prepare($id_rivalsainps))['percentuale'];

            $dati_cassa = [
                'TipoCassa' => setting('Tipo Cassa Previdenziale'),
                'AlCassa' => $percentuale,
                'ImportoContributoCassa' => $totale_rivalsainps,
                'ImponibileCassa' => $documento->imponibile,
                'AliquotaIVA' => $iva['percentuale'],
            ];

            if ($riga->calcolo_ritenuta_acconto == 'IMP+RIV') {
                $dati_cassa['Ritenuta'] = 'SI';
            }

            if (!empty($iva['codice_natura_fe'])) {
                $dati_cassa['Natura'] = $iva['codice_natura_fe'];
            }

            //$dati_cassa['RiferimentoAmministrazione'] = '';

            $result['DatiCassaPrevidenziale'] = $dati_cassa;
        }

        // Sconto / Maggiorazione (2.1.1.8)
        $sconti_maggiorazioni = [];
        $sconto_finale = $documento->getScontoFinale();
        if (!empty($sconto_finale)) {
            $sconto = [
                'Tipo' => 'SC',
            ];

            if (!empty($documento->sconto_finale_percentuale)) {
                $sconto['Percentuale'] = $documento->sconto_finale_percentuale;
            } else {
                $sconto['Importo'] = $documento->sconto_finale;
            }

            $sconti_maggiorazioni[] = $sconto;
        }

        if (!empty($documento->dati_aggiuntivi_fe['sconto_maggiorazione_tipo'])) {
            $sconto = [
                'Tipo' => $documento->dati_aggiuntivi_fe['sconto_maggiorazione_tipo'],
            ];

            if (!empty($documento->dati_aggiuntivi_fe['sconto_maggiorazione_percentuale'])) {
                $sconto['Percentuale'] = $documento->dati_aggiuntivi_fe['sconto_maggiorazione_percentuale'];
            }

            if (!empty($documento->dati_aggiuntivi_fe['sconto_maggiorazione_importo'])) {
                $sconto['Importo'] = $documento->dati_aggiuntivi_fe['sconto_maggiorazione_importo'];
            }

            $sconti_maggiorazioni[] = $sconto;
        }

        if (!empty($sconti_maggiorazioni)) {
            $result['ScontoMaggiorazione'] = $sconti_maggiorazioni;
        }

        // Importo Totale Documento (2.1.1.9)
        // Valorizzare l’importo complessivo lordo della fattura (onnicomprensivo di Iva, bollo, contributi previdenziali, ecc…)
        $result['ImportoTotaleDocumento'] = abs($documento->totale);

        // Arrotondamento - Eventuale arrotondamento sul totale documento (ammette anche il segno negativo) (2.1.1.10)

        // Causale - Descrizione della causale del documento (2.1.1.11)
        $causali = self::chunkSplit($documento['note'], 200);
        foreach ($causali as $causale) {
            $result[] = ['Causale' => $causale];
        }

        // Art73 - Ciò consente al cedente/prestatore l'emissione nello stesso anno di più documenti aventi stesso numero (2.1.1.12)
        $dati_aggiuntivi = $documento->dati_aggiuntivi_fe;
        if (!empty($dati_aggiuntivi['art73'])) {
            $result['Art73'] = 'SI';
        }

        return $result;
    }

    /**
     * Restituisce l'array responsabile per la generazione del tag DatiTrasporto.
     *
     * @return array
     */
    protected static function getDatiTrasporto($fattura)
    {
        $documento = $fattura->getDocumento();
        $database = database();

        $causale = $database->fetchOne('SELECT descrizione FROM dt_causalet WHERE id = '.prepare($documento['idcausalet']))['descrizione'];
        $aspetto = $database->fetchOne('SELECT descrizione FROM dt_aspettobeni WHERE id = '.prepare($documento['idaspettobeni']))['descrizione'];

        $result = [];

        // Se imposto il vettore deve essere indicata anche la p.iva nella sua anagrafica
        if ($documento['idvettore']) {
            $vettore = Anagrafica::find($documento['idvettore']);
            $result['DatiAnagraficiVettore'] = static::getDatiAnagrafici($vettore);
        }

        if (!empty($causale)) {
            $result['CausaleTrasporto'] = $causale;
        }

        if (!empty($documento['n_colli'])) {
            $result['NumeroColli'] = $documento['n_colli'];
        }

        if (!empty($aspetto)) {
            $result['Descrizione'] = $aspetto;
        }

        if ($documento['tipo_resa']) {
            $result['TipoResa'] = $documento['tipo_resa'];
        }

        return $result;
    }

    /**
     * Restituisce l'array responsabile per la generazione del tag DatiOrdineAcquisto.
     *
     * @return array
     */
    protected static function getDatiOrdineAcquisto($fattura, $lista = null)
    {
        $lista = isset($lista) ? $lista : $fattura->getOrdiniAcquisto();

        $result = [];
        foreach ($lista as $element) {
            if (empty($element['id_documento'])) {
                continue;
            }

            $dati = [];

            foreach ($element['riferimento_linea'] as $linea) {
                $dati[] = [
                    'RiferimentoNumeroLinea' => $linea,
                ];
            }

            $dati['IdDocumento'] = $element['id_documento'];

            if (!empty($element['data'])) {
                $dati['Data'] = $element['data'];
            }

            if (!empty($element['num_item'])) {
                $dati['NumItem'] = $element['num_item'];
            }

            if (!empty($element['codice_commessa'])) {
                $dati['CodiceCommessaConvenzione'] = $element['codice_commessa'];
            }

            if (!empty($element['codice_cup'])) {
                $dati['CodiceCUP'] = $element['codice_cup'];
            }

            if (!empty($element['codice_cig'])) {
                $dati['CodiceCIG'] = $element['codice_cig'];
            }
            $result[] = $dati;
        }

        return $result;
    }

    /**
     * Restituisce l'array responsabile per la generazione del tag DatiDdt.
     *
     * @return array
     */
    protected static function getDatiDDT($fattura)
    {
        $ddt = $fattura->getDDT();

        $result = [];
        foreach ($ddt as $element) {
            if (empty($element['id_documento'])) {
                continue;
            }

            $dati = [];

            $dati['NumeroDDT'] = $element['id_documento'];

            if (!empty($element['data'])) {
                $dati['DataDDT'] = $element['data'];
            }

            if (!empty($element['riferimento_linea'])) {
                $dati['RiferimentoNumeroLinea'] = $element['riferimento_linea'];
            }

            $result[] = $dati;
        }

        return $result;
    }

    /**
     * Restituisce l'array responsabile per la generazione del tag DatiContratto.
     *
     * @return array
     */
    protected static function getDatiContratto($fattura)
    {
        $contratti = $fattura->getContratti();

        return self::getDatiOrdineAcquisto($fattura, $contratti);
    }

    /**
     * Restituisce l'array responsabile per la generazione del tag DatiConvenzione.
     *
     * @return array
     */
    protected static function getDatiConvenzione($fattura)
    {
        $documento = $fattura->getDocumento();

        $dati_aggiuntivi = $documento->dati_aggiuntivi_fe;
        $dati = $dati_aggiuntivi['dati_convenzione'] ?: [];

        return self::getDatiOrdineAcquisto($fattura, $dati);
    }

    /**
     * Restituisce l'array responsabile per la generazione del tag DatiRicezione.
     *
     * @return array
     */
    protected static function getDatiRicezione($fattura)
    {
        $documento = $fattura->getDocumento();

        $dati_aggiuntivi = $documento->dati_aggiuntivi_fe;
        $dati = $dati_aggiuntivi['dati_ricezione'] ?: [];

        return self::getDatiOrdineAcquisto($fattura, $dati);
    }

    /**
     * Restituisce l'array responsabile per la generazione del tag DatiFattureCollegate.
     *
     * @return array
     */
    protected static function getDatiFattureCollegate($fattura)
    {
        $fatture = $fattura->getFattureCollegate();

        return self::getDatiOrdineAcquisto($fattura, $fatture);
    }

    /**
     * Restituisce l'array responsabile per la generazione del tag DatiDocumento.
     *
     * @return array
     */
    protected static function getDatiGenerali($fattura)
    {
        $documento = $fattura->getDocumento();
        $cliente = $fattura->getCliente();

        $result = [
            'DatiGeneraliDocumento' => static::getDatiGeneraliDocumento($fattura),
        ];

        // Controllo le le righe per la fatturazione di ordini
        $dati_ordini = static::getDatiOrdineAcquisto($fattura);
        if (!empty($dati_ordini)) {
            foreach ($dati_ordini as $dato) {
                if (!empty($dato)) {
                    $result[] = [
                        'DatiOrdineAcquisto' => $dato,
                    ];
                }
            }
        }

        // Controllo le le righe per la fatturazione di contratti
        $dati_contratti = static::getDatiContratto($fattura);
        if (!empty($dati_contratti)) {
            foreach ($dati_contratti as $dato) {
                if (!empty($dato)) {
                    $result[] = [
                        'DatiContratto' => $dato,
                    ];
                }
            }
        }

        // Controllo le le righe per la fatturazione di contratti
        $dati_convenzioni = static::getDatiConvenzione($fattura);
        if (!empty($dati_convenzioni)) {
            foreach ($dati_convenzioni as $dato) {
                if (!empty($dato)) {
                    $result[] = [
                        'DatiConvenzione' => $dato,
                    ];
                }
            }
        }

        // Controllo le le righe per la fatturazione di contratti
        $dati_ricezioni = static::getDatiRicezione($fattura);
        if (!empty($dati_ricezioni)) {
            foreach ($dati_ricezioni as $dato) {
                if (!empty($dato)) {
                    $result[] = [
                        'DatiRicezione' => $dato,
                    ];
                }
            }
        }

        // Controllo le le righe per la fatturazione di contratti
        $dati_fatture_collegate = static::getDatiFattureCollegate($fattura);
        if (!empty($dati_fatture_collegate)) {
            foreach ($dati_fatture_collegate as $dato) {
                if (!empty($dato)) {
                    $result[] = [
                        'DatiFattureCollegate' => $dato,
                    ];
                }
            }
        }

        // Controllo le le righe per la fatturazione di contratti
        $dati_ddt = static::getDatiDDT($fattura);
        if (!empty($dati_ddt)) {
            foreach ($dati_ddt as $dato) {
                if (!empty($dato)) {
                    $result[] = [
                        'DatiDDT' => $dato,
                    ];
                }
            }
        }

        if ($documento->tipo->descrizione == 'Fattura accompagnatoria di vendita') {
            $result['DatiTrasporto'] = static::getDatiTrasporto($fattura);
        }

        return $result;
    }

    /**
     * Restituisce l'array responsabile per la generazione del tag DatiBeniServizi.
     *
     * @param $fattura
     *
     * @throws \Exception
     *
     * @return array
     */
    protected static function getDatiBeniServizi($fattura)
    {
        $documento = $fattura->getDocumento();
        $ritenuta_contributi = $documento->ritenutaContributi;
        $righe = $documento->getRighe();

        $database = database();

        $result = [];

        // Righe del documento
        $iva_descrizioni = $righe->first(function ($item, $key) {
            return $item->aliquota != null;
        })->aliquota;

        foreach ($righe as $idx => $riga) {
            $dati_aggiuntivi = $riga->dati_aggiuntivi_fe;

            $dettaglio = [
                'NumeroLinea' => $riga['order'],
            ];

            // 2.2.1.2
            if (!empty($dati_aggiuntivi['tipo_cessione_prestazione'])) {
                $dettaglio['TipoCessionePrestazione'] = $dati_aggiuntivi['tipo_cessione_prestazione'];
            }

            // 2.2.1.3
            if ($riga->isArticolo()) {
                //$tipo_codice = $database->fetchOne('SELECT `mg_categorie`.`nome` FROM `mg_categorie` INNER JOIN `mg_articoli` ON `mg_categorie`.`id` = `mg_articoli`.`id_categoria` WHERE `mg_articoli`.`id` = '.prepare($riga['idarticolo']))['nome'];

                $codice_articolo = [
                    'CodiceTipo' => 'COD',
                    'CodiceValore' => $riga->codice,
                ];

                $dettaglio['CodiceArticolo'] = $codice_articolo;
            }

            // Non ammesso ’
            // $descrizione = html_entity_decode($riga['descrizione'], ENT_HTML5, 'UTF-8');
            $descrizione = str_replace('&gt;', ' ', $riga['descrizione']);
            $descrizione = str_replace('…', '...', $descrizione);
            $descrizione = str_replace('’', ' ', $descrizione);

            // Aggiunta dei riferimenti ai documenti
            if (setting('Riferimento dei documenti in Fattura Elettronica') && $riga->hasOriginalComponent()) {
                $descrizione .= "\n".$riga->getOriginalComponent()->getDocument()->getReference();
            }

            $dettaglio['Descrizione'] = $descrizione;

            $qta = abs($riga->qta) ?: 1;
            $dettaglio['Quantita'] = $qta;

            if (!empty($riga['um'])) {
                $dettaglio['UnitaMisura'] = $riga['um'];
            }

            if (!empty($dati_aggiuntivi['data_inizio_periodo'])) {
                $dettaglio['DataInizioPeriodo'] = $dati_aggiuntivi['data_inizio_periodo'];
            }
            if (!empty($dati_aggiuntivi['data_fine_periodo'])) {
                $dettaglio['DataFinePeriodo'] = $dati_aggiuntivi['data_fine_periodo'];
            }

            $dettaglio['PrezzoUnitario'] = $riga->prezzo_unitario ?: 0;

            // Sconto (2.2.1.10)
            $sconto_unitario = (float) $riga->sconto_unitario;

            if (!empty($sconto_unitario)) {
                $sconto = [
                    'Tipo' => $sconto_unitario > 0 ? 'SC' : 'MG',
                ];

                if ($riga['tipo_sconto'] == 'PRC') {
                    $sconto['Percentuale'] = abs($riga->sconto_percentuale);
                } else {
                    $sconto['Importo'] = abs($sconto_unitario);
                }

                $dettaglio['ScontoMaggiorazione'] = $sconto;
            }

            $aliquota = $riga->aliquota ?: $iva_descrizioni;
            $percentuale = floatval($aliquota->percentuale);

            $prezzo_totale = $riga->totale_imponibile;
            $prezzo_totale = $prezzo_totale ?: 0;
            $dettaglio['PrezzoTotale'] = $prezzo_totale;

            $dettaglio['AliquotaIVA'] = $percentuale;

            if (!empty($riga['idritenutaacconto']) && empty($riga['is_descrizione'])) {
                if ($riga['calcolo_ritenuta_acconto'] == 'IMP+RIV') {
                    $dettaglio['Ritenuta'] = 'SI';
                }
            }

            // Controllo aggiuntivo codice_natura_fe per evitare che venga riportato il tag vuoto
            if (empty($percentuale) && !empty($aliquota['codice_natura_fe'])) {
                $dettaglio['Natura'] = $aliquota['codice_natura_fe'];
            }

            if (!empty($dati_aggiuntivi['riferimento_amministrazione'])) {
                $dettaglio['RiferimentoAmministrazione'] = $dati_aggiuntivi['riferimento_amministrazione'];
            }

            // AltriDatiGestionali (2.2.1.16) - Ritenuta ENASARCO
            // https://forum.italia.it/uploads/default/original/2X/d/d35d721c3a3a601d2300378724a270154e23af52.jpeg
            if (!empty($riga['ritenuta_contributi'])) {
                $dettaglio[]['AltriDatiGestionali'] = [
                    'TipoDato' => 'CASSA-PREV',
                    'RiferimentoTesto' => setting('Tipo Cassa Previdenziale').' - '.$ritenuta_contributi->descrizione.' ('.Translator::numberToLocale($ritenuta_contributi->percentuale).'%)',
                    'RiferimentoNumero' => $riga->ritenuta_contributi,
                ];
            }

            $rs_ritenuta = $database->fetchOne('SELECT percentuale_imponibile FROM co_ritenutaacconto WHERE id='.prepare($riga['idritenutaacconto']));
            if (!empty($rs_ritenuta['percentuale_imponibile'])) {
                $dettaglio[]['AltriDatiGestionali'] = [
                    'TipoDato' => 'IMPON-RACC',
                    'RiferimentoTesto' => 'Imponibile % ritenuta d\'acconto',
                    'RiferimentoNumero' => $rs_ritenuta['percentuale_imponibile'],
                ];
            }

            // Dichiarazione d'intento
            $dichiarazione = $documento->dichiarazione;
            $id_iva_dichiarazione = setting("Iva per lettere d'intento");
            if (!empty($dichiarazione) && $riga->aliquota->id == $id_iva_dichiarazione) {
                $dettaglio[]['AltriDatiGestionali'] = [
                    'TipoDato' => 'AswDichInt',
                    'RiferimentoTesto' => $dichiarazione->numero_protocollo,
                    'RiferimentoNumero' => $dichiarazione->numero_progressivo,
                    'RiferimentoData' => $dichiarazione->data_emissione,
                ];
            }

            // Dati aggiuntivi dinamici
            if (!empty($dati_aggiuntivi['altri_dati'])) {
                foreach ($dati_aggiuntivi['altri_dati'] as $dato) {
                    $altri_dati = [];

                    if (!empty($dato['tipo_dato'])) {
                        $altri_dati['TipoDato'] = $dato['tipo_dato'];
                    }

                    if (!empty($dato['riferimento_testo'])) {
                        $altri_dati['RiferimentoTesto'] = $dato['riferimento_testo'];
                    }

                    if (!empty($dato['riferimento_numero'])) {
                        $altri_dati['RiferimentoNumero'] = $dato['riferimento_numero'];
                    }

                    if (!empty($dato['riferimento_data'])) {
                        $altri_dati['RiferimentoData'] = $dato['riferimento_data'];
                    }

                    $dettaglio[]['AltriDatiGestionali'] = $altri_dati;
                }
            }

            $result[] = [
                'DettaglioLinee' => $dettaglio,
            ];
        }

        // Riepiloghi per IVA per percentuale
        $riepiloghi_percentuale = $righe->filter(function ($item, $key) {
            return $item->aliquota != null && $item->aliquota->codice_natura_fe == null;
        })->groupBy(function ($item, $key) {
            return $item->aliquota->percentuale;
        });
        foreach ($riepiloghi_percentuale as $riepilogo) {
            $totale = round($riepilogo->sum('totale_imponibile') + $riepilogo->sum('rivalsa_inps'), 2);
            $imposta = round($riepilogo->sum('iva') + $riepilogo->sum('iva_rivalsa_inps'), 2);

            $totale = $totale;
            $imposta = $imposta;

            $dati = $riepilogo->first()->aliquota;

            $iva = [
                'AliquotaIVA' => $dati['percentuale'],
                'ImponibileImporto' => $totale,
                'Imposta' => $imposta,
                'EsigibilitaIVA' => $dati['esigibilita'],
            ];

            // Con split payment EsigibilitaIVA sempre a S
            if ($documento['split_payment']) {
                $iva['EsigibilitaIVA'] = 'S';
            }

            // TODO: la dicitura può essere diversa tra diverse IVA con stessa percentuale/natura
            // nei riepiloghi viene fatto un accorpamento percentuale/natura
            if (!empty($riepilogo['dicitura'])) {
                // $iva['RiferimentoNormativo'] = $riepilogo['dicitura'];
            }

            // 2.2.2
            $result[] = [
                'DatiRiepilogo' => $iva,
            ];
        }

        // Riepiloghi per IVA per natura
        $riepiloghi_natura = $righe->filter(function ($item, $key) {
            return $item->aliquota != null && $item->aliquota->codice_natura_fe != null;
        })->groupBy(function ($item, $key) {
            return $item->aliquota->codice_natura_fe;
        });
        foreach ($riepiloghi_natura as $riepilogo) {
            $totale = round($riepilogo->sum('totale_imponibile') + $riepilogo->sum('rivalsa_inps'), 2);
            $imposta = round($riepilogo->sum('iva') + $riepilogo->sum('iva_rivalsa_inps'), 2);

            $totale = $totale;
            $imposta = $imposta;

            $dati = $riepilogo->first()->aliquota;

            $iva = [
                'AliquotaIVA' => 0,
                'Natura' => $dati->codice_natura_fe,
                'ImponibileImporto' => $totale,
                'Imposta' => $imposta,
                'EsigibilitaIVA' => $dati->esigibilita,
                'RiferimentoNormativo' => $dati->descrizione,
            ];

            // Con split payment EsigibilitaIVA sempre a S
            if ($documento['split_payment']) {
                $iva['EsigibilitaIVA'] = 'S';
            }

            // 2.2.2
            $result[] = [
                'DatiRiepilogo' => $iva,
            ];
        }

        return $result;
    }

    /**
     * Restituisce l'array responsabile per la generazione del tag DatiPagamento (2.4).
     *
     * @return array
     */
    protected static function getDatiPagamento($fattura)
    {
        $documento = $fattura->getDocumento();

        $fattura = Fattura::find($documento['id']);
        $banca = $fattura->getBanca();

        $database = database();

        $co_pagamenti = $database->fetchOne('SELECT * FROM `co_pagamenti` WHERE `id` = '.prepare($documento['idpagamento']));

        $result = [
            'CondizioniPagamento' => ($co_pagamenti['prc'] == 100) ? 'TP02' : 'TP01',
        ];

        $co_scadenziario = $database->fetchArray('SELECT * FROM `co_scadenziario` WHERE `iddocumento` = '.prepare($documento['id']));
        foreach ($co_scadenziario as $scadenza) {
            $pagamento = [
                'ModalitaPagamento' => $co_pagamenti['codice_modalita_pagamento_fe'],
                'DataScadenzaPagamento' => $scadenza['scadenza'],
                'ImportoPagamento' => abs($scadenza['da_pagare']),
            ];

            if (!empty($banca->nome)) {
                $pagamento['IstitutoFinanziario'] = $banca->nome;
            }

            if (!empty($banca->iban)) {
                $pagamento['IBAN'] = clean($banca->iban);
            }

            // BIC senza parte per filiale (causa errori di validazione)
            if (!empty($banca->bic)) {
                $pagamento['BIC'] = substr($banca->bic, 0, 8);
            }

            $result[]['DettaglioPagamento'] = $pagamento;
        }

        return $result;
    }

    /**
     * Restituisce l'array responsabile per la generazione del tag Allegati.
     *
     * @return array
     */
    protected static function getAllegati($fattura)
    {
        $documento = $fattura->getDocumento();
        $cliente = $fattura->getCliente();
        $attachments = [];

        // Informazioni sul modulo
        $id_module = Modules::get('Fatture di vendita')['id'];
        $directory = Uploads::getDirectory($id_module);

        // Allegati
        $allegati = Uploads::get([
            'id_module' => $id_module,
            'id_record' => $documento['id'],
        ]);

        // Inclusione
        foreach ($allegati as $allegato) {
            if ($allegato['category'] == 'Allegati Fattura Elettronica') {
                $file = base_dir().'/'.$directory.'/'.$allegato['filename'];

                $attachments[] = [
                    'NomeAttachment' => $allegato['name'],
                    'FormatoAttachment' => Uploads::fileInfo($file)['extension'],
                    'Attachment' => base64_encode(file_get_contents($file)),
                ];
            }
        }

        // Aggiunta della stampa
        $print = false;
        if ($cliente['tipo'] == 'Privato') {
            $print = setting('Allega stampa per fattura verso Privati');
        } elseif ($cliente['tipo'] == 'Azienda') {
            $print = setting('Allega stampa per fattura verso Aziende');
        } else {
            $print = setting('Allega stampa per fattura verso PA');
        }

        if (!$print) {
            return $attachments;
        }

        $data = $fattura->getUploadData();

        // Generazione stampa
        $print = Prints::getModulePredefinedPrint($id_module);
        $info = Prints::render($print['id'], $documento['id'], null, true);

        // Salvataggio stampa come allegato
        $name = 'Stampa allegata';
        $is_presente = database()->fetchNum('SELECT id FROM zz_files WHERE id_module = '.prepare($id_module).' AND id_record = '.prepare($documento['id']).' AND name = '.prepare($name));
        if (empty($is_presente)) {
            Uploads::upload($info['pdf'], array_merge($data, [
                'name' => $name,
                'original_name' => $info['path'],
            ]));
        }

        // Introduzione allegato in Fattura Elettronica
        $attachments[] = [
            'NomeAttachment' => 'Fattura',
            'FormatoAttachment' => 'PDF',
            'Attachment' => base64_encode($info['pdf']),
        ];

        return $attachments;
    }

    /**
     * Restituisce l'array responsabile per la generazione del tag FatturaElettronicaHeader.
     *
     * @return array
     */
    protected static function getHeader($fattura)
    {
        $result = [
            'DatiTrasmissione' => static::getDatiTrasmissione($fattura),
            'CedentePrestatore' => static::getCedentePrestatore($fattura),
            'CessionarioCommittente' => static::getCessionarioCommittente($fattura),
        ];

        // 1.5 Terzo Intermediario
        if (!empty(setting('Terzo intermediario'))) {
            $result['TerzoIntermediarioOSoggettoEmittente'] = static::getTerzoIntermediarioOSoggettoEmittente($fattura);

            // 1.6 Soggetto terzo
            $result['SoggettoEmittente'] = 'TZ';
        }

        // 1.5 o Soggetto Emittente (Autofattura) - da parte del fornitore (mia Azienda) per conto del cliente esonerato
        // In caso di acquisto di prodotti da un agricolo in regime agevolato (art. 34, comma 6, del d.P.R. n. 633/72) da parte di un operatore IVA obbligato alla FE, quest'ultimo emetterà una FE usando la tipologia "TD01" per conto dell'agricoltore venditore
        if ($fattura->getDocumento()['is_fattura_conto_terzi']) {
            $result['TerzoIntermediarioOSoggettoEmittente'] = [
                'DatiAnagrafici' => static::getDatiAnagrafici(static::getAzienda()),
            ];

            // 1.6 Cessionario/Committente
            $result['SoggettoEmittente'] = 'CC';
        }

        return $result;
    }

    /**
     * Restituisce l'array responsabile per la generazione del tag FatturaElettronicaBody.
     *
     * @return array
     */
    protected static function getBody($fattura)
    {
        $result = [
            'DatiGenerali' => static::getDatiGenerali($fattura),
            'DatiBeniServizi' => static::getDatiBeniServizi($fattura),
            'DatiPagamento' => static::getDatiPagamento($fattura),
        ];

        // Allegati
        $allegati = static::getAllegati($fattura);
        if (!empty($allegati)) {
            foreach ($allegati as $allegato) {
                $result[] = [
                    'Allegati' => $allegato,
                ];
            }
        }

        return $result;
    }

    protected function getUploadData()
    {
        return [
            'category' => tr('Fattura Elettronica'),
            'id_module' => Modules::get('Fatture di vendita')['id'],
            'id_record' => $this->getDocumento()['id'],
        ];
    }
}
