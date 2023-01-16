<?php

namespace Plugins\PresentazioniBancarie\Cbi;

/**
 * Classe per gestire l'intestazione del RiBa.
 *
 * @property int    $abi
 * @property int    $soggetto_veicolatore
 * @property int    $cab
 * @property string $conto
 * @property string $data_creazione
 * @property string $nome_supporto
 * @property string $codice_divisa
 * @property string $ragione_sociale_creditore
 * @property string $indirizzo_creditore
 * @property string $citta_creditore
 * @property string $partita_iva_o_codice_fiscale_creditore
 * @property string $identificativo_creditore
 * @property string $codice_sia
 * @property bool   $eol
 */
class Intestazione extends Elemento
{
    /**
     * Codice ABI della banca del creditore.
     *
     * @var int Valore numerico di 5 cifre
     */
    protected $abi;
	
	/**
     * Codice ABI del soggetto veicolatore.
     *
     * @var int Valore numerico di 5 cifre
     */
    protected $soggetto_veicolatore;
    /**
     * Codice CAB della banca del creditore.
     *
     * @var int Valore numerico di 5 cifre
     *
     * @property
     */
    protected $cab;
    /**
     * @var string Valore alfanumerico di 12 cifre
     */
    protected $conto;
    /**
     * @var string Valore numerico di 6 cifre in formato ggmmaa
     */
    protected $data_creazione;
    /**
     * @var string Valore alfanumerico di 20 cifre
     */
    protected $nome_supporto;
    /**
     * @var string Valore alfanumerico di 1 cifra, opzionale (default "E")
     */
    protected $codice_divisa = 'E';
    /**
     * @var string Valore alfanumerico di 24 cifre
     */
    protected $ragione_sociale_creditore;
    /**
     * @var string Valore alfanumerico di 24 cifre
     */
    protected $indirizzo_creditore;
    /**
     * @var string Valore alfanumerico di 24 cifre
     */
    protected $citta_creditore;
    /**
     * @var string Valore alfanumerico di 24 cifre
     */
    protected $partita_iva_o_codice_fiscale_creditore;
    /**
     * @var string Valore alfanumerico di 16 cifre, opzionale (default "")
     */
    protected $identificativo_creditore = '';
    /**
     * @var string Valore alfanumerico di 5 cifre
     */
    protected $codice_sia;
    /**
     * @var bool true per aggiungere i caratteri di fine rigo
     */
    protected $eol = true;

    public function toRibaAbiCbiFormat()
    {
        return [
            $this->abi,
            $this->cab,
            $this->conto,
            $this->data_creazione,
            $this->nome_supporto,
            $this->codice_divisa,
            $this->ragione_sociale_creditore,
            $this->indirizzo_creditore,
            $this->citta_creditore,
            $this->partita_iva_o_codice_fiscale_creditore,
            $this->identificativo_creditore,
            $this->codice_sia,
            $this->soggetto_veicolatore,
            $this->eol,
        ];
    }
}
