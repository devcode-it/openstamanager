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
    private const MAX_ATTEMPTS = 3;

    public function execute()
    {
        $result = [
            'response' => 1,
            'message' => tr('Fatture elettroniche inviate correttamente!'),
        ];

        $inviate = 0;
        $errori = 0;
        $in_attesa = 0;
        $fatture_errore = [];

        try {
            $fatture = Fattura::where('hook_send', 1)
                ->where('codice_stato_fe', 'QUEUE')
                ->limit(25)
                ->get();

            if ($fatture->isEmpty()) {
                $result['message'] = tr('Nessuna fattura da inviare');

                return $result;
            }

            foreach ($fatture as $fattura) {
                try {
                    $this->processInvoice($fattura, $inviate, $errori, $in_attesa, $fatture_errore);
                } catch (\UnexpectedValueException) {
                    $this->resetInvoice($fattura);
                } catch (\Exception $e) {
                    $this->handleInvoiceException($fattura, $e, $errori, $in_attesa, $fatture_errore);
                }
            }

            $this->buildResultMessage($result, $inviate, $errori, $in_attesa, $fatture_errore);
        } catch (\Exception $e) {
            $result['response'] = 2;
            $result['message'] = tr('Errore durante l\'invio delle fatture elettroniche: _ERR_', [
                '_ERR_' => $e->getMessage(),
            ]).'<br>';

            logger_osm()->error('Errore critico nel task invio FE: '.$e->getMessage());
        }

        return $result;
    }

    private function processInvoice($fattura, &$inviate, &$errori, &$in_attesa, &$fatture_errore)
    {
        $fattura_elettronica = new FatturaElettronica($fattura->id);
        if (!$fattura_elettronica->isGenerated()) {
            $this->resetInvoice($fattura);
            return;
        }

        $response_invio = Interaction::sendInvoice($fattura->id);

        if ($response_invio['code'] == 200 || $response_invio['code'] == 301) {
            $fattura->hook_send = false;
            $fattura->fe_attempt = 0;
            $fattura->save();
            ++$inviate;
        } else {
            $this->handleFailedAttempt($fattura, $response_invio['message'], $errori, $in_attesa, $fatture_errore);
        }
    }

    private function resetInvoice($fattura)
    {
        $fattura->hook_send = false;
        $fattura->codice_stato_fe = 'GEN';
        $fattura->data_stato_fe = date('Y-m-d H:i:s');
        $fattura->fe_attempt = 0;
        $fattura->save();
    }

    private function handleFailedAttempt($fattura, $message, &$errori, &$in_attesa, &$fatture_errore)
    {
        $fattura->fe_attempt = ($fattura->fe_attempt ?? 0) + 1;

        if ($fattura->fe_attempt >= self::MAX_ATTEMPTS) {
            $this->markAsFailed($fattura, $message, $errori, $fatture_errore);
        } else {
            $this->keepInQueue($fattura, $in_attesa);
        }
    }

    private function markAsFailed($fattura, $message, &$errori, &$fatture_errore)
    {
        $fattura->hook_send = false;
        $fattura->codice_stato_fe = 'ERR';
        $fattura->fe_failed_at = date('Y-m-d H:i:s');
        $fattura->save();
        ++$errori;
        $fatture_errore[] = $fattura->numero_esterno.' ('.$message.')';
    }

    private function keepInQueue($fattura, &$in_attesa)
    {
        $fattura->codice_stato_fe = 'QUEUE';
        $fattura->data_stato_fe = date('Y-m-d H:i:s');
        $fattura->save();
        ++$in_attesa;
    }

    private function handleInvoiceException($fattura, \Exception $e, &$errori, &$in_attesa, &$fatture_errore)
    {
        $fattura->fe_attempt = ($fattura->fe_attempt ?? 0) + 1;

        if ($fattura->fe_attempt >= self::MAX_ATTEMPTS) {
            $this->markAsFailed($fattura, 'errore: '.$e->getMessage(), $errori, $fatture_errore);
            logger_osm()->error('Errore invio FE per fattura '.$fattura->numero_esterno.': '.$e->getMessage());
        } else {
            $this->keepInQueue($fattura, $in_attesa);
            logger_osm()->warning('Tentativo '.$fattura->fe_attempt.'/'.self::MAX_ATTEMPTS.' per fattura '.$fattura->numero_esterno.': '.$e->getMessage());
        }
    }

    private function buildResultMessage(&$result, $inviate, $errori, $in_attesa, $fatture_errore)
    {
        if ($inviate > 0 && $errori == 0 && $in_attesa == 0) {
            $result['message'] = tr('_NUM_ fatture elettroniche inviate correttamente!', ['_NUM_' => $inviate]);
        } elseif ($inviate > 0 && $errori == 0 && $in_attesa > 0) {
            $result['response'] = 2;
            $result['message'] = tr('_SENT_ fatture inviate correttamente, _WAIT_ in attesa di nuovo tentativo (max _MAX_)', [
                '_SENT_' => $inviate,
                '_WAIT_' => $in_attesa,
                '_MAX_' => self::MAX_ATTEMPTS,
            ]);
        } elseif ($inviate > 0 && $errori > 0) {
            $result['response'] = 2;
            $result['message'] = tr('_SENT_ fatture inviate, _ERR_ con errori, _WAIT_ in attesa di nuovo tentativo (max _MAX_): _LIST_', [
                '_SENT_' => $inviate,
                '_ERR_' => $errori,
                '_WAIT_' => $in_attesa,
                '_MAX_' => self::MAX_ATTEMPTS,
                '_LIST_' => implode(', ', $fatture_errore),
            ]);
        } elseif ($errori > 0 && $in_attesa > 0) {
            $result['response'] = 2;
            $result['message'] = tr('Errori nell\'invio di _ERR_ fatture, _WAIT_ in attesa di nuovo tentativo (max _MAX_): _LIST_', [
                '_ERR_' => $errori,
                '_WAIT_' => $in_attesa,
                '_MAX_' => self::MAX_ATTEMPTS,
                '_LIST_' => implode(', ', $fatture_errore),
            ]);
        } elseif ($errori > 0) {
            $result['response'] = 2;
            $result['message'] = tr('Errori nell\'invio di _ERR_ fatture: _LIST_', [
                '_ERR_' => $errori,
                '_LIST_' => implode(', ', $fatture_errore),
            ]);
        } elseif ($in_attesa > 0) {
            $result['response'] = 2;
            $result['message'] = tr('_WAIT_ fatture in attesa di nuovo tentativo (max _MAX_)', [
                '_WAIT_' => $in_attesa,
                '_MAX_' => self::MAX_ATTEMPTS,
            ]);
        }
    }
}
