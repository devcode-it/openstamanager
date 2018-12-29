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

    public function __construct($content)
    {
        $this->xml = XML::read($content);

        $nome = $this->xml['NomeFile'];
        $pieces = explode('_', $nome);

        $progressivo_invio = explode('.', $pieces[1])[0];

        $this->fattura = Fattura::where([
            'progressivo_invio' => $progressivo_invio,
        ])->first();

        if (empty($this->fattura)) {
            throw new UnexpectedValueException();
        }
    }

    public function getFattura()
    {
        return $this->fattura;
    }
}
