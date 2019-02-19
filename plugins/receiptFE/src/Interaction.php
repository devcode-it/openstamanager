<?php

namespace Plugins\ReceiptFE;

use Plugins\ExportFE\Connection;

/**
 * Classe per l'interazione con API esterne.
 *
 * @since 2.4.3
 */
class Interaction extends Connection
{
    public static function getReceiptList()
    {
        $response = static::request('POST', 'notifiche_da_importare');
        $body = static::responseBody($response)['results'];

        return $body;
    }

    public static function getReceipt($name)
    {
        $response = static::request('POST', 'notifica_da_importare', [
            'name' => $name,
        ]);
        $body = static::responseBody($response);

        return $body;
    }
}
