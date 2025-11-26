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
use Modules\Fatture\Fattura;

/**
 * Classe per la gestione delle API esterne per l'invio delle Fatture Elettroniche e la ricerca di ricevute collegate.
 *
 * @since 2.4.3
 */
class Interaction extends Services
{
    public static function isEnabled()
    {
        return parent::isEnabled() && self::verificaRisorsaAttiva('Fatturazione Elettronica');
    }

    public static function sendInvoice($id_record)
    {
        try {
            $fattura_elettronica = new FatturaElettronica($id_record);
            $fattura = Fattura::find($id_record);

            // Verifica che la fattura esista
            if (!$fattura) {
                return [
                    'code' => 404,
                    'message' => tr('Fattura non trovata'),
                ];
            }

            $file = $fattura->getFatturaElettronica();

            // Verifica che il file della fattura elettronica esista
            if (!$file) {
                return [
                    'code' => 400,
                    'message' => tr('File della fattura elettronica non trovato'),
                ];
            }

            // Verifica che la fattura elettronica sia valida
            if (!$fattura_elettronica->isGenerated()) {
                return [
                    'code' => 400,
                    'message' => tr('Fattura elettronica non generata correttamente'),
                ];
            }

            $response = static::request('POST', 'invio_fattura_xml', [
                'xml' => $file->getContent(),
                'filename' => $fattura_elettronica->getFilename(),
            ]);
            $body = static::responseBody($response);

            // Validazione della risposta
            if (empty($body) || !isset($body['status'])) {
                logger_osm()->error('Risposta API non valida per fattura '.$fattura->numero_esterno.': '.json_encode($body));

                return [
                    'code' => 500,
                    'message' => tr('Risposta non valida dal server'),
                ];
            }

            $status_code = (int) $body['status'];

            // Aggiornamento dello stato in base alla risposta
            if ($status_code == 200 || $status_code == 301) {
                // Invio riuscito
                database()->update('co_documenti', [
                    'codice_stato_fe' => 'WAIT',
                    'data_stato_fe' => date('Y-m-d H:i:s'),
                ], ['id' => $id_record]);
            } else {
                // Qualsiasi errore, imposta stato errore
                database()->update('co_documenti', [
                    'codice_stato_fe' => 'ERR',
                    'data_stato_fe' => date('Y-m-d H:i:s'),
                ], ['id' => $id_record]);

                logger_osm()->warning('Errore invio FE fattura '.$fattura->numero_esterno.': '.$body['message']);
            }

            return [
                'code' => $status_code,
                'message' => $body['message'] ?? tr('Risposta non valida dal server'),
            ];
        } catch (\UnexpectedValueException $e) {
            logger_osm()->error('Fattura elettronica non valida per ID '.$id_record.': '.$e->getMessage());

            return [
                'code' => 400,
                'message' => tr('Fattura elettronica non valida'),
            ];
        } catch (\Exception $e) {
            logger_osm()->error('Errore durante invio fattura elettronica ID '.$id_record.': '.$e->getMessage());

            return [
                'code' => 500,
                'message' => tr('Errore interno durante l\'invio: _ERR_', ['_ERR_' => $e->getMessage()]),
            ];
        }
    }

    public static function getInvoiceRecepits($id_record)
    {
        try {
            $fattura_elettronica = new FatturaElettronica($id_record);
            $filename = $fattura_elettronica->getFilename();

            $response = static::request('POST', 'notifiche_fattura', [
                'name' => $filename,
            ]);
            $body = static::responseBody($response);

            // Validazione della risposta
            if (empty($body) || !isset($body['status'])) {
                logger_osm()->error('Risposta API non valida per ricevute fattura ID '.$id_record.': '.json_encode($body));

                return [
                    'code' => 500,
                    'message' => tr('Risposta non valida dal server'),
                ];
            }

            return [
                'code' => (int) $body['status'],
                'results' => $body['results'] ?? [],
            ];
        } catch (\UnexpectedValueException) {
        }

        return [
            'code' => 400,
            'message' => tr('Fattura non generata correttamente'),
        ];
    }
}
