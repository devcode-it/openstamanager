<?php

namespace Plugins\ImportFE;

use API\Services;

/**
 * Classe per l'interazione con API esterne.
 *
 * @since 2.4.3
 */
class Interaction extends Services
{
    public static function getInvoiceList()
    {
        $list = self::getRemoteList();

        // Ricerca fisica
        $names = array_column($list, 'name');
        $files = self::getFileList($names);

        $list = array_merge($list, $files);

        // Aggiornamento cache hook
        InvoiceHook::update($list);

        return $list;
    }

    public static function getRemoteList()
    {
        // Ricerca da remoto
        if (self::isEnabled()) {
            $response = static::request('POST', 'fatture_da_importare');
            $body = static::responseBody($response);

            if ($body['status'] == '200') {
                $list = $body['results'];
            }
        }

        return $list ?: [];
    }

    public static function getFileList($names = [])
    {
        $list = [];

        // Ricerca fisica
        $directory = FatturaElettronica::getImportDirectory();

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

    public static function getInvoiceFile($name)
    {
        $directory = FatturaElettronica::getImportDirectory();
        $file = $directory.'/'.$name;

        if (!file_exists($file)) {
            $response = static::request('POST', 'fattura_da_importare', [
                'name' => $name,
            ]);
            $body = static::responseBody($response);

            FatturaElettronica::store($name, $body['content']);
        }

        return $name;
    }

    public static function processInvoice($filename)
    {
        $response = static::request('POST', 'fattura_xml_salvata', [
            'filename' => $filename,
        ]);

        $body = static::responseBody($response);

        $message = '';
        if ($body['status'] != '200') {
            $message = $body['status'].' - '.$body['message'];
        }

        return $message;
    }
}
