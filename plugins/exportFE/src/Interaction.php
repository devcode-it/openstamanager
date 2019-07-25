<?php

namespace Plugins\ExportFE;

use API\Services;

/**
 * Classe per l'interazione con API esterne.
 *
 * @since 2.4.3
 */
class Interaction extends Services
{
    public static function sendInvoice($id_record)
    {
        try {
            $fattura = new FatturaElettronica($id_record);
            $file = DOCROOT.'/'.FatturaElettronica::getDirectory().'/'.$fattura->getFilename();

            $response = static::request('POST', 'invio_fattura_xml', [
                'xml' => file_get_contents($file),
                'filename' => $fattura->getFilename(),
            ]);
            $body = static::responseBody($response);

            return [
                'code' => $body['status'],
                'message' => $body['message'],
            ];
        } catch (UnexpectedValueException $e) {
        }

        return [
            'code' => 400,
            'message' => tr('Fattura non generata correttamente'),
        ];
    }
}
