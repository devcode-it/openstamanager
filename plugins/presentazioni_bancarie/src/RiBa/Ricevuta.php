<?php

namespace Plugins\PresentazioniBancarie\RiBa;

/**
 * Classe per gestire i dati della ricevuta bancaria del RiBa.
 *
 * @property int    $numero_ricevuta
 * @property string $scadenza
 * @property float  $importo
 * @property string $nome_debitore
 * @property string $identificativo_debitore
 * @property string $indirizzo_debitore
 * @property int    $cap_debitore
 * @property string $comune_debitore
 * @property int    $abi_banca
 * @property int    $cab_banca
 * @property string $descrizione_banca
 * @property int    $codice_cliente
 * @property string $descrizione
 * @property string $provincia_debitore
 * @property string $descrizione_origine
 */
class Ricevuta extends Elemento
{
    /**
     * @var int Valore numerico di 10 cifre
     */
    protected $numero_ricevuta;
    /**
     * @var string Valore numerico di 6 cifre
     */
    protected $scadenza;
    /**
     * @var float Valore numerico di 13 cifre, con 2 cifre decimali
     */
    protected $importo;
    /**
     * @var string Valore alfanumerico di 60 cifre
     */
    protected $nome_debitore;
    /**
     * Codice fiscale oppure Partita IVA.
     *
     * @var string Valore alfanumerico di massimo 16 cifre
     */
    protected $identificativo_debitore;
    /**
     * @var string Valore alfanumerico di 30 cifre
     */
    protected $indirizzo_debitore;
    /**
     * @var int Valore numerico di 5 cifre
     */
    protected $cap_debitore;
    /**
     * @var string Valore alfanumerico di 25 cifre
     */
    protected $comune_debitore;
    /**
     * @var int Valore numerico di 5 cifre
     */
    protected $abi_banca;
    /**
     * @var int Valore numerico di 5 cifre
     */
    protected $cab_banca;
    /**
     * @var string Valore alfanumerico di 50 cifre
     */
    protected $descrizione_banca;
    /**
     * Codice cliente attribuito dal creditore.
     *
     * @var int Valore numerico di 16 cifre
     */
    protected $codice_cliente;
    /**
     * @var string Valore alfanumerico di 40 cifre, con i campi (CIG CUP)
     */
    protected $descrizione;
    /**
     * @var string Valore alfanumerico di 2 cifre
     */
    protected $provincia_debitore;
    /**
     * Numero e data riferimento della fattura che ha generato l'effetto.
     *
     * @var string Valore alfanumerico di 40 cifre
     */
    protected $descrizione_origine;

    public function toCbiFormat()
    {
        return [
            $this->numero_ricevuta,
            $this->scadenza,
            $this->importo,
            $this->nome_debitore,
            $this->identificativo_debitore,
            $this->indirizzo_debitore,
            $this->cap_debitore,
            $this->comune_debitore,
            $this->abi_banca,
            $this->cab_banca,
            $this->descrizione_banca,
            $this->codice_cliente,
            $this->descrizione,
            $this->provincia_debitore,
            $this->descrizione_origine,
        ];
    }
}
