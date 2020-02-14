<?php

namespace Plugins\ReceiptFE;

use API\Services;

/**
 * Classe per l'interazione con API esterne.
 *
 * @since 2.4.3
 */
class Interaction extends Services
{
    public static function getReceiptList()
    {
        $list = self::getRemoteList();

        // Ricerca fisica
        $names = array_column($list, 'name');
        $files = self::getFileList($names);

        $list = array_merge($list, $files);

        // Aggiornamento cache hook
        ReceiptHook::update($list);

        return $list;
    }

    public static function getRemoteList()
    {
        $list = [];

        // Ricerca da remoto
        if (self::isEnabled()) {
            $response = static::request('POST', 'notifiche_da_importare');
            $body = static::responseBody($response);

            if ($body['status'] == '200') {
                $results = $body['results'];

                foreach ($results as $result) {
                    $list[] = [
                        'name' => $result,
                    ];
                }
            }
        }

        return $list ?: [];
    }

    public static function getFileList($names = [])
    {
        $list = [];

        // Ricerca fisica
        $directory = Ricevuta::getImportDirectory();

        $files = glob($directory.'/*.xml*');
        foreach ($files as $id => $file) {
            $name = basename($file);

            if (!in_array($name, $names)) {
                $list[] = [
                    'id' => $id,
                    'name' => $name,
                    'file' => true,
                ];
            }
        }

        return $list;
    }

    public static function getReceipt($name)
    {
        $directory = Ricevuta::getImportDirectory();
        $file = $directory.'/'.$name;

        if (!file_exists($file)) {
            $response = static::request('POST', 'notifica_da_importare', [
                'name' => $name,
            ]);
            $body = static::responseBody($response);

            if (!empty($body['content'])) {
                Ricevuta::store($name, $body['content']);
            }
        }

        return $name;
    }

    public static function processReceipt($filename)
    {
        $response = static::request('POST', 'notifica_xml_salvata', [
            'filename' => $filename,
        ]);
        $body = static::responseBody($response);

        $result = true;
        if ($body['status'] != '200') {
            $result = $body['status'].' - '.$body['message'];
        }

        return $result;
    }
}
