<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Plugins\ImportFE;

use API\Services;
use Models\Cache;

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
        $result = self::getFileList($list);

        // Aggiornamento cache hook
        Cache::get('Fatture Elettroniche')->set($result);

        return $result;
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

    public static function getFileList($list = [])
    {
        $names = array_column($list, 'name');

        // Ricerca fisica
        $directory = FatturaElettronica::getImportDirectory();

        $files = glob($directory.'/*.xml*');
        foreach ($files as $id => $file) {
            $name = basename($file);
            $pos = array_search($name, $names);

            if ($pos === false) {
                $list[] = [
                    'id' => $id,
                    'name' => $name,
                    'file' => true,
                ];
            } else {
                $list[$pos]['id'] = $id;
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

            if (!empty($body['content'])) {
                FatturaElettronica::store($name, $body['content']);
            }
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
