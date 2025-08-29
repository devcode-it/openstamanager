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

        try {
            $fatture = Fattura::where('hook_send', 1)
                ->where('codice_stato_fe', 'QUEUE')
                ->limit(25)
                ->get();

            if ($fatture->isEmpty()) {
                $result['message'] = tr('Nessuna fattura da inviare');
            }

            foreach ($fatture as $fattura) {
                $response_invio = Interaction::sendInvoice($fattura->id);

                if ($response_invio['code'] == 200 || $response_invio['code'] == 301) {
                    $fattura->hook_send = false;
                    $fattura->save();
                }
            }
        } catch (\Exception $e) {
            $result['response'] = 2;
            $result['message'] = tr('Errore durante l\'invio delle fatture elettroniche: _ERR_', [
                '_ERR_' => $e->getMessage(),
            ]).'<br>';
        }

        return $result;
    }
}
