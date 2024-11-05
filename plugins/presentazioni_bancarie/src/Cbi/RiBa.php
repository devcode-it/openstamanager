<?php

namespace Plugins\PresentazioniBancarie\Cbi;

use Plugins\PresentazioniBancarie\Cbi\Records\Record14;
use Plugins\PresentazioniBancarie\Cbi\Records\Record20;
use Plugins\PresentazioniBancarie\Cbi\Records\Record30;
use Plugins\PresentazioniBancarie\Cbi\Records\Record40;
use Plugins\PresentazioniBancarie\Cbi\Records\Record50;
use Plugins\PresentazioniBancarie\Cbi\Records\Record51;
use Plugins\PresentazioniBancarie\Cbi\Records\Record70;
use Plugins\PresentazioniBancarie\Cbi\Records\RecordEF;
use Plugins\PresentazioniBancarie\Cbi\Records\RecordIB;

class RiBa
{
    /**
     * @var Intestazione
     */
    protected $intestazione;

    /**
     * @var Ricevuta[]
     */
    protected $ricevute = [];

    public function __construct(Intestazione $intestazione)
    {
        $this->intestazione = $intestazione;
    }

    public function addRicevuta(Ricevuta $ricevuta)
    {
        $this->ricevute[] = $ricevuta;
    }

    /**
     * @return Intestazione
     */
    public function getIntestazione()
    {
        return $this->intestazione;
    }

    /**
     * @return RiBa
     */
    public function setIntestazione(Intestazione $intestazione)
    {
        $this->intestazione = $intestazione;

        return $this;
    }

    /**
     * @return Ricevuta[]
     */
    public function getRicevute()
    {
        return $this->ricevute;
    }

    /**
     * @param Ricevuta[] $ricevute
     *
     * @return RiBa
     */
    public function setRicevute($ricevute)
    {
        $this->ricevute = $ricevute;

        return $this;
    }

    public function asCBI()
    {
        $eol = "\r\n";

        $intestazione = $this->intestazione;
        $ricevute = $this->ricevute;
        $contenuto = '';

        // Verifica sulla presenza di ricevute
        if (empty($ricevute)) {
            throw new \Exception();
        }

        // Record IB
        $ib = new RecordIB();
        $ib->codice_sia_mittente = $intestazione->codice_sia;
        $ib->abi_assuntrice = $intestazione->abi;
        $ib->nome_supporto = $intestazione->nome_supporto;
        $ib->data_creazione = $intestazione->data_creazione;

        if ($intestazione->soggetto_veicolatore != '') {
            $ib->tipo_flusso = 1;
            $ib->qualificatore_flusso = '$';
            $ib->soggetto_veicolatore = $intestazione->soggetto_veicolatore;
        }
    
        $contenuto .= $ib->toCBI().$eol;

        // Iterazione tra le ricevute interne al RiBa
        $progressivo = 0;
        $totale = 0;
        foreach ($ricevute as $ricevuta) {
            ++$progressivo;
            $totale += $ricevuta->importo;

            // Record 14
            $r14 = new Record14();
            $r14->numero_progressivo = $progressivo;
            $r14->data_pagamento = $ricevuta->scadenza;

            $r14->abi_assuntrice = $intestazione->abi;
            $r14->cab_assuntrice = $intestazione->cab;
            $r14->conto_assuntrice = $intestazione->conto;
            $r14->codice_azienda_creditrice = $intestazione->codice_sia;

            $r14->abi_domiciliataria = $ricevuta->abi_banca;
            $r14->cab_domiciliataria = $ricevuta->cab_banca;
            $r14->importo = $ricevuta->importo;
            $r14->codice_cliente_debitore = $ricevuta->codice_cliente;
            $contenuto .= $r14->toCBI().$eol;

            // Record 20
            $r20 = new Record20();
            $r20->numero_progressivo = $progressivo;
            $r20->descrizione_creditore_1 = $intestazione->ragione_sociale_creditore;
            $r20->descrizione_creditore_2 = $intestazione->indirizzo_creditore;
            $r20->descrizione_creditore_3 = $intestazione->citta_creditore;
            $r20->descrizione_creditore_4 = $intestazione->partita_iva_o_codice_fiscale_creditore;
            $contenuto .= $r20->toCBI().$eol;

            // Record 30
            $r30 = new Record30();
            $r30->numero_progressivo = $progressivo;
            $r30->descrizione_debitore_1 = $ricevuta->nome_debitore;
            // $r30->descrizione_debitore_2 = $ricevuta->indirizzo_debitore;
            $r30->codice_fiscale_debitore = $ricevuta->identificativo_debitore;
            $contenuto .= $r30->toCBI().$eol;

            // Record 40
            $r40 = new Record40();
            $r40->numero_progressivo = $progressivo;
            $r40->indirizzo_debitore = $ricevuta->indirizzo_debitore;
            $r40->cap_debitore = $ricevuta->cap_debitore;
            $r40->comune_debitore = $ricevuta->comune_debitore;
            $r40->provincia_debitore = $ricevuta->provincia_debitore;
            $r40->banca_domiciliataria = $ricevuta->descrizione_banca;
            $contenuto .= $r40->toCBI().$eol;

            // Record 50
            $r50 = new Record50();
            $r50->numero_progressivo = $progressivo;
            $r50->partita_iva_o_codice_fiscale_creditore = $intestazione->partita_iva_o_codice_fiscale_creditore;
            $r50->riferimento_debito_1 = $ricevuta->descrizione;
            $r50->riferimento_debito_2 = $ricevuta->descrizione_origine ?: '';
            $contenuto .= $r50->toCBI().$eol;

            // Record 51
            $r51 = new Record51();
            $r51->numero_progressivo = $progressivo;
            $r51->numero_ricevuta = $ricevuta->numero_ricevuta;
            $r51->denominazione_creditore = $intestazione->ragione_sociale_creditore;
            $contenuto .= $r51->toCBI().$eol;

            // Record 70
            $r70 = new Record70();
            $r70->numero_progressivo = $progressivo;
            $contenuto .= $r70->toCBI().$eol;
        }

        // Record EF
        $ef = new RecordEF();
        $ef->codice_sia_mittente = $intestazione->codice_sia;
        $ef->abi_assuntrice = $intestazione->abi;
        $ef->nome_supporto = $intestazione->nome_supporto;
        $ef->data_creazione = $intestazione->data_creazione;

        $ef->numero_disposizioni = $progressivo;
        $ef->totale_importi_negativi = $totale;
        $ef->numero_record = $progressivo * 7 + 2;

        $contenuto .= $ef->toCBI().$eol;

        return $contenuto;
    }

    public function asRibaAbiCbi()
    {
        $formato_intestazione = $this->intestazione->toRibaAbiCbiFormat();

        // Trasformazione delle ricevute nel formato relativo
        $formato_ricevute = [];
        foreach ($this->ricevute as $ricevuta) {
            $formato_ricevute[] = $ricevuta->toRibaAbiCbiFormat();
        }

        // Eccezione in caso di assenza di ricevute interne
        if (empty($formato_ricevute)) {
            throw new \InvalidArgumentException();
        }

        $cbi = new RibaAbiCbi();

        return $cbi->creaFile($formato_intestazione, $formato_ricevute);
    }
}
