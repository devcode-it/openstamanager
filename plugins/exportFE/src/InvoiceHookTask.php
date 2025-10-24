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

 use Modules\Fatture\Fattura;
 use Tasks\Manager;

class InvoiceHookTask extends Manager
{
    public function execute()
    {
        $result = [
            'response' => 1,
            'message' => tr('Fatture elettroniche inviate correttamente!'),
        ];

        $inviate = 0;
        $errori = 0;
        $fatture_errore = [];

        try {
            $fatture = Fattura::where('hook_send', 1)
                ->where('codice_stato_fe', 'QUEUE')
                ->limit(25)
                ->get();

            if( $fatture->isEmpty() ){
                $result['message'] = tr('Nessuna fattura da inviare');
                return $result;
            }

            foreach ($fatture as $fattura) {
                try {
                    // Verifica che la fattura elettronica sia ancora valida
                    $fattura_elettronica = new FatturaElettronica($fattura->id);
                    if (!$fattura_elettronica->isGenerated()) {
                        // Fattura elettronica non più valida, rimuovi dalla coda
                        $fattura->hook_send = false;
                        $fattura->codice_stato_fe = 'GEN';
                        $fattura->data_stato_fe = date('Y-m-d H:i:s');
                        $fattura->save();
                        continue;
                    }

                    $response_invio = Interaction::sendInvoice($fattura->id);

                    if ($response_invio['code'] == 200 || $response_invio['code'] == 301) {
                        // Invio riuscito
                        $fattura->hook_send = false;
                        $fattura->save();
                        $inviate++;
                    } else {
                        // Qualsiasi errore, rimuovi dalla coda e imposta errore
                        $fattura->hook_send = false;
                        $fattura->codice_stato_fe = 'ERR';
                        $fattura->save();
                        $errori++;
                        $fatture_errore[] = $fattura->numero_esterno.' ('.$response_invio['message'].')';
                    }
                } catch (\UnexpectedValueException $e) {
                    // Fattura elettronica non valida, rimuovi dalla coda
                    $fattura->hook_send = false;
                    $fattura->codice_stato_fe = 'GEN';
                    $fattura->save();
                } catch (\Exception $e) {
                    // Errore generico, rimuovi dalla coda e imposta errore
                    $fattura->hook_send = false;
                    $fattura->codice_stato_fe = 'ERR';
                    $fattura->save();
                    $errori++;
                    $fatture_errore[] = $fattura->numero_esterno.' (errore: '.$e->getMessage().')';

                    // Log dell'errore per debugging
                    logger()->error('Errore invio FE per fattura '.$fattura->numero_esterno.': '.$e->getMessage());
                }
            }

            // Costruzione messaggio di risposta
            if ($inviate > 0 && $errori == 0) {
                $result['message'] = tr('_NUM_ fatture elettroniche inviate correttamente!', ['_NUM_' => $inviate]);
            } elseif ($inviate > 0 && $errori > 0) {
                $result['response'] = 2;
                $result['message'] = tr('_SENT_ fatture inviate, _ERR_ con errori: _LIST_', [
                    '_SENT_' => $inviate,
                    '_ERR_' => $errori,
                    '_LIST_' => implode(', ', $fatture_errore)
                ]);
            } elseif ($errori > 0) {
                $result['response'] = 2;
                $result['message'] = tr('Errori nell\'invio di _ERR_ fatture: _LIST_', [
                    '_ERR_' => $errori,
                    '_LIST_' => implode(', ', $fatture_errore)
                ]);
            }

        } catch (\Exception $e) {
            $result['response'] = 2;
            $result['message'] = tr('Errore durante l\'invio delle fatture elettroniche: _ERR_', [
                '_ERR_' => $e->getMessage(),
            ])."<br>";

            // Log dell'errore critico
            logger()->error('Errore critico nel task invio FE: '.$e->getMessage());
        }

        return $result;
    }
}
