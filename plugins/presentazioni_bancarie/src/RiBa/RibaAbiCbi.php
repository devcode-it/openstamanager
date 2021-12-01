<?php

namespace Plugins\PresentazioniBancarie\RiBa;

/**
 * Questa classe genera il file RiBa standard ABI-CBI passando alla funzione "creaFile".
 *
 * @source GAzie - Gestione Azienda <http://gazie.sourceforge.net>
 *
 * @license GPL-2.0
 * @copyright Copyright (C) 2004-2020 - Antonio De Vincentiis Montesilvano (PE) (http://www.devincentiis.it)
 */
class RibaAbiCbi
{
    protected $progressivo = 0;
    protected $assuntrice;
    protected $data;
    protected $valuta;
    protected $supporto;
    protected $totale;
    protected $creditore;
    protected $sia_code;
    protected $cab_ass;

    /**
     * @param array $intestazione      = [
     *                                 [0] => abi_assuntrice variabile lunghezza 5 numerico
     *                                 [1] => cab_assuntrice variabile lunghezza 5 numerico
     *                                 [2] => conto variabile lunghezza 12 alfanumerico
     *                                 [3] => data_creazione variabile lunghezza 6 numerico formato GGMAA
     *                                 [4] => nome_supporto variabile lunghezza 20 alfanumerico
     *                                 [5] => codice_divisa variabile lunghezza 1 alfanumerico opzionale default "E"
     *                                 [6] => ragione_soc1_creditore variabile lunghezza 24 alfanumerico
     *                                 [7] => ragione_soc2_creditore variabile lunghezza 24 alfanumerico
     *                                 [8] => indirizzo_creditore variabile lunghezza 24 alfanumerico
     *                                 [9] => cap_citta_prov_creditore variabile lunghezza 24 alfanumerico
     *                                 [10] => codice_fiscale_creditore variabile lunghezza 16 alfanumerico opzionale default ""
     *                                 [11] => codice SIA 5 caratteri alfanumerici
     *                                 [12] => carry  booleano true per aggiungere i caratteri di fine rigo chr(13) e chr(10)
     *                                 ]
     * @param array $ricevute_bancarie = [
     *                                 [0] => numero ricevuta lunghezza 10 numerico
     *                                 [1] => scadenza lunghezza 6 numerico
     *                                 [2] => importo in centesimi di euro lunghezza 13 numerico
     *                                 [3] => nome debitore lunghezza 60 alfanumerico
     *                                 [4] => codice fiscale/partita iva debitore lunghezza 16 alfanumerico
     *                                 [5] => indirizzo debitore lunghezza 30 alfanumerico
     *                                 [6] => cap debitore lunghezza 5 numerico
     *                                 [7] => comune debitore lunghezza 25 alfanumerico
     *                                 [8] => abi banca domiciliataria lunghezza 5 numerico
     *                                 [9] => cab banca domiciliataria lunghezza 5 numerico
     *                                 [10] => descrizione banca domiciliataria lunghezza 50 alfanumerico
     *                                 [11] => codice cliente attribuito dal creditore lunghezza 16 numerico
     *                                 [12] => descrizione del debito lunghezza 40 alfanumerico (CIG CUP)
     *                                 [13] => provincia debitore lunghezza 2 alfanumerico
     *                                 [14] => descrizione del debito lunghezza 40 alfanumerico (Numero e data riferimento della fattura che ha generato l'effetto)
     *                                 ]
     *
     * @return string
     */
    public function creaFile($intestazione, $ricevute_bancarie)
    {
        $eol = '';
        if (isset($intestazione[12])) {
            $eol = chr(13).chr(10);
        }

        $contenuto = $this->RecordIB($intestazione[0], $intestazione[3], $intestazione[4], $intestazione[5], $intestazione[11], $intestazione[1]).$eol;
        foreach ($ricevute_bancarie as $ricevuta) { //estraggo le ricevute dall'array
            ++$this->progressivo;
            $contenuto .= $this->Record14($ricevuta[1], $ricevuta[2], $intestazione[0], $intestazione[1], $intestazione[2], $ricevuta[8], $ricevuta[9], $ricevuta[11]).$eol;
            $contenuto .= $this->Record20($intestazione[6], $intestazione[7], $intestazione[8], $intestazione[9]).$eol;
            $contenuto .= $this->Record30($ricevuta[3], $ricevuta[4]).$eol;
            $contenuto .= $this->Record40($ricevuta[5], $ricevuta[6], $ricevuta[7], $ricevuta[10], $ricevuta[13]).$eol;
            $contenuto .= $this->Record50($ricevuta[12].' '.$ricevuta[14], $intestazione[10]).$eol;
            $contenuto .= $this->Record51($ricevuta[0]).$eol;
            $contenuto .= $this->Record70().$eol;
        }
        $contenuto .= $this->RecordEF().$eol;

        return $contenuto;
    }

    /**
     * @param string $string
     * @param int    $length
     *
     * @return string
     */
    protected function padString($string, $length)
    {
        // Sostituzione di alcuni simboli noti
        $replaces = [
            '&#039;' => "'",
            '&quot;' => "'",
            '&amp;' => '&',
        ];
        $string = str_replace(array_keys($replaces), array_values($replaces), $string);

        return substr(str_pad($string, $length), 0, $length);
    }

    /**
     * @param string $string
     * @param int    $length
     *
     * @return string
     */
    protected function padNumber($string, $length)
    {
        return str_pad($string, $length, '0', STR_PAD_LEFT);
    }

    /**
     * Record di testa.
     *
     * @param $abi_assuntrice
     * @param $data_creazione
     * @param $nome_supporto
     * @param $codice_divisa
     * @param $sia_code
     * @param $cab_assuntrice
     *
     * @return string
     */
    protected function RecordIB($abi_assuntrice, $data_creazione, $nome_supporto, $codice_divisa, $sia_code, $cab_assuntrice)
    {
        $this->assuntrice = $this->padNumber($abi_assuntrice, 5);
        $this->cab_ass = $this->padNumber($cab_assuntrice, 5);
        $this->data = str_pad($data_creazione, 6, '0');
        $this->valuta = substr($codice_divisa, 0, 1);
        $this->supporto = str_pad($nome_supporto, 20, '*', STR_PAD_LEFT);
        $this->sia_code = $this->padNumber($sia_code, 5);

        return ' IB'.$this->sia_code.$this->assuntrice.$this->data.$this->supporto.str_repeat(' ', 65).'1$'.$this->assuntrice.str_repeat(' ', 2).$this->valuta.str_repeat(' ', 6);
    }

    /**
     * @param string $scadenza
     * @param float  $importo
     * @param string $abi_assuntrice
     * @param string $cab_assuntrice
     * @param string $conto
     * @param string $abi_domiciliataria
     * @param string $cab_domiciliataria
     * @param string $codice_cliente
     *
     * @return string
     */
    protected function Record14($scadenza, $importo, $abi_assuntrice, $cab_assuntrice, $conto, $abi_domiciliataria, $cab_domiciliataria, $codice_cliente)
    {
        $this->totale += $importo;

        return ' 14'.$this->padNumber($this->progressivo, 7)
            .str_repeat(' ', 12).$scadenza.'30000'.$this->padNumber($importo, 13).'-'.$this->padNumber($abi_assuntrice, 5).$this->padNumber($cab_assuntrice, 5).str_pad($conto, 12)
            .$this->padNumber($abi_domiciliataria, 5)
            .$this->padNumber($cab_domiciliataria, 5)
            .str_repeat(' ', 12).$this->sia_code.'4'.str_pad($codice_cliente, 16)
            .str_repeat(' ', 6).$this->valuta;
    }

    /**
     * @param string $ragione_soc1_creditore
     * @param string $ragione_soc2_creditore
     * @param string $indirizzo_creditore
     * @param string $cap_citta_prov_creditore
     *
     * @return string
     */
    protected function Record20($ragione_soc1_creditore, $ragione_soc2_creditore, $indirizzo_creditore, $cap_citta_prov_creditore)
    {
        $this->creditore = str_pad($ragione_soc1_creditore, 24);

        return ' 20'.$this->padNumber($this->progressivo, 7)
            .substr($this->creditore, 0, 24)
            .$this->padString($ragione_soc2_creditore, 24)
            .$this->padString($indirizzo_creditore, 24)
            .$this->padString($cap_citta_prov_creditore, 24)
            .str_repeat(' ', 14);
    }

    /**
     * @param string $nome_debitore
     * @param string $codice_fiscale_debitore
     *
     * @return string
     */
    protected function Record30($nome_debitore, $codice_fiscale_debitore)
    {
        return ' 30'.$this->padNumber($this->progressivo, 7)
            .$this->padString($nome_debitore, 60)
            .str_pad($codice_fiscale_debitore, 16, ' ')
            .str_repeat(' ', 34);
    }

    /**
     * @param string $indirizzo_debitore
     * @param string $cap_debitore
     * @param string $comune_debitore
     * @param string $descrizione_domiciliataria
     * @param string $provincia_debitore
     *
     * @return string
     */
    protected function Record40($indirizzo_debitore, $cap_debitore, $comune_debitore, $descrizione_domiciliataria = '', $provincia_debitore = '')
    {
        return ' 40'.$this->padNumber($this->progressivo, 7)
            .$this->padString($indirizzo_debitore, 30)
            .$this->padNumber(intval($cap_debitore), 5)
            .$this->padString($comune_debitore, 22).' '.$this->padString($provincia_debitore, 2)
            .$this->padString($descrizione_domiciliataria, 50);
    }

    /**
     * @param string $descrizione_debito
     * @param string $codice_fiscale_creditore
     *
     * @return string
     */
    protected function Record50($descrizione_debito, $codice_fiscale_creditore)
    {
        return ' 50'.$this->padNumber($this->progressivo, 7)
            .$this->padString($descrizione_debito, 80)
            .str_repeat(' ', 10)
            .str_pad($codice_fiscale_creditore, 16, ' ')
            .str_repeat(' ', 4);
    }

    /**
     * @param string $numero_ricevuta_creditore
     *
     * @return string
     */
    protected function Record51($numero_ricevuta_creditore)
    {
        return ' 51'.$this->padNumber($this->progressivo, 7)
            .$this->padNumber($numero_ricevuta_creditore, 10)
            .substr($this->creditore, 0, 20)
            .str_repeat(' ', 80);
    }

    /**
     * @return string
     */
    protected function Record70()
    {
        return ' 70'.$this->padNumber($this->progressivo, 7)
            .str_repeat(' ', 110);
    }

    /**
     * Record di coda.
     *
     * @return string
     */
    protected function RecordEF()
    {
        return ' EF'.$this->sia_code.$this->assuntrice.$this->data.$this->supporto.str_repeat(' ', 6)
            .$this->padNumber($this->progressivo, 7)
            .$this->padNumber($this->totale, 15)
            .str_repeat('0', 15)
            .$this->padNumber($this->progressivo * 7 + 2, 7)
            .str_repeat(' ', 24).$this->valuta.str_repeat(' ', 6);
    }
}
