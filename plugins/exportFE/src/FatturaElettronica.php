<?php

namespace Plugins\ExportFE;

use DateTime;
use FluidXml\FluidXml;
use GuzzleHttp\Client;
use Modules;
use Prints;
use Respect\Validation\Validator as v;
use Stringy\Stringy as S;
use Uploads;

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

    /** @var array Contratti collegati al documento */
    protected $contratti = [];
    /** @var array Ordini di acquisto collegati al documento */
    protected $ordini = [];
    /** @var array Righe del documento */
    protected $righe = [];

    /** @var array XML della fattura */
    protected $xml = null;

    /** @var array Irregolarità nella fattura XML */
    protected $errors = null;

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

    public function __construct($id_documento)
    {
        $database = database();

        // Documento
        $this->documento = $database->fetchOne('SELECT `co_documenti`.*, `co_tipidocumento`.`descrizione` AS `tipo`, `co_tipidocumento`.`codice_tipo_documento_fe` AS `tipo_documento`, `co_statidocumento`.`descrizione` AS `stato` FROM `co_documenti`
        INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento`
        INNER JOIN `co_statidocumento` ON `co_statidocumento`.`id` = `co_documenti`.`idstatodocumento`
        WHERE `co_documenti`.`id` = '.prepare($id_documento));

        // Controllo sulla possibilità di creare la fattura elettronica
        // Posso fatturare ai privati utilizzando il codice fiscale
        if ($this->documento['stato'] != 'Emessa') {
            throw new \UnexpectedValueException();
        }
    }

    public function __toString()
    {
        return $this->toXML();
    }

    /**
     * Restituisce le informazioni sull'anagrafica azienda.
     *
     * @return array
     */
    public function isGenerated()
    {
        $documento = $this->getDocumento();

        return !empty($documento['progressivo_invio']) && file_exists(DOCROOT.'/'.static::getDirectory().'/'.$this->getFilename());
    }

    /**
     * Restituisce le informazioni sull'anagrafica azienda.
     *
     * @return array
     */
    public static function getAzienda()
    {
        if (empty(static::$azienda)) {
            static::$azienda = static::getAnagrafica(setting('Azienda predefinita'));
        }

        return static::$azienda;
    }

    /**
     * Restituisce le informazioni sull'anagrafica cliente legata al documento.
     *
     * @return array
     */
    public function getCliente()
    {
        if (empty($this->cliente)) {
            $cliente = static::getAnagrafica($this->getDocumento()['idanagrafica']);

            $sede = database()->fetchOne('SELECT `codice_destinatario` FROM `an_sedi` WHERE `id` = '.prepare($this->getDocumento()['idsede']));
            if (!empty($sede)) {
                $cliente['codice_destinatario'] = $sede['codice_destinatario'];
            }

            $this->cliente = $cliente;
        }

        return $this->cliente;
    }

    /**
     * Restituisce le informazioni sull'anagrafica dell'intermediario.
     *
     * @return array
     */
    public function getIntermediario()
    {
        if (empty($this->intermediario)) {
            $intermediario = static::getAnagrafica(setting('Terzo intermediario'));
            $this->intermediario = $intermediario;
        }

        return $this->intermediario;
    }

    /**
     * Restituisce le informazioni riguardanti un anagrafica sulla base dell'identificativo fornito.
     *
     * @param int $id
     *
     * @return array
     */
    public static function getAnagrafica($id)
    {
        return database()->fetchOne('SELECT *, (SELECT `iso2` FROM `an_nazioni` WHERE `an_nazioni`.`id` = `an_anagrafiche`.`id_nazione`) AS nazione FROM `an_anagrafiche` WHERE `idanagrafica` = '.prepare($id));
    }

    /**
     * Restituisce le righe del documento.
     *
     * @return array
     */
    public function getRighe()
    {
        if (empty($this->righe)) {
            //AND is_descrizione = 0
            $this->righe = database()->fetchArray('SELECT * FROM `co_righe_documenti` WHERE `sconto_globale` = 0  AND `iddocumento` = '.prepare($this->getDocumento()['id']));
        }

        return $this->righe;
    }

    /**
     * Restituisce i contratti collegati al documento (contratti e interventi).
     *
     * @return array
     */
    public function getContratti()
    {
        if (empty($this->contratti)) {
            $documento = $this->getDocumento();
            $database = database();

            $contratti = $database->fetchArray('SELECT `id_documento_fe`, `codice_cig`, `codice_cup` FROM `co_contratti` INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`idcontratto` = `co_contratti`.`id` WHERE `co_righe_documenti`.`iddocumento` = '.prepare($documento['id']).' AND `id_documento_fe` IS NOT NULL');

            $interventi = $database->fetchArray('SELECT `id_documento_fe`, `codice_cig`, `codice_cup` FROM `in_interventi` INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`idintervento` = `in_interventi`.`id` WHERE `co_righe_documenti`.`iddocumento` = '.prepare($documento['id']).' AND `id_documento_fe` IS NOT NULL');

            $this->contratti = array_merge($contratti, $interventi);
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

            $ordini = $database->fetchArray('SELECT `id_documento_fe`, `codice_cig`, `codice_cup` FROM `or_ordini` INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`idordine` = `or_ordini`.`id` WHERE `co_righe_documenti`.`iddocumento` = '.prepare($documento['id']).' AND `id_documento_fe` IS NOT NULL');

            $this->ordini = $ordini;
        }

        return $this->ordini;
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

            $note_accredito = $database->fetchArray('SELECT numero_esterno, data FROM co_documenti WHERE id='.prepare($documento['ref_documento']));

            $this->fatture_collegate = $note_accredito;
        }

        return $this->fatture_collegate;
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
        if (!isset($this->errors)) {
            $this->toXML();
        }

        return $this->errors;
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
     * @param string $directory
     *
     * @return string Nome del file
     */
    public function save($directory)
    {
        $name = 'Fattura Elettronica';
        $previous = $this->getFilename();
        $data = $this->getUploadData();

        Uploads::delete($previous, $data);

        // Generazione nome XML
        $filename = $this->getFilename(true);

        // Salvataggio del file
        $file = rtrim($directory, '/').'/'.$filename;
        $result = directory($directory) && file_put_contents($file, $this->toXML());

        // Registrazione come allegato
        Uploads::register(array_merge([
            'name' => $name,
            'original' => $filename,
        ], $data));

        // Aggiornamento effettivo
        database()->update('co_documenti', [
            'progressivo_invio' => $this->getDocumento()['progressivo_invio'],
            'codice_stato_fe' => 'GEN',
        ], ['id' => $this->getDocumento()['id']]);

        return ($result === false) ? null : $filename;
    }

    /**
     * Restituisce il nome del file XML per la fattura elettronica.
     *
     * @return string
     */
    public function getFilename($new = false)
    {
        $azienda = static::getAzienda();
        $prefix = 'IT'.(!empty($azienda['codice_fiscale']) ? $azienda['codice_fiscale'] : $azienda['piva']);

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
                'xsi:schemaLocation' => 'http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2 http://www.fatturapa.gov.it/export/fatturazione/sdi/fatturapa/v1.2/Schema_del_file_xml_FatturaPA_versione_1.2.xsd',
            ];
            foreach ($attributes as $key => $value) {
                $rootNode->setAttribute($key, $value);
            }

            // Generazione della fattura elettronica
            $xml = static::prepareForXML([
                'FatturaElettronicaHeader' => static::getHeader($this),
                'FatturaElettronicaBody' => static::getBody($this),
            ]);
            $fattura->add($xml);

            $this->xml = $fattura->__toString();
        }

        return $this->xml;
    }

    /**
     * Restituisce l'array responsabile per la generazione del tag DatiTrasmission.
     *
     * @return array
     */
    protected static function getDatiTrasmissione($fattura)
    {
        $azienda = static::getAzienda();
        $documento = $fattura->getDocumento();
        $cliente = $fattura->getCliente();

        // Se sto fatturando ad un ente pubblico il codice destinatario di default è 99999 (sei nove), in alternativa uso 0000000 (sette zeri)
        $default_code = ($cliente['tipo'] == 'Ente pubblico') ? '999999' : '0000000';
        // Se il mio cliente non ha sede in Italia il codice destinatario di default diventa (XXXXXXX) (sette X)
        $default_code = ($cliente['nazione'] != 'IT') ? 'XXXXXXX' : $default_code;

        // Generazione dell'header
        // Se all'Anagrafe Tributaria il trasmittente è censito con il codice fiscale
        $result = [
            'IdTrasmittente' => [
                'IdPaese' => $azienda['nazione'],
                'IdCodice' => (!empty($azienda['codice_fiscale'])) ? $azienda['codice_fiscale'] : $azienda['piva'],
            ],
            'ProgressivoInvio' => $documento['progressivo_invio'],
            'FormatoTrasmissione' => ($cliente['tipo'] == 'Ente pubblico') ? 'FPA12' : 'FPR12',
            'CodiceDestinatario' => !empty($cliente['codice_destinatario']) ? $cliente['codice_destinatario'] : $default_code,
        ];

        // Telefono di contatto
        if (!empty($azienda['telefono'])) {
            $result['ContattiTrasmittente']['Telefono'] = $azienda['telefono'];
        }

        // Email di contatto
        if (!empty($azienda['email'])) {
            $result['ContattiTrasmittente']['Email'] = $azienda['email'];
        }

        // Inizializzazione PEC solo se anagrafica azienda e codice destinatario non compilato, per privato e PA la PEC non serve
        if (empty($cliente['codice_destinatario']) && $cliente['tipo'] == 'Azienda') {
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
            if (!empty($anagrafica['nazione'])) {
                $result['IdFiscaleIVA']['IdPaese'] = $anagrafica['nazione'];
            }

            $result['IdFiscaleIVA']['IdCodice'] = $anagrafica['piva'];
        }

        // Codice fiscale
        if (!empty($anagrafica['codice_fiscale'])) {
            $result['CodiceFiscale'] = $anagrafica['codice_fiscale'];
        }
		
		if (!empty($anagrafica['nome']) or !empty($anagrafica['cognome'])){
			
			$result['Anagrafica'] = [
				//'Denominazione' => $anagrafica['ragione_sociale'],
				'Nome' => $anagrafica['nome'],
				'Cognome' => $anagrafica['cognome'],
				// TODO: 'Titolo' => $anagrafica['ragione_sociale'],
				// TODO: CodEORI
			];
			
			
		}else{
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
     * @return array
     */
    protected static function getSede($anagrafica)
    {
        $result = [
            'Indirizzo' => $anagrafica['indirizzo'],
            'CAP' => $anagrafica['cap'],
            'Comune' => $anagrafica['citta'],
        ];

        // Provincia impostata e SOLO SE nazione ITALIA
        if (!empty($anagrafica['provincia']) && $anagrafica['nazione'] == 'IT') {
            $result['Provincia'] = strtoupper($anagrafica['provincia']);
        }

        $result['Nazione'] = $anagrafica['nazione'];

        return $result;
    }

    /**
     * Restituisce l'array responsabile per la generazione del tag CedentePrestatore.
     *
     * @return array
     */
    protected static function getCedentePrestatore($fattura)
    {
        $azienda = static::getAzienda();

        $result = [
            'DatiAnagrafici' => static::getDatiAnagrafici($azienda, true),
            'Sede' => static::getSede($azienda),
        ];

        // IscrizioneREA
        if (!empty($azienda['codicerea'])) {
            $codice = explode('-', $azienda['codicerea']);

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

        return $result;
    }

    /**
     * Restituisce l'array responsabile per la generazione del tag CessionarioCommittente (1.4).
     *
     * @return array
     */
    protected static function getCessionarioCommittente($fattura)
    {
        $cliente = $fattura->getCliente();

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
        $intermediario = $fattura->getIntermediario();

        $result = [
            'DatiAnagrafici' => static::getDatiAnagrafici($intermediario),
        ];

        return $result;
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

        $fattura = Modules\Fatture\Fattura::find($documento['id']);

        $result = [
            'TipoDocumento' => $documento['tipo_documento'],
            'Divisa' => 'EUR',
            'Data' => $documento['data'],
            'Numero' => $documento['numero_esterno'],
            // TODO: 'Causale' => $documento['causale'],
        ];

        // Ritenuta d'Acconto
        $righe = $fattura->getRighe();
        $id_ritenuta = null;
        $id_rivalsainps = null;
        $totale_rivalsainps = 0;
        $totale_ritenutaacconto = 0;
        foreach ($righe as $riga) {
            if (!empty($riga['idritenutaacconto'])) {
                $id_ritenuta = $riga['idritenutaacconto'];
                $totale_ritenutaacconto += $riga['ritenutaacconto'];
            }

            if (!empty($riga['idrivalsainps'])) {
                $id_rivalsainps = $riga['idrivalsainps'];
                $totale_rivalsainps += $riga['rivalsainps'];
                $aliquota_iva_rivalsainps = $riga['idiva'];
            }
        }

        if (!empty($id_ritenuta)) {
            $percentuale = database()->fetchOne('SELECT percentuale FROM co_ritenutaacconto WHERE id = '.prepare($id_ritenuta))['percentuale'];

            $result['DatiRitenuta'] = [
                'TipoRitenuta' => ($azienda['tipo'] == 'Privato') ? 'RT01' : 'RT02',
                'ImportoRitenuta' => $totale_ritenutaacconto,
                'AliquotaRitenuta' => $percentuale,
                'CausalePagamento' => setting("Causale ritenuta d'acconto"),
            ];
        }

        // Bollo (2.1.1.6)
        $documento['bollo'] = floatval($documento['bollo']);
        if (!empty($documento['bollo'])) {
            $result['DatiBollo'] = [
                'BolloVirtuale' => 'SI',
                'ImportoBollo' => $documento['bollo'],
            ];
        }

        // Cassa Previdenziale
        if (!empty($id_rivalsainps)) {
            $iva = database()->fetchOne('SELECT `percentuale`, `codice_natura_fe` FROM `co_iva` WHERE `id` = '.prepare($aliquota_iva_rivalsainps));
            $percentuale = database()->fetchOne('SELECT percentuale FROM co_rivalsainps WHERE id = '.prepare($id_rivalsainps))['percentuale'];

            $dati_cassa = [
                'TipoCassa' => setting('Tipo Cassa'),
                'AlCassa' => $percentuale,
                'ImportoContributoCassa' => $totale_rivalsainps,
                'ImponibileCassa' => $fattura->imponibile,
                'AliquotaIVA' => $iva['percentuale'],
            ];

            $ritenuta_predefinita = setting("Percentuale ritenuta d'acconto");
            if (!empty($ritenuta_predefinita)) {
                $dati_cassa['Ritenuta'] = 'SI';
            }

            $dati_cassa['Natura'] = $iva['codice_natura_fe'];
            $dati_cassa['RiferimentoAmministrazione'] = '';

            $result['DatiCassaPrevidenziale'] = $dati_cassa;
        }

        // Sconto globale (2.1.1.8)
        $documento['sconto_globale'] = floatval($documento['sconto_globale']);
        if (!empty($documento['sconto_globale'])) {
            $sconto = [
                'Tipo' => $documento['sconto_globale'] > 0 ? 'SC' : 'MG',
            ];

            if ($documento['tipo_sconto_globale'] == 'PRC') {
                $sconto['Percentuale'] = $documento['sconto_globale'];
            } else {
                $sconto['Importo'] = $documento['sconto_globale'];
            }

            $result['ScontoMaggiorazione'] = $sconto;
        }

        // Importo Totale Documento (2.1.1.9)
        // Importo totale del documento al netto dell'eventuale sconto e comprensivo di imposta a debito del cessionario / committente
        $fattura = Modules\Fatture\Fattura::find($documento['id']);
        $result['ImportoTotaleDocumento'] = abs($fattura->netto);

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

        if ($documento['idvettore']) {
            $vettore = static::getAnagrafica($documento['idvettore']);
            $result['DatiAnagraficiVettore'] = static::getDatiAnagrafici($vettore);
        }

        $result['CausaleTrasporto'] = $causale;
        $result['NumeroColli'] = $documento['n_colli'];
        $result['Descrizione'] = $aspetto;

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
    protected static function getDatiOrdineAcquisto($fattura)
    {
        $ordini = $fattura->getOrdiniAcquisto();

        $result = [];
        foreach ($ordini as $element) {
            if (!empty($element['id_documento_fe'])) {
                $dati = [
                    'IdDocumento' => $element['id_documento_fe'],
                ];
            }

            if (!empty($element['codice_cig'])) {
                $dati['CodiceCIG'] = $element['codice_cig'];
            }

            if (!empty($element['codice_cup'])) {
                $dati['CodiceCUP'] = $element['codice_cup'];
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

        $result = [];
        foreach ($contratti as $element) {
            if (!empty($element['id_documento_fe'])) {
                $dati = [
                    'IdDocumento' => $element['id_documento_fe'],
                ];
            }

            if (!empty($element['codice_cig'])) {
                $dati['CodiceCIG'] = $element['codice_cig'];
            }

            if (!empty($element['codice_cup'])) {
                $dati['CodiceCUP'] = $element['codice_cup'];
            }

            $result[] = $dati;
        }

        return $result;
    }

    /**
     * Restituisce l'array responsabile per la generazione del tag DatiFattureCollegate.
     *
     * @return array
     */
    protected static function getDatiFattureCollegate($fattura)
    {
        $fatture = $fattura->getFattureCollegate();

        $result = [];
        foreach ($fatture as $element) {
            $result[] = [
                'IdDocumento' => $element['numero_esterno'],
                'Data' => $element['data'],
            ];
        }

        return $result;
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

        if ($documento['tipo'] == 'Fattura accompagnatoria di vendita') {
            $result['DatiTrasporto'] = static::getDatiTrasporto($fattura);
        }

        return $result;
    }

    /**
     * Restituisce l'array responsabile per la generazione del tag DatiBeniServizi.
     *
     * @return array
     */
    protected static function getDatiBeniServizi($fattura)
    {
        $documento = $fattura->getDocumento();

        $database = database();

        $result = [];

        // Righe del documento
        $righe_documento = $fattura->getRighe();
        foreach ($righe_documento as $numero => $riga) {
            $riga['subtotale'] = abs($riga['subtotale']);
            $riga['qta'] = abs($riga['qta']);
            $riga['sconto'] = abs($riga['sconto']);
			
			//Fix per righe di tipo descrizione, copio idiva dalla prima riga del documento che non è di tipo descrizione, riportando di conseguenza eventuali % e/o nature
			if (!empty($riga['is_descrizione'])){
				$riga['idiva'] = $database->fetchOne('SELECT `idiva` FROM `co_righe_documenti` WHERE `is_descrizione` = 0 AND `iddocumento` = '.prepare($documento['id']))['idiva'];
			}
			
			//Fix per qta, deve sempre essere impostata almeno a 1
            $riga['qta'] = (!empty($riga['qta'])) ? $riga['qta'] : 1;

            $prezzo_unitario = $riga['subtotale'] / $riga['qta'];
            $prezzo_totale = $riga['subtotale'] - $riga['sconto'];

            $iva = $database->fetchOne('SELECT `percentuale`, `codice_natura_fe` FROM `co_iva` WHERE `id` = '.prepare($riga['idiva']));
            $percentuale = floatval($iva['percentuale']);

            $dettaglio = [
                'NumeroLinea' => $numero + 1,
            ];

            // 2.2.1.2
            if (!empty($riga['tipo_cessione_prestazione'])) {
                $dettaglio['TipoCessionePrestazione'] = $riga['tipo_cessione_prestazione'];
            }

            //2.2.1.3
            if (!empty($riga['idarticolo'])) {
                $codice_articolo = [
                    'CodiceTipo' => 'OSM',
                    'CodiceValore' => $database->fetchOne('SELECT `codice` FROM `mg_articoli` WHERE `id` = '.prepare($riga['idarticolo']))['codice'],
                ];

                $dettaglio['CodiceArticolo'] = $codice_articolo;
            }

            $dettaglio['Descrizione'] = $riga['descrizione'];
            $dettaglio['Quantita'] = $riga['qta'];

            if (!empty($riga['um'])) {
                $dettaglio['UnitaMisura'] = $riga['um'];
            }

            if (!empty($riga['data_inizio_periodo'])) {
                $dettaglio['DataInizioPeriodo'] = $riga['data_inizio_periodo'];
            }
            if (!empty($riga['data_fine_periodo'])) {
                $dettaglio['DataFinePeriodo'] = $riga['data_fine_periodo'];
            }

            $dettaglio['PrezzoUnitario'] = $prezzo_unitario;

            // Sconto (2.2.1.10)
            $riga['sconto_unitario'] = floatval($riga['sconto_unitario']);
            if (!empty($riga['sconto_unitario'])) {
                $sconto = [
                    'Tipo' => $riga['sconto_unitario'] > 0 ? 'SC' : 'MG',
                ];

                if ($riga['tipo_sconto'] == 'PRC') {
                    $sconto['Percentuale'] = $riga['sconto_unitario'];
                } else {
                    $sconto['Importo'] = $riga['sconto_unitario'];
                }

                $dettaglio['ScontoMaggiorazione'] = $sconto;
            }

            $dettaglio['PrezzoTotale'] = $prezzo_totale;
            $dettaglio['AliquotaIVA'] = $percentuale;

            if (!empty($riga['idritenutaacconto'])) {
                $dettaglio['Ritenuta'] = 'SI';
            }

            if (empty($percentuale)) {
                //Controllo aggiuntivo codice_natura_fe per evitare che venga riportato il tag vuoto
                if (!empty($iva['codice_natura_fe'])) {
                    $dettaglio['Natura'] = $iva['codice_natura_fe'];
                }
            }

            if (!empty($riga['riferimento_amministrazione'])) {
                $dettaglio['RiferimentoAmministrazione'] = $riga['riferimento_amministrazione'];
            }
			
			
			 // AltriDatiGestionali (2.2.1.16) - Ritenuta ENASARCO
            $riga['ritenutaenasarco'] = floatval($riga['ritenutaenasarco']);
            if (!empty($riga['ritenutaenasarco'])) {
				
                $ritenutaenasarco = [
                    'TipoDato' => 'CASSA-PREV',
					'RiferimentoTesto' => 'ENASARCO - TC07',
					'RiferimentoNumero' => $riga['ritenutaenasarco'],
                ];

                $dettaglio['AltriDatiGestionali'] = $ritenutaenasarco;
            }
			
			
            $result[] = [
                'DettaglioLinee' => $dettaglio,
            ];
        }

        // Riepiloghi per IVA per percentuale
        $riepiloghi_percentuale = $database->fetchArray('SELECT SUM(`co_righe_documenti`.`subtotale` - `co_righe_documenti`.`sconto`) as totale, SUM(`co_righe_documenti`.`iva`) as iva, `co_iva`.`esigibilita`, `co_iva`.`percentuale`, `co_iva`.`dicitura` FROM `co_righe_documenti` INNER JOIN `co_iva` ON `co_iva`.`id` = `co_righe_documenti`.`idiva` WHERE `co_righe_documenti`.`iddocumento` = '.prepare($documento['id']).' AND
        `co_iva`.`codice_natura_fe` IS NULL AND sconto_globale=0 GROUP BY `co_iva`.`percentuale`');
        foreach ($riepiloghi_percentuale as $riepilogo) {
            $iva = [
                'AliquotaIVA' => $riepilogo['percentuale'],
                'ImponibileImporto' => abs($riepilogo['totale']),
                'Imposta' => abs($riepilogo['iva']),
                'EsigibilitaIVA' => $riepilogo['esigibilita'],
            ];

            //Con split payment EsigibilitaIVA sempre a S
            if ($documento['split_payment']) {
                $iva['EsigibilitaIVA'] = 'S';
            }

            // TODO: la dicitura può essere diversa tra diverse IVA con stessa percentuale/natura
            // nei riepiloghi viene fatto un accorpamento percentuale/natura
            if (!empty($riepilogo['dicitura'])) {
                // $iva['RiferimentoNormativo'] = $riepilogo['dicitura'];
            }

            $result[] = [
                'DatiRiepilogo' => $iva,
            ];
        }

        // Riepiloghi per IVA per natura
        $riepiloghi_natura = $database->fetchArray('SELECT SUM(`co_righe_documenti`.`subtotale` - `co_righe_documenti`.`sconto`) as totale, SUM(`co_righe_documenti`.`iva`) as iva, `co_iva`.`esigibilita`, `co_iva`.`codice_natura_fe` FROM `co_righe_documenti` INNER JOIN `co_iva` ON `co_iva`.`id` = `co_righe_documenti`.`idiva` WHERE `co_righe_documenti`.`iddocumento` = '.prepare($documento['id']).' AND
        `co_iva`.`codice_natura_fe` IS NOT NULL GROUP BY `co_iva`.`codice_natura_fe`');
        foreach ($riepiloghi_natura as $riepilogo) {
            $iva = [
                'AliquotaIVA' => 0,
                'Natura' => $riepilogo['codice_natura_fe'],
                'ImponibileImporto' => abs($riepilogo['totale']),
                'Imposta' => abs($riepilogo['iva']),
                'EsigibilitaIVA' => $riepilogo['esigibilita'],
            ];

            //Con split payment EsigibilitaIVA sempre a S
            if ($documento['split_payment']) {
                $iva['EsigibilitaIVA'] = 'S';
            }

            $result[] = [
                'DatiRiepilogo' => $iva,
            ];
        }

        return $result;
    }

    /**
     * Restituisce l'array responsabile per la generazione del tag DatiPagamento.
     *
     * @return array
     */
    protected static function getDatiPagamento($fattura)
    {
        $documento = $fattura->getDocumento();

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

            if (!empty($documento['idbanca'])) {
                $co_banche = $database->fetchOne('SELECT * FROM co_banche WHERE id = '.prepare($documento['idbanca']));
                if (!empty($co_banche['nome'])) {
                    $pagamento['IstitutoFinanziario'] = $co_banche['nome'];
                }
                if (!empty($co_banche['iban'])) {
                    $pagamento['IBAN'] = $co_banche['iban'];
                }
                if (!empty($co_banche['bic'])) {
                    $pagamento['BIC'] = $co_banche['bic'];
                }
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
            if ($allegato['category'] == 'Fattura Elettronica') {
                $file = DOCROOT.'/'.$directory.'/'.$allegato['filename'];

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
        $dir = static::getDirectory();

        $rapportino_nome = sanitizeFilename($documento['numero_esterno'].'.pdf');
        $filename = slashes(DOCROOT.'/'.$dir.'/'.$rapportino_nome);

        Uploads::delete($rapportino_nome, $data);

        $print = Prints::getModulePredefinedPrint($id_module);
        Prints::render($print['id'], $documento['id'], $filename);

        Uploads::register(array_merge([
            'name' => 'Stampa allegata',
            'original' => $rapportino_nome,
        ], $data));

        $attachments[] = [
            'NomeAttachment' => 'Fattura',
            'FormatoAttachment' => 'PDF',
            'Attachment' => base64_encode(file_get_contents($filename)),
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

        //Terzo Intermediario o Soggetto Emittente
        if (!empty(setting('Terzo intermediario'))) {
            $result['TerzoIntermediarioOSoggettoEmittente'] = static::getTerzoIntermediarioOSoggettoEmittente($fattura);
            $result['SoggettoEmittente'] = 'TZ';
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
            $info = static::$validators[$key];
            $size = isset($info['size']) ? $info['size'] : null;

            $output = $input;

            // Operazioni di normalizzazione
            // Formattazione decimali
            if ($info['type'] == 'decimal') {
                $output = number_format($output, 2, '.', '');
            }

            // Formattazione date
            elseif ($info['type'] == 'date') {
                $object = DateTime::createFromFormat('Y-m-d H:i:s', $output);
                if (is_object($object)) {
                    $output = $object->format('Y-m-d');
                }
            }

            // Formattazione testo
            elseif ($info['type'] == 'string') {
            }

            // Riduzione delle dimensioni
            if ($info['type'] != 'integer' && isset($size[1])) {
                $output = trim($output);
                $output = S::create($output)->substr(0, $size[1])->__toString();
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

                // Segnalazione dell'irregolarità
                if (!intval($validation)) {
                    $this->errors[] = $key;
                }
            }
        }

        return $output;
    }

    protected function getUploadData()
    {
        return [
            'category' => tr('Fattura elettronica'),
            'id_module' => Modules::get('Fatture di vendita')['id'],
            'id_record' => $this->getDocumento()['id'],
        ];
    }
}
