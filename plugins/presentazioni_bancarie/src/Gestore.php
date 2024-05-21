<?php

namespace Plugins\PresentazioniBancarie;

use Carbon\Carbon;
use Digitick\Sepa\PaymentInformation;
use Digitick\Sepa\TransferFile\Factory\TransferFileFacadeFactory;
use Modules\Anagrafiche\Anagrafica;
use Modules\Banche\Banca;
use Modules\Scadenzario\Scadenza;
use Plugins\PresentazioniBancarie\Cbi\CbiSepa;
use Plugins\PresentazioniBancarie\Cbi\Intestazione;
use Plugins\PresentazioniBancarie\Cbi\RiBa;
use Plugins\PresentazioniBancarie\Cbi\Ricevuta;
use Sdd\DirectDebit as DirectDebitCBI;
use Sdd\DirectDebit\GroupHeader as HeaderCBI;
use Sdd\DirectDebit\Payment as PaymentCBI;
use Sdd\DirectDebit\PaymentInformation as PaymentInformationCBI;

class Gestore
{
    /**
     * @var Anagrafica|null
     */
    protected static $azienda_predefinita;

    /**
     * @var Banca|null
     */
    protected static $banca_predefinita_azienda;

    protected $id_riba;
    protected $id_bonifico;
    protected $id_debito_diretto;
    protected $id_credito_diretto;

    protected $riba;
    protected $debito_diretto;
    protected $credito_diretto;
    protected $bonifico;

    protected $numero_transazioni_debito_diretto = 0;

    protected $totale_debito_diretto = 0;

    /**
     * @var Anagrafica
     */
    private $azienda;
    /**
     * @var Banca
     */
    private $banca_azienda;

    public function __construct(Anagrafica $azienda, Banca $banca_azienda)
    {
        $this->azienda = $azienda;
        $this->banca_azienda = $banca_azienda;

        $this->id_riba = random_string();
        $this->initRiBa();

        $this->id_bonifico = random_string();
        $this->initBonifico();

        $this->id_credito_diretto = random_string();
        $this->initCreditoDiretto();

        $this->id_debito_diretto = random_string();
        $this->initDebitoDiretto();
    }

    /**
     * Inizializzazione del formato per il sistema RiBa.
     */
    public function initRiBa()
    {
        $iban = $this->banca_azienda->iban;
        $conto = mb_substr($iban, 15, 12);
        $abi_assuntrice = mb_substr($iban, 5, 5);
        $cab_assuntrice = mb_substr($iban, 10, 5);

        $data = new Carbon();
        $supporto = $data->format('dmYHis');

        // Generazione intestazione
        $intestazione = new Intestazione();
        $intestazione->codice_sia = $this->banca_azienda['codice_sia'];
        $intestazione->conto = $conto;
        $intestazione->abi = $abi_assuntrice;
        $intestazione->cab = $cab_assuntrice;
        $intestazione->data_creazione = $data->format('dmy');
        $intestazione->nome_supporto = $supporto;
        $intestazione->citta_creditore = $this::cleanString(mb_strtoupper($this->azienda['cap'].' '.$this->azienda['citta'].' '.$this->azienda['provincia']));
        $intestazione->ragione_sociale_creditore = $this::cleanString(mb_strtoupper($this->azienda->ragione_sociale));
        $intestazione->indirizzo_creditore = $this::cleanString(mb_strtoupper($this->azienda['indirizzo']));
        $intestazione->partita_iva_o_codice_fiscale_creditore = !empty($this->azienda->partita_iva) ? $this->azienda->partita_iva : $this->azienda->codice_fiscale;

        $this->riba = new RiBa($intestazione);
    }

    /**
     * Inizializzazione del formato per il sistema Bonifico.
     */
    public function initBonifico()
    {
        $iban = $this->banca_azienda->iban;
        $conto = mb_substr($iban, 15, 12);
        $abi_assuntrice = mb_substr($iban, 5, 5);
        $cab_assuntrice = mb_substr($iban, 10, 5);
        $descrizione_banca = $this->banca_azienda->nome.' '.$this->banca_azienda->filiale;

        $data = new Carbon();
        $supporto = $data->format('dmYHis');

        // Generazione intestazione
        $intestazione = new Intestazione();
        $intestazione->codice_sia = $this->banca_azienda['codice_sia'];
        $intestazione->conto = $conto;
        $intestazione->abi = $abi_assuntrice;
        $intestazione->cab = $cab_assuntrice;
        $intestazione->iban = $iban;
        $intestazione->data_creazione = $data->format('dmy');
        $intestazione->nome_supporto = $supporto;
        $intestazione->citta_creditore = mb_strtoupper($this->azienda['cap'].' '.$this->azienda['citta'].' '.$this->azienda['provincia']);
        $intestazione->ragione_sociale_creditore = mb_strtoupper($this->azienda->ragione_sociale);
        $intestazione->indirizzo_creditore = mb_strtoupper($this->azienda['indirizzo']);
        $intestazione->partita_iva_o_codice_fiscale_creditore = !empty($this->azienda->partita_iva) ? $this->azienda->partita_iva : $this->azienda->codice_fiscale;
        $intestazione->identificativo_creditore = !empty($this->azienda->partita_iva) ? $this->azienda->partita_iva : $this->azienda->codice_fiscale;
        $intestazione->descrizione_banca = $descrizione_banca;

        $this->bonifico = new CbiSepa($intestazione);
    }

    /**
     * Inizializzazione del formato per il credito diretto.
     *
     * @source https://github.com/php-sepa-xml/php-sepa-xml/blob/master/doc/direct_credit.md
     */
    public function initCreditoDiretto()
    {
        $this->credito_diretto = TransferFileFacadeFactory::createCustomerCredit($this->id_credito_diretto, $this->azienda->ragione_sociale);
    }

    /**
     * Inizializzazione del formato per il debito diretto.
     *
     * @source https://github.com/php-sepa-xml/php-sepa-xml/blob/master/doc/direct_debit.md
     */
    public function initDebitoDirettoSEPA()
    {
        $this->debito_diretto = TransferFileFacadeFactory::createDirectDebit($this->id_debito_diretto, $this->azienda->ragione_sociale);
    }

    /**
     * Inizializzazione del formato per il debito diretto.
     *
     * @source https://github.com/wdog/sdd_ita/blob/master/tests/DirectDebitTest.php
     */
    public function initDebitoDiretto()
    {
        $this->debito_diretto = new DirectDebitCBI();
    }

    public function aggiungi(Scadenza $scadenza, int $identifier, string $descrizione, ?string $codice_sequenza = null)
    {
        $documento = $scadenza->documento;
        $controparte = $scadenza->anagrafica;
        $banca_controparte = self::getBancaControparte($scadenza);
        if (empty($banca_controparte)) {
            return false;
        }
        $ctgypurp = $this->getTipo($scadenza)['ctgypurp'] ?: 'SUPP';

        $pagamento = $documento->pagamento;
        $direzione = $documento->direzione;
        $importo = $scadenza->da_pagare - $scadenza->pagato;
        $totale = (abs($scadenza->da_pagare) - abs($scadenza->pagato));

        $is_credito_diretto = ($direzione == 'uscita' && in_array($pagamento->codice_modalita_pagamento_fe, ['MP09', 'MP10', 'MP11', 'MP19', 'MP20', 'MP21'])) || (empty($documento) && $importo < 0 && $ctgypurp != 'SALA');
        $is_debito_diretto = $direzione == 'entrata' && in_array($pagamento->codice_modalita_pagamento_fe, ['MP09', 'MP10', 'MP11', 'MP19', 'MP20', 'MP21']) && !empty($this->banca_azienda->creditor_id); // Mandato SEPA disponibile
        $is_riba = $direzione == 'entrata' && in_array($pagamento->codice_modalita_pagamento_fe, ['MP12']) && !empty($this->banca_azienda->codice_sia);
        $is_bonifico = $direzione == 'uscita' && in_array($pagamento->codice_modalita_pagamento_fe, ['MP05']) && !empty($this->banca_azienda->codice_sia) || (empty($documento) && $importo < 0);

        if (in_array($pagamento->codice_modalita_pagamento_fe, ['MP19', 'MP21'])) {
            $method = 'B2B';
        } elseif (in_array($pagamento->codice_modalita_pagamento_fe, ['MP20'])) {
            $method = 'CORE';
        }

        if ($is_credito_diretto) {
            return $this->aggiungiCreditoDiretto($identifier, $controparte, $banca_controparte, $descrizione, $totale, $scadenza->scadenza, $ctgypurp);
        } elseif ($is_debito_diretto) {
            return $this->aggiungiDebitoDiretto($identifier, $controparte, $banca_controparte, $descrizione, $totale, $scadenza->scadenza, $method, $codice_sequenza);
        } elseif ($is_riba) {
            $totale = $totale * 100;

            return $this->aggiungiRiBa($identifier, $controparte, $banca_controparte, $descrizione, $totale, $scadenza->scadenza);
        } elseif ($is_bonifico) {
            return $this->aggiungiBonifico($identifier, $controparte, $banca_controparte, $descrizione, $totale, $scadenza->scadenza, $ctgypurp);
        }

        return false;
    }

    public function aggiungiRiBa(int $identifier, Anagrafica $controparte, Banca $banca_controparte, string $descrizione, int $totale, \DateTime $data_prevista)
    {
        $data_scadenza = $data_prevista->format('dmy');

        // Dati banca cliente
        $abi_cliente = $banca_controparte['bank_code'];
        $cab_cliente = $banca_controparte['branch_code'];

        $descrizione_banca = $banca_controparte['nome'].' '.$banca_controparte['filiale'];

        // Aggiunta codice CIG CUP se presenti
        if (!empty($controparte['cig'])) {
            $descrizione .= ' CIG:'.$controparte['cig'];
        }

        if (!empty($controparte['cup'])) {
            $descrizione .= ' CUP:'.$controparte['cup'];
        }

        // Salvataggio della singola ricevuta nel RiBa
        $ricevuta = new Ricevuta();
        $ricevuta->numero_ricevuta = $identifier;
        $ricevuta->scadenza = $data_scadenza;
        $ricevuta->importo = $totale;
        $ricevuta->abi_banca = $abi_cliente;
        $ricevuta->cab_banca = $cab_cliente;
        $ricevuta->codice_cliente = $controparte['codice'];

        // controlli sulla ragione sociale
        $ragione_sociale = $this::cleanString($controparte['ragione_sociale']);

        // Sostituzione di alcuni simboli noti
        $replaces = [
            '&#039;' => "'",
            '&quot;' => "'",
            '&amp;' => '',
        ];
        $ragione_sociale = str_replace(array_keys($replaces), array_values($replaces), $ragione_sociale);

        $ricevuta->nome_debitore = $this::cleanString(mb_strtoupper($ragione_sociale));
        $ricevuta->identificativo_debitore = !empty($controparte->partita_iva) ? $controparte->partita_iva : $controparte->codice_fiscale;
        $ricevuta->indirizzo_debitore = $this::cleanString(mb_strtoupper($controparte['indirizzo']));
        $ricevuta->cap_debitore = $controparte['cap'];
        $ricevuta->comune_debitore = $this::cleanString(mb_strtoupper($controparte['citta']));
        $ricevuta->provincia_debitore = $this::cleanString($controparte['provincia']);
        $ricevuta->descrizione_banca = $this::cleanString($descrizione_banca);
        $ricevuta->descrizione = $this::cleanString(mb_strtoupper($descrizione));

        $this->riba->addRicevuta($ricevuta);

        return true;
    }

    public function aggiungiBonifico(int $identifier, Anagrafica $controparte, Banca $banca_controparte, string $descrizione, float $totale, \DateTime $data_prevista, $ctgypurp)
    {
        $data_scadenza = $data_prevista->format('dmy');

        // Dati banca cliente
        $abi_cliente = $banca_controparte['bank_code'];
        $cab_cliente = $banca_controparte['branch_code'];

        $descrizione_banca = $banca_controparte['nome'].' '.$banca_controparte['filiale'];

        // Aggiunta codice CIG CUP se presenti
        if (!empty($controparte['cig'])) {
            $descrizione .= ' CIG:'.$controparte['cig'];
        }

        if (!empty($controparte['cup'])) {
            $descrizione .= ' CUP:'.$controparte['cup'];
        }

        // Unifico ricevute per anagrafica
        $identificativo_debitore = (!empty($controparte->partita_iva) ? $controparte->partita_iva : (!empty($controparte->codice_fiscale) ? $controparte->codice_fiscale : $controparte->codice));
        $ricevute = $this->bonifico->getRicevute();
        foreach ($ricevute as $ric) {
            if ($ric->identificativo_debitore == $identificativo_debitore) {
                $ricevuta = $ric;
            }
        }

        if (empty($ricevuta)) {
            $ricevuta = new Ricevuta();
            $ricevuta->numero_ricevuta = $identifier;
            $ricevuta->scadenza = $data_scadenza;
            $ricevuta->importo = $totale;
            $ricevuta->abi_banca = $abi_cliente;
            $ricevuta->cab_banca = $cab_cliente;
            $ricevuta->iban = $banca_controparte['iban'];
            $ricevuta->codice_cliente = $controparte['codice'];
            $ricevuta->ctgypurp = $ctgypurp;

            // controlli sulla ragione sociale
            $ragione_sociale = $controparte['ragione_sociale'];

            // Sostituzione di alcuni simboli noti
            $replaces = [
                '&#039;' => "'",
                '&quot;' => "'",
            ];
            $ragione_sociale = str_replace(array_keys($replaces), array_values($replaces), $ragione_sociale);

            $ricevuta->nome_debitore = mb_strtoupper($ragione_sociale);
            $ricevuta->identificativo_debitore = $identificativo_debitore;
            $ricevuta->indirizzo_debitore = mb_strtoupper($controparte['indirizzo']);
            $ricevuta->cap_debitore = $controparte['cap'];
            $ricevuta->comune_debitore = mb_strtoupper($controparte['citta']);
            $ricevuta->provincia_debitore = $controparte['provincia'];
            $ricevuta->descrizione_banca = $descrizione_banca;
            $ricevuta->descrizione = mb_strtoupper($descrizione);

            $this->bonifico->addRicevuta($ricevuta);
        } else {
            $ricevuta->importo += $totale;
            $ricevuta->descrizione .= ' - '.mb_strtoupper($descrizione);
        }

        return true;
    }

    public function aggiungiCreditoDiretto(int $identifier, Anagrafica $controparte, Banca $banca_controparte, string $descrizione, $totale, \DateTime $data_prevista, $ctgypurp)
    {
        $id = 'pagamento_'.$identifier;

        // Esportazione del pagamento
        $payment = $this->credito_diretto->addPaymentInfo($id, [
            'id' => $identifier,
            'dueDate' => $data_prevista->format('Y-m-d'),
            'debtorName' => $this->azienda->ragione_sociale,
            'debtorAccountIBAN' => $this->banca_azienda->iban,
            'debtorAgentBIC' => $this->banca_azienda->bic,
        ]);

        $this->credito_diretto->addTransfer($id, [
            'amount' => $totale * 100,
            'creditorIban' => $banca_controparte->iban,
            'creditorBic' => $banca_controparte->bic,
            'creditorName' => $controparte->ragione_sociale,
            'remittanceInformation' => $descrizione,
        ]);

        $payment->setCategoryPurposeCode($ctgypurp);

        return true;
    }

    public function aggiungiDebitoDirettoSEPA(int $identifier, Anagrafica $controparte, Banca $banca_controparte, string $descrizione, $totale, \DateTime $data_prevista)
    {
        $id = 'pagamento_'.$identifier;

        $this->debito_diretto->addPaymentInfo($id, [
            'id' => $identifier,
            'dueDate' => $data_prevista->format('Y-m-d'),
            'creditorName' => $this->azienda->ragione_sociale,
            'creditorAccountIBAN' => $this->banca_azienda->iban,
            'creditorAgentBIC' => $this->banca_azienda->bic,
            'seqType' => PaymentInformation::S_ONEOFF,
            'creditorId' => $this->banca_azienda->creditor_id,
            'localInstrumentCode' => 'CORE', // default. optional.
        ]);

        // Add a Single Transaction to the named payment
        $mandato = $this->getMandato($banca_controparte);
        $this->debito_diretto->addTransfer($id, [
            'amount' => $totale * 100,
            'debtorName' => $controparte->ragione_sociale,
            'debtorIban' => $banca_controparte->iban,
            'debtorBic' => $banca_controparte->bic,
            'debtorMandate' => $mandato['id_mandato'],
            'debtorMandateSignDate' => $mandato['data_firma_mandato'],
            'remittanceInformation' => $descrizione,
        ]);

        return true;
    }

    public function aggiungiDebitoDiretto(int $identifier, Anagrafica $controparte, Banca $banca_controparte, string $descrizione, $totale, \DateTime $data_prevista, $method, $codice_sequenza)
    {
        $paymentInformation = new PaymentInformationCBI();
        $paymentInformation
            ->setCreditorName($this->azienda->ragione_sociale)
            ->setCreditorIBAN($this->banca_azienda->iban)
            ->setCreditorId($this->banca_azienda->creditor_id)
            ->setPaymentInformationIdentification($identifier)
            ->setRequestedExecutionDate($data_prevista->format('Y-m-d'))
            ->setLocalMethod($method)
            ->setServiceLevel('SEPA')
            ->setSeqType($codice_sequenza != '' ? $codice_sequenza : 'RCUR');

        $mandato = $this->getMandato($banca_controparte);
        $payment = new PaymentCBI();
        $payment
            ->setInstrId($identifier)
            ->setAmount($totale)
            ->setEndToEndId($identifier.$this->numero_transazioni_debito_diretto)
            ->setDebtorIBAN($banca_controparte->iban)
            ->setDebtorName(htmlentities($controparte->ragione_sociale))
            ->setMndt($mandato['id_mandato'])
            ->setMndtDate($mandato['data_firma_mandato'])
            ->setRemittanceInformation($descrizione);

        $paymentInformation->addPayments([$payment]);

        $this->debito_diretto->addPaymentInformation($paymentInformation);
        ++$this->numero_transazioni_debito_diretto;

        $this->totale_debito_diretto += $totale;

        return true;
    }

    public static function getBancaControparte(Scadenza $scadenza): ?Banca
    {
        $documento = $scadenza->documento;

        $banca_controparte = $documento->id_banca_controparte ? Banca::find($documento->id_banca_controparte) : null;
        if (empty($banca_controparte)) {
            $banca_controparte = Banca::where('id_anagrafica', $scadenza->idanagrafica)
                ->where('predefined', 1)
                ->first();
        }

        return $banca_controparte;
    }

    public static function getBancaAzienda(Scadenza $scadenza): Banca
    {
        $documento = $scadenza->documento;

        $banca = $documento->id_banca_azienda ? Banca::find($documento->id_banca_azienda) : '';

        if (empty($banca)) {
            $banca = self::getBancaPredefinitaAzienda();
        }

        return $banca;
    }

    public static function getBancaPredefinitaAzienda(): Banca
    {
        if (!isset(self::$banca_predefinita_azienda)) {
            self::$banca_predefinita_azienda = Banca::where('id_anagrafica', self::getAzienda()->id)
                ->where('predefined', 1)
                ->first();
        }

        return self::$banca_predefinita_azienda;
    }

    public static function getAzienda(): Anagrafica
    {
        if (!isset(self::$azienda_predefinita)) {
            self::$azienda_predefinita = Anagrafica::find(setting('Azienda predefinita'));
        }

        return self::$azienda_predefinita;
    }

    public function esporta(string $path): array
    {
        /**
         * Salvataggio dei file nei diversi formati.
         */
        $files = [];

        // File per il pagamento delle vendite RiBa
        try {
            $content = $this->riba->asCBI();

            // Generazione filename
            $filename = $this->id_riba.'.txt';
            $file = $path.'/'.$filename;
            $files[] = base_url().'/'.$file;

            // Salvataggio del file
            file_put_contents(base_dir().'/'.$file, $content);
        } catch (\Exception $e) {
        }

        // File per il pagamento delle vendite Bonifico
        try {
            $content = $this->bonifico->asXML();

            // Generazione filename
            $filename = $this->id_bonifico.'.xml';
            $file = $path.'/'.$filename;
            $files[] = base_url().'/'.$file;

            // Salvataggio del file
            file_put_contents(base_dir().'/'.$file, $content);
        } catch (\Exception $e) {
        }

        // File per il pagamento degli acquisti SEPA
        try {
            $xml = $this->credito_diretto->asXML();

            // Generazione filename
            $filename = $this->id_credito_diretto.'.xml';
            $file = $path.'/'.$filename;
            $files[] = base_url().'/'.$file;

            // Salvataggio del file
            file_put_contents(base_dir().'/'.$file, $xml);
        } catch (\Exception $e) {
        }

        // File per il pagamento delle vendite SEPA CBI
        try {
            $groupHeader = new HeaderCBI();
            $groupHeader->setControlSum($this->totale_debito_diretto)
                ->setInitiatingPartyName($this->azienda->ragione_sociale)
                ->setOrgHeaderId('ABC') // Codice Unico CBI
                ->setOrgHeaderIssr('CBI')
                ->setMessageIdentification($this->id_debito_diretto)
                ->setNumberOfTransactions($this->numero_transazioni_debito_diretto);
            $this->debito_diretto->setGroupHeader($groupHeader);

            if($groupHeader->getNumberOfTransactions() > 0) {
                $xml = $this->debito_diretto->xml();

                // Generazione filename
                $filename = $this->id_debito_diretto.'.xml';
                $file = $path.'/'.$filename;
                $files[] = base_url().'/'.$file;

                // Salvataggio del file
                file_put_contents(base_dir().'/'.$file, $xml);
            }
        } catch (\Exception $e) {
        }

        return $files;
    }

    protected function getMandato(Banca $banca)
    {
        if (database()->tableExists('co_mandati_sepa')) {
            return database()->fetchOne('SELECT * FROM co_mandati_sepa WHERE id_banca = '.prepare($banca->id));
        } else {
            return [];
        }
    }

    protected function getTipo(Scadenza $scadenza)
    {
        return database()->fetchOne('SELECT * FROM `co_tipi_scadenze` LEFT JOIN `co_tipi_scadenze_lang` ON (`co_tipi_scadenze_lang`.`id_record` = `co_tipi_scadenze`.`id` AND `co_tipi_scadenze_lang`.`id_lang` = '.prepare(\Models\Locale::getDefault()->id).') WHERE `title` = '.prepare($scadenza->tipo));
    }

    protected static function cleanString($string)
    {
        // sostituisci tutti i caratteri accentati con la versione non accentata
        $replace = [
            'ъ' => '-', 'Ь' => '-', 'Ъ' => '-', 'ь' => '-',
            'Ă' => 'A', 'Ą' => 'A', 'À' => 'A', 'Ã' => 'A', 'Á' => 'A', 'Æ' => 'A', 'Â' => 'A', 'Å' => 'A', 'Ä' => 'Ae',
            'Þ' => 'B',
            'Ć' => 'C', 'ץ' => 'C', 'Ç' => 'C',
            'È' => 'E', 'Ę' => 'E', 'É' => 'E', 'Ë' => 'E', 'Ê' => 'E',
            'Ğ' => 'G',
            'İ' => 'I', 'Ï' => 'I', 'Î' => 'I', 'Í' => 'I', 'Ì' => 'I',
            'Ł' => 'L',
            'Ñ' => 'N', 'Ń' => 'N',
            'Ø' => 'O', 'Ó' => 'O', 'Ò' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'Oe',
            'Ş' => 'S', 'Ś' => 'S', 'Ș' => 'S', 'Š' => 'S',
            'Ț' => 'T',
            'Ù' => 'U', 'Û' => 'U', 'Ú' => 'U', 'Ü' => 'Ue',
            'Ý' => 'Y',
            'Ź' => 'Z', 'Ž' => 'Z', 'Ż' => 'Z',
            'â' => 'a', 'ǎ' => 'a', 'ą' => 'a', 'á' => 'a', 'ă' => 'a', 'ã' => 'a', 'Ǎ' => 'a', 'а' => 'a', 'А' => 'a', 'å' => 'a', 'à' => 'a', 'א' => 'a', 'Ǻ' => 'a', 'Ā' => 'a', 'ǻ' => 'a', 'ā' => 'a', 'ä' => 'ae', 'æ' => 'ae', 'Ǽ' => 'ae', 'ǽ' => 'ae',
            'б' => 'b', 'ב' => 'b', 'Б' => 'b', 'þ' => 'b',
            'ĉ' => 'c', 'Ĉ' => 'c', 'Ċ' => 'c', 'ć' => 'c', 'ç' => 'c', 'ц' => 'c', 'צ' => 'c', 'ċ' => 'c', 'Ц' => 'c', 'Č' => 'c', 'č' => 'c', 'Ч' => 'ch', 'ч' => 'ch',
            'ד' => 'd', 'ď' => 'd', 'Đ' => 'd', 'Ď' => 'd', 'đ' => 'd', 'д' => 'd', 'Д' => 'D', 'ð' => 'd',
            'є' => 'e', 'ע' => 'e', 'е' => 'e', 'Е' => 'e', 'Ə' => 'e', 'ę' => 'e', 'ĕ' => 'e', 'ē' => 'e', 'Ē' => 'e', 'Ė' => 'e', 'ė' => 'e', 'ě' => 'e', 'Ě' => 'e', 'Є' => 'e', 'Ĕ' => 'e', 'ê' => 'e', 'ə' => 'e', 'è' => 'e', 'ë' => 'e', 'é' => 'e',
            'ф' => 'f', 'ƒ' => 'f', 'Ф' => 'f',
            'ġ' => 'g', 'Ģ' => 'g', 'Ġ' => 'g', 'Ĝ' => 'g', 'Г' => 'g', 'г' => 'g', 'ĝ' => 'g', 'ğ' => 'g', 'ג' => 'g', 'Ґ' => 'g', 'ґ' => 'g', 'ģ' => 'g',
            'ח' => 'h', 'ħ' => 'h', 'Х' => 'h', 'Ħ' => 'h', 'Ĥ' => 'h', 'ĥ' => 'h', 'х' => 'h', 'ה' => 'h',
            'î' => 'i', 'ï' => 'i', 'í' => 'i', 'ì' => 'i', 'į' => 'i', 'ĭ' => 'i', 'ı' => 'i', 'Ĭ' => 'i', 'И' => 'i', 'ĩ' => 'i', 'ǐ' => 'i', 'Ĩ' => 'i', 'Ǐ' => 'i', 'и' => 'i', 'Į' => 'i', 'י' => 'i', 'Ї' => 'i', 'Ī' => 'i', 'І' => 'i', 'ї' => 'i', 'і' => 'i', 'ī' => 'i', 'ĳ' => 'ij', 'Ĳ' => 'ij',
            'й' => 'j', 'Й' => 'j', 'Ĵ' => 'j', 'ĵ' => 'j', 'я' => 'ja', 'Я' => 'ja', 'Э' => 'je', 'э' => 'je', 'ё' => 'jo', 'Ё' => 'jo', 'ю' => 'ju', 'Ю' => 'ju',
            'ĸ' => 'k', 'כ' => 'k', 'Ķ' => 'k', 'К' => 'k', 'к' => 'k', 'ķ' => 'k', 'ך' => 'k',
            'Ŀ' => 'l', 'ŀ' => 'l', 'Л' => 'l', 'ł' => 'l', 'ļ' => 'l', 'ĺ' => 'l', 'Ĺ' => 'l', 'Ļ' => 'l', 'л' => 'l', 'Ľ' => 'l', 'ľ' => 'l', 'ל' => 'l',
            'מ' => 'm', 'М' => 'm', 'ם' => 'm', 'м' => 'm',
            'ñ' => 'n', 'н' => 'n', 'Ņ' => 'n', 'ן' => 'n', 'ŋ' => 'n', 'נ' => 'n', 'Н' => 'n', 'ń' => 'n', 'Ŋ' => 'n', 'ņ' => 'n', 'ŉ' => 'n', 'Ň' => 'n', 'ň' => 'n',
            'о' => 'o', 'О' => 'o', 'ő' => 'o', 'õ' => 'o', 'ô' => 'o', 'Ő' => 'o', 'ŏ' => 'o', 'Ŏ' => 'o', 'Ō' => 'o', 'ō' => 'o', 'ø' => 'o', 'ǿ' => 'o', 'ǒ' => 'o', 'ò' => 'o', 'Ǿ' => 'o', 'Ǒ' => 'o', 'ơ' => 'o', 'ó' => 'o', 'Ơ' => 'o', 'œ' => 'oe', 'Œ' => 'oe', 'ö' => 'oe',
            'פ' => 'p', 'ף' => 'p', 'п' => 'p', 'П' => 'p',
            'ק' => 'q',
            'ŕ' => 'r', 'ř' => 'r', 'Ř' => 'r', 'ŗ' => 'r', 'Ŗ' => 'r', 'ר' => 'r', 'Ŕ' => 'r', 'Р' => 'r', 'р' => 'r',
            'ș' => 's', 'с' => 's', 'Ŝ' => 's', 'š' => 's', 'ś' => 's', 'ס' => 's', 'ş' => 's', 'С' => 's', 'ŝ' => 's', 'Щ' => 'sch', 'щ' => 'sch', 'ш' => 'sh', 'Ш' => 'sh', 'ß' => 'ss',
            'т' => 't', 'ט' => 't', 'ŧ' => 't', 'ת' => 't', 'ť' => 't', 'ţ' => 't', 'Ţ' => 't', 'Т' => 't', 'ț' => 't', 'Ŧ' => 't', 'Ť' => 't', '™' => 'tm',
            'ū' => 'u', 'у' => 'u', 'Ũ' => 'u', 'ũ' => 'u', 'Ư' => 'u', 'ư' => 'u', 'Ū' => 'u', 'Ǔ' => 'u', 'ų' => 'u', 'Ų' => 'u', 'ŭ' => 'u', 'Ŭ' => 'u', 'Ů' => 'u', 'ů' => 'u', 'ű' => 'u', 'Ű' => 'u', 'Ǖ' => 'u', 'ǔ' => 'u', 'Ǜ' => 'u', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'У' => 'u', 'ǚ' => 'u', 'ǜ' => 'u', 'Ǚ' => 'u', 'Ǘ' => 'u', 'ǖ' => 'u', 'ǘ' => 'u', 'ü' => 'ue',
            'в' => 'v', 'ו' => 'v', 'В' => 'v',
            'ש' => 'w', 'ŵ' => 'w', 'Ŵ' => 'w',
            'ы' => 'y', 'ŷ' => 'y', 'ý' => 'y', 'ÿ' => 'y', 'Ÿ' => 'y', 'Ŷ' => 'y',
            'Ы' => 'y', 'ž' => 'z', 'З' => 'z', 'з' => 'z', 'ź' => 'z', 'ז' => 'z', 'ż' => 'z', 'ſ' => 'z', 'Ж' => 'zh', 'ж' => 'zh',
        ];

        $unaccentedString = strtr($string, $replace);

        return $unaccentedString;
    }
}
