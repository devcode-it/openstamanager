<?php

namespace Plugins\ImportFE;

use Plugins\ExportFE\Connection;

/**
 * Classe per l'interazione con API esterne.
 *
 * @since 2.4.3
 */
class Interaction extends Connection
{
    public static function listToImport()
    {
        $directory = FatturaElettronica::getImportDirectory();

        $response = static::request('POST', 'get_fatture_da_importare');
        $body = static::responseBody($response);

        $code = $body['code'];

        if ($code == '200') {
            $files = $body['results'];

            foreach ($files as $file) {
                /*
                  * Verifico che l'XML non sia già stato importato nel db
                  */

                if (preg_match("/^([A-Z]{2})(.+?)_([^\.]+)\.xml/i", $file, $m)) {
                    $partita_iva = $m[2];
                    $progressivo_invio = $m[3];
                    $fattura = database()->fetchOne('SELECT co_documenti.id FROM (co_documenti INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id) INNER JOIN an_anagrafiche ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica WHERE co_tipidocumento.dir="uscita" AND an_anagrafiche.piva='.prepare($partita_iva).' AND co_documenti.progressivo_invio='.prepare($progressivo_invio));

                    if (!$fattura) {
                        $list[] = basename($file);
                    }
                }
            }

            return array_clean($list);
        }
    }

    public static function getImportXML($name)
    {
        $directory = FatturaElettronica::getImportDirectory();
        $file = $directory.'/'.$name;

        if (!file_exists($file)) {
            $response = static::request('POST', 'get_fattura_da_importare', [
                'name' => $name,
            ]);
            $body = static::responseBody($response);

            FatturaElettronica::store($name, $body['content']);
        }

        return $name;
    }

    public static function processXML($filename)
    {
        $response = static::request('POST', 'process_xml', [
                'filename' => $filename,
            ]);

        $body = static::responseBody($response);

        if ($body['processed'] == '0') {
            $message = $body['code'].' - '.$body['message'];
        } else {
            $message = '';
        }

        return $message;
    }
}
