<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
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

namespace Plugins\ExportFE;

use API\Services;
use UnexpectedValueException;

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
            $file = base_dir().'/'.FatturaElettronica::getDirectory().'/'.$fattura->getFilename();

            $response = static::request('POST', 'invio_fattura_xml', [
                'xml' => file_get_contents($file),
                'filename' => $fattura->getFilename(),
            ]);
            $body = static::responseBody($response);

            // Aggiornamento dello stato
            if ($body['status'] == 200 || $body['status'] == 301) {
                database()->update('co_documenti', [
                    'codice_stato_fe' => 'WAIT',
                    'data_stato_fe' => date('Y-m-d H:i:s'),
                ], ['id' => $id_record]);
            } elseif ($body['status'] == 405) {
                database()->update('co_documenti', [
                    'codice_stato_fe' => 'ERR',
                    'data_stato_fe' => date('Y-m-d H:i:s'),
                ], ['id' => $id_record]);
            }

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

    public static function getInvoiceRecepits($id_record)
    {
        try {
            $fattura = new FatturaElettronica($id_record);
            $filename = $fattura->getFilename();

            $response = static::request('POST', 'notifiche_fattura', [
                'name' => $filename,
            ]);
            $body = static::responseBody($response);

            return [
                'code' => $body['status'],
                'results' => $body['results'],
            ];
        } catch (UnexpectedValueException $e) {
        }

        return [
            'code' => 400,
            'message' => tr('Fattura non generata correttamente'),
        ];
    }
}
