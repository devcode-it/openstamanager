<?php

namespace Plugins\ExportFE;

/**
 * Classe per l'interazione con API esterne.
 *
 * @since 2.4.3
 */
class Interaction extends Connection
{
    public static function sendXML($id_record)
    {
        try {
            $fattura = new FatturaElettronica($id_record);
            $file = DOCROOT.'/'.FatturaElettronica::getDirectory().'/'.$fattura->getFilename();

            $response = static::request('POST', 'send_xml', [], [
                'multipart' => [
                    [
                        'name' => 'xml',
                        'filename' => $fattura->getFilename(),
                        'contents' => file_get_contents($file),
                    ],
                ],
            ]);

            $body = static::responseBody($response);

            return $body;
        } catch (UnexpectedValueException $e) {
        }

        return false;
    }
}
