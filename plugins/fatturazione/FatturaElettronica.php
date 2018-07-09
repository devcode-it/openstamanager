<?php

namespace Plugins\Fatturazione;

use FluidXml\FluidXml;
use Respect\Validation\Validator as v;
use Stringy\Stringy as S;

/**
 * Classe per la gestione della fatturazione elettronica in XML.
 *
 * @since 2.4.2
 */
class FatturaElettronica
{
    /** @var array Informazioni sull'anagrafica Azienda */
    protected static $azienda = [];

    /** @var array Informazioni sull'anagrafica Cliente del documento */
    protected $cliente = [];
    /** @var array Informazioni sul documento */
    protected $documento = [];
    /** @var array Informazioni sulle righe del documento */
    protected $righe_documento = [];

    /** @var array Stato di validazione interna dell'XML della fattura */
    protected $is_valid = null;
    /** @var array XML della fattura */
    protected $xml = null;

    public function __construct($id_documento)
    {
        $database = \Database::getConnection();

        // Documento
        $documento = $database->fetchOne('SELECT *, (SELECT `codice_tipo_documento_fe` FROM `co_tipidocumento` WHERE `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento`) AS `tipo_documento`, (SELECT `descrizione` FROM `co_statidocumento` WHERE `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`) AS `stato` FROM `co_documenti` WHERE `id` = '.prepare($id_documento));

        // Controllo sulla possibilità di creare la fattura elettronica
        if ($documento['stato'] != 'Emessa') {
            throw new UnexpectedValueException();
        }
        $this->documento = $documento;
    }

    /**
     * Restituisce le informazioni sull'anagrafica azienda.
     *
     * @return array
     */
    public static function getAzienda()
    {
        if (empty(self::$azienda)) {
            $database = \Database::getConnection();

            self::$azienda = $database->fetchOne('SELECT *, (SELECT `iso2` FROM `an_nazioni` WHERE `an_nazioni`.`id` = `an_anagrafiche`.`id_nazione`) AS nazione FROM `an_anagrafiche` WHERE `idanagrafica` = '.prepare(setting('Azienda predefinita')));
        }

        return self::$azienda;
    }

    /**
     * Restituisce le informazioni sull'anagrafica cliente legata al documento.
     *
     * @return array
     */
    public function getCliente()
    {
        if (empty($this->cliente)) {
            $database = \Database::getConnection();

            $this->cliente = $database->fetchOne('SELECT *, (SELECT `iso2` FROM `an_nazioni` WHERE `an_nazioni`.`id` = `an_anagrafiche`.`id_nazione`) AS nazione FROM `an_anagrafiche` WHERE `idanagrafica` = '.prepare($this->getDocumento()['idanagrafica']));
        }

        return $this->cliente;
    }

    /**
     * Restituisce le informazioni relative al documento.
     *
     * @return array
     */
    public function getDocumento()
    {
        return $this->documento;
    }

    /**
     * Restituisce le informazioni relative alle righe del documento.
     *
     * @return array
     */
    public function getRigheDocumento()
    {
        if (empty($this->righe_documento)) {
            $database = \Database::getConnection();

            $this->righe_documento = $database->select('co_righe_documenti', '*', ['iddocumento' => $this->getDocumento()['idanagrafica']]);
        }

        return $this->righe_documento;
    }

    /**
     * Restituisce lo stato di validazione interna dell'XML della fattura.
     *
     * @return bool
     */
    public function isValid()
    {
        if (empty($this->is_valid)) {
            $this->__toString();
        }

        return $this->is_valid;
    }

    /**
     * Restituisce l'array responsabile per la generazione del tag DatiTrasmission.
     *
     * @return array
     */
    protected static function getDatiTrasmissione($documento, $azienda, $cliente)
    {
        // Generazione dell'header
        $result = [
            'IdTrasmittente' => [
                'IdPaese' => $azienda['nazione'],
                'IdCodice' => $azienda['piva'],
            ],
            'ProgressivoInvio' => $documento['numero_esterno'],
            'FormatoTrasmissione' => ($cliente['tipo'] == 'Ente pubblico') ? 'FPA12' : 'FPR12',
            'CodiceDestinatario' => !empty($cliente['codice_pa']) ? $cliente['codice_pa'] : '0000000',
        ];

        // Telefono di contatto
        if (!empty($azienda['telefono'])) {
            $result['ContattiTrasmittente']['Telefono'] = $azienda['telefono'];
        }

        // Email di contatto
        if (!empty($azienda['email'])) {
            $result['ContattiTrasmittente']['Email'] = $azienda['email'];
        }

        // Inizializzazione PEC solo se necessario
        if (empty($cliente['codice_pa'])) {
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

        // Partita IVA (obbligatoria se presente)
        if (!empty($anagrafica['piva'])) {
            $result['IdFiscaleIVA'] = [
                'IdPaese' => $anagrafica['nazione'],
                'IdCodice' => $anagrafica['piva'],
            ];
        }

        // Codice fiscale
        if (!empty($anagrafica['codice_fiscale'])) {
            $result['CodiceFiscale'] = $anagrafica['codice_fiscale'];
        }

        $result['Anagrafica'] = [
            'Denominazione' => $anagrafica['ragione_sociale'],
            // TODO: 'Nome' => $azienda['ragione_sociale'],
            // TODO: 'Cognome' => $azienda['ragione_sociale'],
            // TODO: 'Titolo' => $azienda['ragione_sociale'],
            // TODO: CodEORI
        ];

        // Informazioni specifiche azienda
        if ($azienda) {
            // AlboProfessionale, ProvinciaAlbo, NumeroIscrizioneAlbo, DataIscrizioneAlbo

            $result['RegimeFiscale'] = setting('Regime Fiscale');
        }

        return $result;
    }

    /**
     * Restituisce l'array responsabile per la generazione dei tag Sede per Azienda e Cliente.
     *
     * @return array
     */
    protected static function getSede($anagrafica)
    {
        $result = [
            'Indirizzo' => $anagrafica['indirizzo'],
            'CAP' => $anagrafica['cap'],
            'Comune' => $anagrafica['citta'],
        ];

        // Provincia se impostata e SOLO SE nazione ITALIA
        if (!empty($azienda['provincia']) && $azienda['nazione'] == 'IT') {
            $result['Provincia'] = $azienda['provincia'];
        }

        $result['Nazione'] = $anagrafica['nazione'];

        return $result;
    }

    /**
     * Restituisce l'array responsabile per la generazione del tag CedentePrestatore.
     *
     * @return array
     */
    protected static function getCedentePrestatore($azienda)
    {
        $result = [
            'DatiAnagrafici' => self::getDatiAnagrafici($azienda, true),
            'Sede' => self::getSede($azienda),
            // TODO: StabileOrganizzazione,
        ];

        // IscrizioneREA
        if (!empty($azienda['codicerea'])) {
            $result['IscrizioneREA'] = [
                'Ufficio' => strtoupper(substr($azienda['capitale_sociale'], 0, 2)),
                'NumeroREA' => $azienda['codicerea'],
            ];

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
        if (!empty($azienda['email'])) {
            $result['Contatti']['Fax'] = $azienda['fax'];
        }

        // Email
        if (!empty($azienda['email'])) {
            $result['Contatti']['Email'] = $azienda['email'];
        }

        // TODO: RiferimentoAmministrazione

        return $result;
    }

    /**
     * Restituisce l'array responsabile per la generazione del tag CessionarioCommittente.
     *
     * @return array
     */
    protected static function getCessionarioCommittente($cliente)
    {
        $result = [
            'DatiAnagrafici' => self::getDatiAnagrafici($cliente),
            'Sede' => self::getSede($cliente),
            // TODO: StabileOrganizzazione, RappresentanteFiscale
        ];

        return $result;
    }

    /**
     * Restituisce l'array responsabile per la generazione del tag DatiGeneraliDocumento.
     *
     * @return array
     */
    protected static function getDatiGeneraliDocumento($documento)
    {
        $result = [
            'TipoDocumento' => $documento['tipo_documento'],
            'Divisa' => 'EUR',
            'Data' => $documento['data'],
            'Numero' => $documento['numero_esterno'],
            //'Causale' => $documento['causale'],
        ];

        return $result;
    }

    /**
     * Restituisce l'array responsabile per la generazione del tag DatiDocumento.
     *
     * @return array
     */
    protected static function getDatiDocumento($documento)
    {
        $result = [
            'DatiGeneraliDocumento' => self::getDatiGeneraliDocumento($documento),
        ];

        return $result;
    }

    /**
     * Restituisce l'array responsabile per la generazione del tag DatiBeniServizi.
     *
     * @return array
     */
    protected static function getDatiBeniServizi($documento, $righe_documento)
    {
        $database = \Database::getConnection();

        $result = [];

        // Righe del documento
        foreach ($righe_documento as $numero => $riga) {
            $prezzo_unitario = $riga['subtotale'] / $riga['qta'];
            $prezzo_totale = $riga['subtotale'] - $riga['sconto'];

            $iva = $database->fetchArray('SELECT `percentuale` FROM `co_iva` WHERE `id` = '.prepare($riga['idiva']));
            $percentuale = $iva[0]['percentuale'];

            $result[] = [
                'DettaglioLinee' => [
                    'NumeroLinea' => $numero + 1,
                    'Descrizione' => $riga['descrizione'],
                    'Quantita' => $riga['qta'],
                    'PrezzoUnitario' => $prezzo_unitario,
                    'ScontoMaggiorazione' => [
                        'Tipo' => 'SC',
                        'Percentuale' => ($riga['tipo_sconto'] == 'PRC') ? $riga['sconto'] : ($riga['sconto'] * 100) / $riga['subtotale'],
                    ],
                    'PrezzoTotale' => $prezzo_totale,
                    'AliquotaIVA' => $percentuale,
                ],
            ];
        }

        // Riepiloghi per IVA
        $riepiloghi = $database->fetchArray('SELECT SUM(`subtotale` - `sconto`) as totale, SUM(`iva`) as iva, `idiva` FROM `co_righe_documenti` WHERE `iddocumento` = '.prepare($documento['id']).' GROUP BY `idiva`');
        foreach ($riepiloghi as $riepilogo) {
            $iva = $database->fetchArray('SELECT `percentuale` FROM `co_iva` WHERE `id` = '.prepare($riepilogo['idiva']));
            $percentuale = $iva[0]['percentuale'];

            $result[] = [
                'DatiRiepilogo' => [
                    'AliquotaIVA' => $percentuale,
                    'ImponibileImporto' => $riepilogo['totale'],
                    'Imposta' => $riepilogo['iva'],
                    'EsigibilitaIVA' => 'I',
                ],
            ];
        }

        return $result;
    }

    /**
     * Restituisce l'array responsabile per la generazione del tag DatiPagamento.
     *
     * @return array
     */
    protected static function getDatiPagamento($documento)
    {
        $database = \Database::getConnection();

        $pagamento = $database->fetchOne('SELECT * FROM `co_pagamenti` WHERE `id` = '.prepare($documento['idpagamento']));

        $result = [
            'CondizioniPagamento' => ($pagamento['prc'] == 100) ? 'TP02' : 'TP01',
        ];

        $scadenze = $database->fetchArray('SELECT * FROM `co_scadenziario` WHERE `iddocumento` = '.prepare($documento['id']));
        foreach ($scadenze as $scadenza) {
            $result[] = [
                'DettaglioPagamento' => [
                    'ModalitaPagamento' => $pagamento['codice_modalita_pagemento_fe'],
                    'DataScadenzaPagamento' => $scadenza['scadenza'],
                    'ImportoPagamento' => $scadenza['da_pagare'],
                ],
            ];
        }

        return $result;
    }

    /**
     * Restituisce l'array responsabile per la generazione del tag FatturaElettronicaHeader.
     *
     * @return array
     */
    protected static function getHeader($fattura)
    {
        $azienda = self::getAzienda();
        $documento = $fattura->getDocumento();
        $cliente = $fattura->getCliente();

        $result = [
            'DatiTrasmissione' => self::getDatiTrasmissione($documento, $azienda, $cliente),
            'CedentePrestatore' => self::getCedentePrestatore($azienda),
            'CessionarioCommittente' => self::getCessionarioCommittente($cliente),
        ];

        return $result;
    }

    /**
     * Restituisce l'array responsabile per la generazione del tag FatturaElettronicaBody.
     *
     * @return array
     */
    protected static function getBody($fattura)
    {
        $documento = $fattura->getDocumento();
        $righe_documento = $fattura->getRigheDocumento();

        $result = [
            'DatiGenerali' => self::getDatiDocumento($documento),
            'DatiBeniServizi' => self::getDatiBeniServizi($documento, $righe_documento),
            'DatiPagamento' => self::getDatiPagamento($documento),
        ];

        return $result;
    }

    /**
     * Prepara i contenuti per la generazione dell'XML della fattura.
     * Effettua inoltre dei controlli interni di validità sui campi previsti dallo standard.
     *
     * @param mixed  $input
     * @param string $key
     *
     * @return mixed
     */
    protected function prepareForXML($input, $key = null)
    {
        $output = null;
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $output[$key] = $this->prepareForXML($value, $key);
            }
        } elseif (!is_null($input)) {
            $info = self::$validators[$key];
            $size = isset($info['size']) ? $info['size'] : null;

            $output = $input;
            // Operazioni di normalizzazione
            if ($info['type'] == 'decimal') {
                $output = number_format($output, 2, '.', '');
            } elseif ($info['type'] != 'integer' && isset($size[1])) {
                $output = trim($output);
                S::create($output)->substr(2, $size[1]);
            }

            // Validazione
            if ($info['type'] == 'string' || $info['type'] == 'normalizedString') {
                $validator = v::stringType();

                if (isset($size[1])) {
                    $validator = $validator->length($size[0], $size[1]);
                }
            } elseif ($info['type'] == 'decimal') {
                $validator = v::floatVal();
            } elseif ($info['type'] == 'integer') {
                $validator = v::intVal();
            } elseif ($info['type'] == 'date') {
                $validator = v::date();
            }

            if (!empty($validator)) {
                $validation = $validator->validate($output);

                $this->is_valid &= $validation;

                //echo $key.': '.intval($validation).'<br>';
            }
        }

        return $output;
    }

    /**
     * Salva il file XML.
     *
     * @param string $directory
     *
     * @return string Nome del file
     */
    public function save($directory)
    {
        $documento = $this->getDocumento();
        $filename = $documento['codice_xml'];

        $azienda = self::getAzienda();
        $codice = 'IT'.(empty($azienda['piva']) ? $azienda['codice_fiscale'] : $azienda['piva']);

        // Generazione nome XML
        if (empty($filename) || !starts_with($filename, $codice)) {
            $database = \Database::getConnection();

            do {
                $filename = $codice.'_'.date('y').secure_random_string(3);
            } while ($database->fetchNum('SELECT `id` FROM `co_documenti` WHERE `codice_xml` = '.prepare($filename)));

            // Registrazione
            $database->update('co_documenti', ['codice_xml' => $filename], ['id' => $documento['id']]);
            $this->documento['codice_xml'] = $filename;
        }
        $filename .= '.xml';

        // Salvataggio del file
        $result = directory($directory) && file_put_contents(rtrim($directory, '/').'/'.$filename, $this->__toString());

        return ($result === false) ? null : $filename;
    }

    /**
     * Restituisce il codice XML della fattura elettronica.
     *
     * @return string
     */
    public function __toString()
    {
        if (empty($this->xml)) {
            $this->is_valid = true;

            $cliente = $this->getCliente();

            // Inizializzazione libreria per la generazione della fattura in XML
            $fattura = new FluidXml(null);

            // Generazione dell'elemento root
            $fattura->namespace('p', 'http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2');
            $root = $fattura->addChild('p:FatturaElettronica', true);
            $rootNode = $root[0];

            // Completamento dei tag
            $attributes = [
                'versione' => ($cliente['tipo'] == 'Ente pubblico') ? 'FPA12' : 'FPR12',
                'xmlns:ds' => 'http://www.w3.org/2000/09/xmldsig#',
                'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                'xsi:schemaLocation' => 'http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2 http://www.fatturapa.gov.it/export/fatturazione/sdi/fatturapa/v1.2/Schema_del_file_xml_FatturaPA_versione_1.2.xsd',
            ];
            foreach ($attributes as $key => $value) {
                $rootNode->setAttribute($key, $value);
            }

            // Generazione della fattura elettronica
            $xml = self::prepareForXML([
                'FatturaElettronicaHeader' => self::getHeader($this),
                'FatturaElettronicaBody' => self::getBody($this),
            ]);
            $fattura->add($xml);

            $this->xml = $fattura->__toString();
        }

        return $this->xml;
    }

    /** @var array Elenco di campi dello standard per la formattazione e la validazione */
    protected static $validators = [
        'IdPaese' => [
            'type' => 'string',
            'size' => 2,
        ],
        'IdCodice' => [
            'type' => 'string',
            'size' => [1, 28],
        ],
        'ProgressivoInvio' => [
            'type' => 'normalizedString',
            'size' => [1, 10],
        ],
        'FormatoTrasmissione' => [
            'type' => 'string',
            'size' => 5,
        ],
        'CodiceDestinatario' => [
            'type' => 'string',
            'size' => [6, 7],
        ],
        'Telefono' => [
            'type' => 'normalizedString',
            'size' => [5, 12],
        ],
        'Email' => [
            'type' => 'string',
            'size' => [7, 256],
        ],
        'PECDestinatario' => [
            'type' => 'normalizedString',
            'size' => [7, 256],
        ],
        'CodiceFiscale' => [
            'type' => 'string',
            'size' => [11, 16],
        ],
        'Denominazione' => [
            'type' => 'normalizedString',
            'size' => [1, 80],
        ],
        'Nome' => [
            'type' => 'normalizedString',
            'size' => [1, 60],
        ],
        'Cognome' => [
            'type' => 'normalizedString',
            'size' => [1, 60],
        ],
        'Titolo' => [
            'type' => 'normalizedString',
            'size' => [2, 10],
        ],
        'CodEORI' => [
            'type' => 'string',
            'size' => [13, 17],
        ],
        'AlboProfessionale' => [
            'type' => 'normalizedString',
            'size' => [1, 60],
        ],
        'ProvinciaAlbo' => [
            'type' => 'string',
            'size' => 2,
        ],
        'NumeroIscrizioneAlbo' => [
            'type' => 'normalizedString',
            'size' => [1, 60],
        ],
        'DataIscrizioneAlbo' => [
            'type' => 'date',
            'size' => 10,
        ],
        'RegimeFiscale' => [
            'type' => 'string',
            'size' => 4,
        ],
        'Indirizzo' => [
            'type' => 'normalizedString',
            'size' => [1, 60],
        ],
        'NumeroCivico' => [
            'type' => 'normalizedString',
            'size' => [1, 8],
        ],
        'CAP' => [
            'type' => 'string',
            'size' => 5,
        ],
        'Comune' => [
            'type' => 'normalizedString',
            'size' => [1, 60],
        ],
        'Provincia' => [
            'type' => 'string',
            'size' => 2,
        ],
        'Nazione' => [
            'type' => 'string',
            'size' => 2,
        ],
        'Ufficio' => [
            'type' => 'string',
            'size' => 2,
        ],
        'NumeroREA' => [
            'type' => 'normalizedString',
            'size' => [1, 20],
        ],
        'CapitaleSociale' => [
            'type' => 'decimal',
            'size' => [4, 15],
        ],
        'SocioUnico' => [
            'type' => 'string',
            'size' => 2,
        ],
        'StatoLiquidazione' => [
            'type' => 'string',
            'size' => 2,
        ],
        'Fax' => [
            'type' => 'normalizedString',
            'size' => [5, 12],
        ],
        'RiferimentoAmministrazione' => [
            'type' => 'normalizedString',
            'size' => [1, 20],
        ],
        'SoggettoEmittente' => [
            'type' => 'string',
            'size' => 2,
        ],
        'TipoDocumento' => [
            'type' => 'string',
            'size' => 4,
        ],
        'Divisa' => [
            'type' => 'string',
            'size' => 3,
        ],
        'Data' => [
            'type' => 'date',
            'size' => 10,
        ],
        'Numero' => [
            'type' => 'normalizedString',
            'size' => [1, 20],
        ],
        'TipoRitenuta' => [
            'type' => 'string',
            'size' => 4,
        ],
        'ImportoRitenuta' => [
            'type' => 'decimal',
            'size' => [4, 15],
        ],
        'AliquotaRitenuta' => [
            'type' => 'decimal',
            'size' => [4, 6],
        ],
        'CausalePagamento' => [
            'type' => 'string',
            'size' => [1, 2],
        ],
        'BolloVirtuale' => [
            'type' => 'string',
            'size' => 2,
        ],
        'ImportoBollo' => [
            'type' => 'decimal',
            'size' => [4, 15],
        ],
        'TipoCassa' => [
            'type' => 'string',
            'size' => 4,
        ],
        'AlCassa' => [
            'type' => 'decimal',
            'size' => [4, 6],
        ],
        'ImportoContributoCassa' => [
            'type' => 'decimal',
            'size' => [4, 15],
        ],
        'ImponibileCassa' => [
            'type' => 'decimal',
            'size' => [4, 15],
        ],
        'AliquotaIVA' => [
            'type' => 'decimal',
            'size' => [4, 6],
        ],
        'Ritenuta' => [
            'type' => 'string',
            'size' => 2,
        ],
        'Natura' => [
            'type' => 'string',
            'size' => 2,
        ],
        'Tipo' => [
            'type' => 'string',
            'size' => 2,
        ],
        'Percentuale' => [
            'type' => 'decimal',
            'size' => [4, 6],
        ],
        'Importo' => [
            'type' => 'decimal',
            'size' => [4, 15],
        ],
        'ImportoTotaleDocumento' => [
            'type' => 'decimal',
            'size' => [4, 15],
        ],
        'Arrotondamento' => [
            'type' => 'decimal',
            'size' => [4, 21],
        ],
        'Causale' => [
            'type' => 'normalizedString',
            'size' => [1, 200],
        ],
        'Art73' => [
            'type' => 'string',
            'size' => 2,
        ],
        'RiferimentoNumeroLinea' => [
            'type' => 'integer',
            'size' => [1, 4],
        ],
        'IdDocumento' => [
            'type' => 'normalizedString',
            'size' => [1, 20],
        ],
        'NumItem' => [
            'type' => 'normalizedString',
            'size' => [1, 20],
        ],
        'CodiceCommessaConvenzione' => [
            'type' => 'normalizedString',
            'size' => [1, 100],
        ],
        'CodiceCUP' => [
            'type' => 'normalizedString',
            'size' => [1, 15],
        ],
        'CodiceCIG' => [
            'type' => 'normalizedString',
            'size' => [1, 15],
        ],
        'RiferimentoFase' => [
            'type' => 'integer',
            'size' => [1, 3],
        ],
        'NumeroDDT' => [
            'type' => 'normalizedString',
            'size' => [1, 20],
        ],
        'DataDDT' => [
            'type' => 'date',
            'size' => 10,
        ],
        'NumeroLicenzaGuida' => [
            'type' => 'normalizedString',
            'size' => [1, 20],
        ],
        'MezzoTrasporto' => [
            'type' => 'normalizedString',
            'size' => [1, 80],
        ],
        'CausaleTrasporto' => [
            'type' => 'normalizedString',
            'size' => [1, 100],
        ],
        'NumeroColli' => [
            'type' => 'integer',
            'size' => [1, 4],
        ],
        'Descrizione' => [
            'type' => 'normalizedString',
            'size' => [1, 1000],
        ],
        'UnitaMisuraPeso' => [
            'type' => 'normalizedString',
            'size' => [1, 10],
        ],
        'PesoLordo' => [
            'type' => 'decimal',
            'size' => [4, 7],
        ],
        'PesoNetto' => [
            'type' => 'decimal',
            'size' => [4, 7],
        ],
        'DataOraRitiro' => [
            'type' => 'date',
            'size' => 19,
        ],
        'DataInizioTrasporto' => [
            'type' => 'date',
            'size' => 10,
        ],
        'TipoResa' => [
            'type' => 'string',
            'size' => 3,
        ],
        'DataOraConsegna' => [
            'type' => 'date',
            'size' => 19,
        ],
        'NumeroFatturaPrincipale' => [
            'type' => 'string',
            'size' => [1, 20],
        ],
        'DataFatturaPrincipale' => [
            'type' => 'date',
            'size' => 10,
        ],
        'NumeroLinea' => [
            'type' => 'integer',
            'size' => [1, 4],
        ],
        'TipoCessionePrestazione' => [
            'type' => 'string',
            'size' => 2,
        ],
        'CodiceArticolo' => [
            'type' => 'normalizedString',
        ],
        'CodiceTipo' => [
            'type' => 'normalizedString',
            'size' => [1, 35],
        ],
        'CodiceValore' => [
            'type' => 'normalizedString',
            'size' => [1, 35],
        ],
        'Quantita' => [
            'type' => 'decimal',
            'size' => [4, 21],
        ],
        'UnitaMisura' => [
            'type' => 'normalizedString',
            'size' => [1, 10],
        ],
        'DataInizioPeriodo' => [
            'type' => 'date',
            'size' => 10,
        ],
        'DataFinePeriodo' => [
            'type' => 'date',
            'size' => 10,
        ],
        'PrezzoUnitario' => [
            'type' => 'decimal',
            'size' => [4, 21],
        ],
        'PrezzoTotale' => [
            'type' => 'decimal',
            'size' => [4, 21],
        ],
        'TipoDato' => [
            'type' => 'normalizedString',
            'size' => [1, 10],
        ],
        'RiferimentoTesto' => [
            'type' => 'normalizedString',
            'size' => [1, 60],
        ],
        'RiferimentoNumero' => [
            'type' => 'decimal',
            'size' => [4, 21],
        ],
        'RiferimentoData' => [
            'type' => 'normalizedString',
            'size' => 10,
        ],
        'SpeseAccessorie' => [
            'type' => 'decimal',
            'size' => [4, 15],
        ],
        'ImponibileImporto' => [
            'type' => 'decimal',
            'size' => [4, 15],
        ],
        'Imposta' => [
            'type' => 'decimal',
            'size' => [4, 15],
        ],
        'EsigibilitaIVA' => [
            'type' => 'string',
            'size' => 1,
        ],
        'RiferimentoNormativo' => [
            'type' => 'normalizedString',
            'size' => [1, 100],
        ],
        'TotalePercorso' => [
            'type' => 'normalizedString',
            'size' => [1, 15],
        ],
        'CondizioniPagamento' => [
            'type' => 'string',
            'size' => 4,
        ],
        'Beneficiario' => [
            'type' => 'string',
            'size' => [1, 200],
        ],
        'ModalitaPagamento' => [
            'type' => 'string',
            'size' => 4,
        ],
        'DataRiferimentoTerminiPagamento' => [
            'type' => 'date',
            'size' => 10,
        ],
        'GiorniTerminiPagamento' => [
            'type' => 'integer',
            'size' => [1, 3],
        ],
        'DataScadenzaPagamento' => [
            'type' => 'date',
            'size' => 10,
        ],
        'ImportoPagamento' => [
            'type' => 'decimal',
            'size' => [4, 15],
        ],
        'CodUfficioPostale' => [
            'type' => 'normalizedString',
            'size' => [1, 20],
        ],
        'CognomeQuietanzante' => [
            'type' => 'normalizedString',
            'size' => [1, 60],
        ],
        'NomeQuietanzante' => [
            'type' => 'normalizedString',
            'size' => [1, 60],
        ],
        'CFQuietanzante' => [
            'type' => 'string',
            'size' => 16,
        ],
        'TitoloQuietanzante' => [
            'type' => 'normalizedString',
            'size' => [2, 10],
        ],
        'IstitutoFinanziario' => [
            'type' => 'normalizedString',
            'size' => [1, 80],
        ],
        'IBAN' => [
            'type' => 'string',
            'size' => [15, 34],
        ],
        'ABI' => [
            'type' => 'string',
            'size' => 5,
        ],
        'CAB' => [
            'type' => 'string',
            'size' => 5,
        ],
        'BIC' => [
            'type' => 'string',
            'size' => [8, 11],
        ],
        'ScontoPagamentoAnticipato' => [
            'type' => 'decimal',
            'size' => [4, 15],
        ],
        'DataLimitePagamentoAnticipato' => [
            'type' => 'date',
            'size' => 10,
        ],
        'PenalitaPagamentiRitardati' => [
            'type' => 'decimal',
            'size' => [4, 15],
        ],
        'DataDecorrenzaPenale' => [
            'type' => 'date',
            'size' => 10,
        ],
        'CodicePagamento' => [
            'type' => 'string',
            'size' => [1, 60],
        ],
        'NomeAttachment' => [
            'type' => 'normalizedString',
            'size' => [1, 60],
        ],
        'AlgoritmoCompressione' => [
            'type' => 'string',
            'size' => [1, 10],
        ],
        'FormatoAttachment' => [
            'type' => 'string',
            'size' => [1, 10],
        ],
        'DescrizioneAttachment' => [
            'type' => 'normalizedString',
            'size' => [1, 100],
        ],
        'Attachment' => [
            'type' => 'base64Binary',
        ],
    ];
}
