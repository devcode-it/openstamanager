<?php

namespace Plugins\ReceiptFE;

use Modules\Fatture\Fattura;
use UnexpectedValueException;
use Util\XML;

/**
 * Classe per la gestione della fatturazione elettronica in XML.
 *
 * @since 2.4.2
 */
class Ricevuta
{
    /** @var array XML della fattura */
    protected $xml = null;

    /** @var array XML della fattura */
    protected $fattura = null;

    public function __construct($name, $content)
    {
        $this->xml = XML::read($content);

        $nome = $this->xml['NomeFile'];
        $filename = explode('.', $nome)[0];
        $pieces = explode('_', $filename);

        $progressivo_invio = $pieces[1];

        $this->fattura = Fattura::where([
            'progressivo_invio' => $progressivo_invio,
        ])->first();

        if (empty($this->fattura)) {
            throw new UnexpectedValueException();
        } else {
            // Processo la ricevuta e salvo il codice e messaggio di errore
            $filename = explode('.', $name)[0];
            $pieces = explode('_', $filename);
            $codice = $pieces[2];
            $descrizione = $this->xml['Destinatario']['Descrizione'];
            $data = $this->xml['DataOraRicezione'];

            $this->fattura->codice_stato_fe = $codice;
            $this->fattura->descrizione_stato_fe = $descrizione;
            $this->fattura->data_stato_fe = date('Y-m-d H:i:s', strtotime($data));
            $this->fattura->save();
            
            return true;
        }
    }

    public function getFattura()
    {
        return $this->fattura;
    }
}
