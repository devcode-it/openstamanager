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
        $response = static::request('POST', 'get_receipt_list');
        $body = static::responseBody($response)['results'];

        return $body;
    }

    public static function getReceipt($name)
    {
        $response = static::request('POST', 'get_receipt', [
            'name' => $name,
        ]);
        $body = static::responseBody($response);

        return $body;
    }
}
