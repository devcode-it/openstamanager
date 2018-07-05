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
    protected $azienda;
    protected $cliente;

    protected $documento;
    protected $righe_documento;

    public function __construct($id_documento)
    {
        $database = \Database::getConnection();

        // Documento
        $this->documento = $database->fetchOne('SELECT *, (SELECT `codice_fe` FROM `co_tipidocumento` WHERE `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento`) AS `tipo_documento` FROM `co_documenti` WHERE `id` = '.prepare($id_documento));

        // Righe del documento
        $this->righe_documento = $database->select('co_righe_documenti', '*', ['iddocumento' => $id_documento]);

        // Anagrafica azienda
        $this->azienda = $database->fetchOne('SELECT *, (SELECT `iso2` FROM `an_nazioni` WHERE `an_nazioni`.`id` = `an_anagrafiche`.`id_nazione`) AS nazione FROM `an_anagrafiche` WHERE `idanagrafica` = '.prepare(\Settings::get('Azienda predefinita')));

        // Anagrafica cliente
        $this->cliente = $database->fetchOne('SELECT *, (SELECT `iso2` FROM `an_nazioni` WHERE `an_nazioni`.`id` = `an_anagrafiche`.`id_nazione`) AS nazione FROM `an_anagrafiche` WHERE `idanagrafica` = '.prepare($this->documento['idanagrafica']));
    }

    public function getAzienda()
    {
        return $this->azienda;
    }

    public function getCliente()
    {
        return $this->cliente;
    }

    public function getDocumento()
    {
        return $this->documento;
    }

    public function getRigheDocumento()
    {
        return $this->righe_documento;
    }

    /**
     * Undocumented function.
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
            'CodiceDestinatario' => !empty($cliente['cod_sogei']) ? $cliente['cod_sogei'] : '0000000',
            'ContattiTrasmittente' => [
                'Telefono' => $azienda['email'],
                'Email' => $azienda['email'],
            ],
        ];

        // Inizializzazione PEC solo se necessario
        if (empty($cliente['cod_sogei'])) {
            $result['PECDestinatario'] = $cliente['cod_sogei_pec'];
        }

        return $result;
    }

    /**
     * Undocumented function.
     *
     * @return array
     */
    protected static function getDatiAzienda($azienda)
    {
        $result = [
            'DatiAnagrafici' => [
                'IdFiscaleIVA' => [
                    'IdPaese' => $azienda['nazione'],
                    'IdCodice' => $azienda['piva'],
                ],
                'CodiceFiscale' => $azienda['codice_fiscale'],
                'Anagrafica' => [
                    'Denominazione' => $azienda['ragione_sociale'], // Massimo 80 caratteri
                    // TODO: 'Nome' => $azienda['ragione_sociale'], // Massimo 80 caratteri
                    // TODO: 'Cognome' => $azienda['ragione_sociale'], // Massimo 80 caratteri
                    // TODO: 'Titolo' => $azienda['ragione_sociale'], // Massimo 80 caratteri
                    // TODO: CodEORI
                ],
                // TODO: AlboProfessionale, ProvinciaAlbo, NumeroIscrizioneAlbo, DataIscrizioneAlbo
                'RegimeFiscale' => \Settings::get('Regime Fiscale'), // Da introdurre
            ],
            'Sede' => [
                'Indirizzo' => $azienda['indirizzo'], // Massimo 60 caratteri
                'CAP' => $azienda['cap'], // Massimo 5 di 10 caratteri
                'Comune' => $azienda['citta'], // Massimo 60 caratteri
                'Provincia' => $azienda['provincia'], // SOLO SE nazione ITALIA
                'Nazione' => $azienda['nazione'],
            ],
            // TODO: StabileOrganizzazione, IscrizioneREA, Contatti, RiferimentoAmministrazione
        ];

        return $result;
    }

    /**
     * Undocumented function.
     *
     * @return array
     */
    protected static function getDatiCliente($cliente)
    {
        $result = [
            'DatiAnagrafici' => [
                'CodiceFiscale' => $cliente['codice_fiscale'], // Oppure PARTITA IVA in alternativa
                'Anagrafica' => [
                    'Denominazione' => $cliente['ragione_sociale'], // Massimo 80 caratteri
                ],
            ],
            'Sede' => [
                'Indirizzo' => $cliente['indirizzo'], // Massimo 60 caratteri
                'CAP' => $cliente['cap'], // Massimo 5 di 10 caratteri
                'Comune' => $cliente['citta'], // Massimo 60 caratteri
                'Provincia' => $cliente['provincia'],
                'Nazione' => $cliente['nazione'],
            ],
        ];

        return $result;
    }

    /**
     * Undocumented function.
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
     * Undocumented function.
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
     * Undocumented function.
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

            $iva = $database->fetchArray('SELECT percentuale FROM co_iva WHERE id='.prepare($riga['idiva']));
            $id_iva = $iva[0]['percentuale'];

            $result[] = [
                'DettaglioLinee' => [
                    'NumeroLinea' => $numero,
                    'Descrizione' => $riga['descrizione'],
                    'Quantita' => $riga['qta'],
                    'PrezzoUnitario' => $prezzo_unitario,
                    'ScontoMaggiorazione' => [
                        'Tipo' => 'SC',
                        'Percentuale' => ($riga['tipo_sconto'] == 'PRC') ? $riga['sconto'] : ($riga['sconto'] * 100) / $riga['subtotale'],
                    ],
                    'PrezzoTotale' => $prezzo_totale,
                    'AliquotaIVA' => $id_iva,
                ],
            ];
        }

        // Riepiloghi per IVA
        $riepiloghi = $database->fetchArray('SELECT SUM(subtotale - sconto) as totale, SUM(iva) as iva, idiva  FROM `co_righe_documenti` WHERE iddocumento = '.prepare($id_documento).' GROUP BY idiva');
        foreach ($riepiloghi as $numero => $riepilogo) {
            $iva = $database->fetchArray('SELECT percentuale FROM co_iva WHERE id='.prepare($riepilogo['idiva']));
            $id_iva = $iva[0]['percentuale'];

            $result[] = [
                'DatiRiepilogo' => [
                    'AliquotaIVA' => $id_iva,
                    'ImponibileImporto' => $riepilogo['tot'],
                    'Imposta' => $riepilogo['iva'],
                    'EsigibilitaIVA' => 'I',
                ],
            ];
        }

        return $result;
    }

    /**
     * Undocumented function.
     *
     * @return array
     */
    protected static function getDatiPagamento($documento)
    {
        $result = [
            'CondizioniPagamento' => '',
            'DettaglioPagamento' => [
                'ModalitaPagamento' => '',
                'DataScadenzaPagamento' => '',
                'ImportoPagamento' => '',
            ],
        ];

        return $result;
    }

    protected static function getHeader($documento, $azienda, $cliente)
    {
        $result = [
            'DatiTrasmissione' => self::getDatiTrasmissione($documento, $azienda, $cliente),
            'CedentePrestatore' => self::getDatiAzienda($azienda),
            'CessionarioCommittente' => self::getDatiCliente($cliente),
        ];

        return $result;
    }

    protected static function getBody($documento, $azienda, $cliente, $righe_documento)
    {
        $result = [
            'DatiGenerali' => self::getDatiDocumento($documento),
            'DatiBeniServizi' => self::getDatiBeniServizi($documento, $righe_documento),
            'DatiPagamento' => self::getDatiPagamento($documento),
        ];

        return $result;
    }

    protected static function prepareForXML($input, $key = null)
    {
        $output = null;
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $output[$key] = self::prepareForXML($value, $key);
            }
        } elseif (!is_null($input)) {
            $info = self::$validators[$key];
            $size = isset($info['size']) ? $info['size'] : null;
            $size = explode('-', $info['size']);

            $output = $input;
            // Operazioni di normalizzazione
            if ($info['type'] == 'decimal') {
                $output = number_format($output, 2, '.', '');
            } elseif (isset($size[1])) {
                S::create($output)->substr(2, $size[1]);
            }

            // Validazione
            if ($info['type'] == 'string' || $info['type'] == 'normalizedString') {
                if ($info['type'] == 'string') {
                    $validator = v::alnum();
                } elseif ('normalizedString') {
                    $validator = v::alnum()->noWhitespace();
                }

                if (isset($size[1])) {
                    $validator = $validator->length($size[0], $size[1]);
                }
            } elseif ($info['type'] == 'decimal') {
                $validator = v::floatVal();
            } elseif ($info['type'] == 'date') {
                $validator = v::date();
            }

            if (!empty($validator)) {
                //echo $key.': '.intval($validator->validate($output)).'<br>';
            }
        }

        return $output;
    }

    public function save($directory)
    {
        $documento = $this->getDocumento();
        $filename = $documento['codice_xml'];

        // Generazione nome XML
        if (empty($filename)) {
            $azienda = $this->getAzienda();
            $codice = empty($azienda['piva']) ? $azienda['codice_fiscale'] : $azienda['piva'];

            $filename = 'IT'.$codice.'_'.date('y').secure_random_string(3);

            // Registrazione
            $database = \Database::getConnection();
            $database->update('co_documenti', ['codice_xml' => $filename], ['id' => $documento['id']]);
            $this->documento['codice_xml'] = $filename;
        }
        $filename .= '.xml';

        // Salvataggio del file
        $result = directory($directory) && file_put_contents(rtrim($directory, '/').'/'.$filename, $this->__toString());

        return ($result === false) ? null : $filename;
    }

    public function __toString()
    {
        $azienda = $this->getAzienda();
        $documento = $this->getDocumento();
        $cliente = $this->getCliente();
        $righe_documento = $this->getRigheDocumento();

        // Inizializzazione libreria per la generazione della fattura in XML
        $fattura = new FluidXml(null);

        // Generazione dell'elemento root
        $fattura->namespace('p', 'http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2');
        $root = $fattura->addChild('p:FatturaElettronica', true); // Our document is with no root node, let's create one.
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

        $xml = [
            'FatturaElettronicaHeader' => self::getHeader($documento, $azienda, $cliente),
            'FatturaElettronicaBody' => self::getBody($documento, $azienda, $cliente, $righe_documento),
        ];
        $xml = self::prepareForXML($xml);

        $fattura->add($xml);

        return $fattura->__toString();
    }

    protected static $validators = [
        'IdPaese' => [
            'type' => 'string',
            'size' => 2,
        ],
        'IdCodice' => [
            'type' => 'string',
            'size' => '1-28',
        ],
        'ProgressivoInvio' => [
            'type' => 'normalizedString',
            'size' => '1-10',
        ],
        'FormatoTrasmissione' => [
            'type' => 'string',
            'size' => 5,
        ],
        'CodiceDestinatario' => [
            'type' => 'string',
            'size' => '6-7',
        ],
        'Telefono' => [
            'type' => 'normalizedString',
            'size' => '5-12',
        ],
        'Email' => [
            'type' => 'string',
            'size' => '7-256',
        ],
        'PECDestinatario' => [
            'type' => 'normalizedString',
            'size' => '7-256',
        ],
        'CodiceFiscale' => [
            'type' => 'string',
            'size' => '11-16',
        ],
        'Denominazione' => [
            'type' => 'normalizedString',
            'size' => '1-80',
        ],
        'Nome' => [
            'type' => 'normalizedString',
            'size' => '1-60',
        ],
        'Cognome' => [
            'type' => 'normalizedString',
            'size' => '1-60',
        ],
        'Titolo' => [
            'type' => 'normalizedString',
            'size' => '2-10',
        ],
        'CodEORI' => [
            'type' => 'string',
            'size' => '13-17',
        ],
        'AlboProfessionale' => [
            'type' => 'normalizedString',
            'size' => '1-60',
        ],
        'ProvinciaAlbo' => [
            'type' => 'string',
            'size' => 2,
        ],
        'NumeroIscrizioneAlbo' => [
            'type' => 'normalizedString',
            'size' => '1-60',
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
            'size' => '1-60',
        ],
        'NumeroCivico' => [
            'type' => 'normalizedString',
            'size' => '1-8',
        ],
        'CAP' => [
            'type' => 'string',
            'size' => 5,
        ],
        'Comune' => [
            'type' => 'normalizedString',
            'size' => '1-60',
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
            'size' => '1-20',
        ],
        'CapitaleSociale' => [
            'type' => 'decimal',
            'size' => '4-15',
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
            'size' => '5-12',
        ],
        'RiferimentoAmministrazione' => [
            'type' => 'normalizedString',
            'size' => '1-20',
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
            'size' => '1-20',
        ],
        'TipoRitenuta' => [
            'type' => 'string',
            'size' => 4,
        ],
        'ImportoRitenuta' => [
            'type' => 'decimal',
            'size' => '4-15',
        ],
        'AliquotaRitenuta' => [
            'type' => 'decimal',
            'size' => '4-6',
        ],
        'CausalePagamento' => [
            'type' => 'string',
            'size' => '1-2',
        ],
        'BolloVirtuale' => [
            'type' => 'string',
            'size' => 2,
        ],
        'ImportoBollo' => [
            'type' => 'decimal',
            'size' => '4-15',
        ],
        'TipoCassa' => [
            'type' => 'string',
            'size' => 4,
        ],
        'AlCassa' => [
            'type' => 'decimal',
            'size' => '4-6',
        ],
        'ImportoContributoCassa' => [
            'type' => 'decimal',
            'size' => '4-15',
        ],
        'ImponibileCassa' => [
            'type' => 'decimal',
            'size' => '4-15',
        ],
        'AliquotaIVA' => [
            'type' => 'decimal',
            'size' => '4-6',
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
            'size' => '4-6',
        ],
        'Importo' => [
            'type' => 'decimal',
            'size' => '4-15',
        ],
        'ImportoTotaleDocumento' => [
            'type' => 'decimal',
            'size' => '4-15',
        ],
        'Arrotondamento' => [
            'type' => 'decimal',
            'size' => '4-21',
        ],
        'Causale' => [
            'type' => 'normalizedString',
            'size' => '1-200',
        ],
        'Art73' => [
            'type' => 'string',
            'size' => 2,
        ],
        'RiferimentoNumeroLinea' => [
            'type' => 'integer',
            'size' => '1-4',
        ],
        'IdDocumento' => [
            'type' => 'normalizedString',
            'size' => '1-20',
        ],
        'NumItem' => [
            'type' => 'normalizedString',
            'size' => '1-20',
        ],
        'CodiceCommessaConvenzione' => [
            'type' => 'normalizedString',
            'size' => '1-100',
        ],
        'CodiceCUP' => [
            'type' => 'normalizedString',
            'size' => '1-15',
        ],
        'CodiceCIG' => [
            'type' => 'normalizedString',
            'size' => '1-15',
        ],
        'RiferimentoFase' => [
            'type' => 'integer',
            'size' => '1-3',
        ],
        'NumeroDDT' => [
            'type' => 'normalizedString',
            'size' => '1-20',
        ],
        'DataDDT' => [
            'type' => 'date',
            'size' => 10,
        ],
        'NumeroLicenzaGuida' => [
            'type' => 'normalizedString',
            'size' => '1-20',
        ],
        'MezzoTrasporto' => [
            'type' => 'normalizedString',
            'size' => '1-80',
        ],
        'CausaleTrasporto' => [
            'type' => 'normalizedString',
            'size' => '1-100',
        ],
        'NumeroColli' => [
            'type' => 'integer',
            'size' => '1-4',
        ],
        'Descrizione' => [
            'type' => 'normalizedString',
            'size' => '1-1000',
        ],
        'UnitaMisuraPeso' => [
            'type' => 'normalizedString',
            'size' => '1-10',
        ],
        'PesoLordo' => [
            'type' => 'decimal',
            'size' => '4-7',
        ],
        'PesoNetto' => [
            'type' => 'decimal',
            'size' => '4-7',
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
            'size' => '1-20',
        ],
        'DataFatturaPrincipale' => [
            'type' => 'date',
            'size' => 10,
        ],
        'NumeroLinea' => [
            'type' => 'integer',
            'size' => '1-4',
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
            'size' => '1-35',
        ],
        'CodiceValore' => [
            'type' => 'normalizedString',
            'size' => '1-35',
        ],
        'Quantita' => [
            'type' => 'decimal',
            'size' => '4-21',
        ],
        'UnitaMisura' => [
            'type' => 'normalizedString',
            'size' => '1-10',
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
            'size' => '4-21',
        ],
        'PrezzoTotale' => [
            'type' => 'decimal',
            'size' => '4-21',
        ],
        'TipoDato' => [
            'type' => 'normalizedString',
            'size' => '1-10',
        ],
        'RiferimentoTesto' => [
            'type' => 'normalizedString',
            'size' => '1-60',
        ],
        'RiferimentoNumero' => [
            'type' => 'decimal',
            'size' => '4-21',
        ],
        'RiferimentoData' => [
            'type' => 'normalizedString',
            'size' => 10,
        ],
        'SpeseAccessorie' => [
            'type' => 'decimal',
            'size' => '4-15',
        ],
        'ImponibileImporto' => [
            'type' => 'decimal',
            'size' => '4-15',
        ],
        'Imposta' => [
            'type' => 'decimal',
            'size' => '4-15',
        ],
        'EsigibilitaIVA' => [
            'type' => 'string',
            'size' => 1,
        ],
        'RiferimentoNormativo' => [
            'type' => 'normalizedString',
            'size' => '1-100',
        ],
        'TotalePercorso' => [
            'type' => 'normalizedString',
            'size' => '1-15',
        ],
        'CondizioniPagamento' => [
            'type' => 'string',
            'size' => 4,
        ],
        'Beneficiario' => [
            'type' => 'string',
            'size' => '1-200',
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
            'size' => '1-3',
        ],
        'DataScadenzaPagamento' => [
            'type' => 'date',
            'size' => 10,
        ],
        'ImportoPagamento' => [
            'type' => 'decimal',
            'size' => '4-15',
        ],
        'CodUfficioPostale' => [
            'type' => 'normalizedString',
            'size' => '1-20',
        ],
        'CognomeQuietanzante' => [
            'type' => 'normalizedString',
            'size' => '1-60',
        ],
        'NomeQuietanzante' => [
            'type' => 'normalizedString',
            'size' => '1-60',
        ],
        'CFQuietanzante' => [
            'type' => 'string',
            'size' => 16,
        ],
        'TitoloQuietanzante' => [
            'type' => 'normalizedString',
            'size' => '2-10',
        ],
        'IstitutoFinanziario' => [
            'type' => 'normalizedString',
            'size' => '1-80',
        ],
        'IBAN' => [
            'type' => 'string',
            'size' => '15-34',
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
            'size' => '8-11',
        ],
        'ScontoPagamentoAnticipato' => [
            'type' => 'decimal',
            'size' => '4-15',
        ],
        'DataLimitePagamentoAnticipato' => [
            'type' => 'date',
            'size' => 10,
        ],
        'PenalitaPagamentiRitardati' => [
            'type' => 'decimal',
            'size' => '4-15',
        ],
        'DataDecorrenzaPenale' => [
            'type' => 'date',
            'size' => 10,
        ],
        'CodicePagamento' => [
            'type' => 'string',
            'size' => '1-60',
        ],
        'NomeAttachment' => [
            'type' => 'normalizedString',
            'size' => '1-60',
        ],
        'AlgoritmoCompressione' => [
            'type' => 'string',
            'size' => '1-10',
        ],
        'FormatoAttachment' => [
            'type' => 'string',
            'size' => '1-10',
        ],
        'DescrizioneAttachment' => [
            'type' => 'normalizedString',
            'size' => '1-100',
        ],
        'Attachment' => [
            'type' => 'base64Binary',
        ],
    ];
}
