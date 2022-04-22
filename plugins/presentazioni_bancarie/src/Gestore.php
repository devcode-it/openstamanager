<?php

namespace Plugins\PresentazioniBancarie;

use Carbon\Carbon;
use DateTime;
use Plugins\PresentazioniBancarie\Cbi\Intestazione;
use Plugins\PresentazioniBancarie\Cbi\RiBa;
use Plugins\PresentazioniBancarie\Cbi\Ricevuta;
use Digitick\Sepa\PaymentInformation;
use Digitick\Sepa\TransferFile\Factory\TransferFileFacadeFactory;
use Exception;
use Modules\Anagrafiche\Anagrafica;
use Modules\Banche\Banca;
use Modules\Scadenzario\Scadenza;
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
    protected $id_debito_diretto;
    protected $id_credito_diretto;

    protected $riba;
    protected $debito_diretto;
    protected $credito_diretto;

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
        $conto = substr($iban, 15, 12);
        $abi_assuntrice = substr($iban, 5, 5);
        $cab_assuntrice = substr($iban, 10, 5);

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
        $intestazione->cap_citta_prov_creditore = strtoupper($this->azienda['cap'].' '.$this->azienda['citta'].' '.$this->azienda['provincia']);
        $intestazione->ragione_soc1_creditore = strtoupper($this->azienda->ragione_sociale);
        $intestazione->indirizzo_creditore = strtoupper($this->azienda['indirizzo']);
        $intestazione->identificativo_creditore = !empty($this->azienda->partita_iva) ? $this->azienda->partita_iva : $this->azienda->codice_fiscale;

        $this->riba = new RiBa($intestazione);
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

    public function aggiungi(Scadenza $scadenza, int $identifier, string $descrizione, string $codice_sequenza = null)
    {
        $documento = $scadenza->documento;
        $controparte = $documento->anagrafica;
        $banca_controparte = self::getBancaControparte($scadenza);
        if (empty($banca_controparte)) {
            return false;
        }

        $pagamento = $documento->pagamento;
        $direzione = $documento->direzione;
        $totale = (abs($scadenza->da_pagare) - abs($scadenza->pagato));

        $is_credito_diretto = $direzione == 'uscita' && in_array($pagamento->codice_modalita_pagamento_fe, ['MP09', 'MP10', 'MP11', 'MP19', 'MP20', 'MP21']);
        $is_debito_diretto = $direzione == 'entrata' && in_array($pagamento->codice_modalita_pagamento_fe, ['MP09', 'MP10', 'MP11', 'MP19', 'MP20', 'MP21']) && !empty($this->banca_azienda->creditor_id); // Mandato SEPA disponibile
        $is_riba = $direzione == 'entrata' && in_array($pagamento->codice_modalita_pagamento_fe, ['MP12']) && !empty($this->banca_azienda->codice_sia);

        if(in_array($pagamento->codice_modalita_pagamento_fe, ['MP19', 'MP21'])){
            $method = 'B2B';
        }else if(in_array($pagamento->codice_modalita_pagamento_fe, ['MP20'])){
            $method = 'CORE';
        }

        if ($is_credito_diretto) {
            return $this->aggiungiCreditoDiretto($identifier, $controparte, $banca_controparte, $descrizione, $totale, $scadenza->scadenza);
        } elseif ($is_debito_diretto) {
            return $this->aggiungiDebitoDiretto($identifier, $controparte, $banca_controparte, $descrizione, $totale, $scadenza->scadenza, $method, $codice_sequenza);
        } elseif ($is_riba) {
            $totale = $totale*100;
            return $this->aggiungiRiBa($identifier, $controparte, $banca_controparte, $descrizione, $totale, $scadenza->scadenza);
        }

        return false;
    }

    public function aggiungiRiBa(int $identifier, Anagrafica $controparte, Banca $banca_controparte, string $descrizione, int $totale, DateTime $data_prevista)
    {
        $data_scadenza = $data_prevista->format('dmy');

        // Dati banca cliente
        $abi_cliente = substr($banca_controparte['iban'], 5, 5);
        $cab_cliente = substr($banca_controparte['iban'], 10, 5);

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
        
        //controlli sulla ragione sociale
        $ragione_sociale = utf8_decode($controparte['ragione_sociale']);

        // Sostituzione di alcuni simboli noti
        $replaces = [
            '&#039;' => "'",
            '&quot;' => "'",
            '&amp;' => '&',
        ];
        $ragione_sociale = str_replace(array_keys($replaces), array_values($replaces), $ragione_sociale);

        $ricevuta->nome_debitore = strtoupper($ragione_sociale);
        $ricevuta->identificativo_debitore = !empty($controparte->partita_iva) ? $controparte->partita_iva : $controparte->codice_fiscale;
        $ricevuta->indirizzo_debitore = strtoupper($controparte['indirizzo']);
        $ricevuta->cap_debitore = $controparte['cap'];
        $ricevuta->comune_debitore = strtoupper($controparte['citta']);
        $ricevuta->provincia_debitore = $controparte['provincia'];
        $ricevuta->descrizione_banca = $descrizione_banca;
        $ricevuta->descrizione = strtoupper($descrizione);

        $this->riba->addRicevuta($ricevuta);

        return true;
    }

    public function aggiungiCreditoDiretto(int $identifier, Anagrafica $controparte, Banca $banca_controparte, string $descrizione, $totale, DateTime $data_prevista)
    {
        $id = 'pagamento_'.$identifier;

        // Esportazione del pagamento
        $this->credito_diretto->addPaymentInfo($id, [
            'id' => $identifier,
            'dueDate' => $data_prevista->format('dmy'),
            'debtorName' => $this->azienda->ragione_sociale,
            'debtorAccountIBAN' => $this->banca_azienda->iban,
            'debtorAgentBIC' => $this->banca_azienda->bic,
        ]);

        $this->credito_diretto->addTransfer($id, [
            'amount' => $totale,
            'creditorIban' => $banca_controparte->iban,
            'creditorBic' => $banca_controparte->bic,
            'creditorName' => $controparte->ragione_sociale,
            'remittanceInformation' => $descrizione,
        ]);

        return true;
    }

    public function aggiungiDebitoDirettoSEPA(int $identifier, Anagrafica $controparte, Banca $banca_controparte, string $descrizione, $totale, DateTime $data_prevista)
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
            'amount' => $totale,
            'debtorName' => $controparte->ragione_sociale,
            'debtorIban' => $banca_controparte->iban,
            'debtorBic' => $banca_controparte->bic,
            'debtorMandate' => $mandato['id_mandato'],
            'debtorMandateSignDate' => $mandato['data_firma_mandato'],
            'remittanceInformation' => $descrizione,
        ]);

        return true;
    }

    public function aggiungiDebitoDiretto(int $identifier, Anagrafica $controparte, Banca $banca_controparte, string $descrizione, $totale, DateTime $data_prevista, $method, $codice_sequenza)
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
            ->setSeqType(($codice_sequenza!=''?$codice_sequenza:'RCUR'));

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
        $anagrafica = $documento->anagrafica;

        $banca_controparte = $documento->id_banca_controparte ? Banca::find($documento->id_banca_controparte) : null;
        if (empty($banca_controparte)) {
            $banca_controparte = Banca::where('id_anagrafica', $anagrafica->id)
                ->where('predefined', 1)
                ->first();
        }

        return $banca_controparte;
    }

    public static function getBancaAzienda(Scadenza $scadenza): Banca
    {
        $documento = $scadenza->documento;

        return $documento->id_banca_azienda ? Banca::find($documento->id_banca_azienda) : self::getBancaPredefinitaAzienda();
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
        } catch (Exception $e) {
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
        } catch (Exception $e) {
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

            $xml = $this->debito_diretto->xml();

            // Generazione filename
            $filename = $this->id_debito_diretto.'.xml';
            $file = $path.'/'.$filename;
            $files[] = base_url().'/'.$file;

            // Salvataggio del file
            file_put_contents(base_dir().'/'.$file, $xml);
        } catch (Exception $e) {
        }

        return $files;
    }

    protected function getMandato(Banca $banca)
    {
        if (database()->tableExists('co_mandati_sepa')) {
            return database()->fetchOne('SELECT * FROM co_mandati_sepa WHERE id_banca = '.prepare($banca->id));
        } else{
            return [];
        }
    }
}
