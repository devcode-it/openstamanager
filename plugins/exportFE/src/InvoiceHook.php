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

use Hooks\Manager;
use Modules\Fatture\Fattura;

/**
 * Hook dedicato all'invio automatico delle Fatture Elettroniche inserite in coda di invio tramite azioni di gruppo.
 */
class InvoiceHook extends Manager
{
    public function isSingleton()
    {
        return true;
    }

    public function needsExecution()
    {
        if (!Interaction::isEnabled()) {
            return false;
        }

        $remaining = Fattura::where('hook_send', 1)
            ->where('codice_stato_fe', 'QUEUE')
            ->count();

        return !empty($remaining);
    }

    public function execute()
    {
        $fattura = Fattura::where('hook_send', 1)
            ->where('codice_stato_fe', 'QUEUE')
            ->first();

        $result = Interaction::sendInvoice($fattura->id);

        if ($result['code'] == 200 || $result['code'] == 301) {
            $fattura->hook_send = false;
            $fattura->save();
        }

        return $result;
    }

    public function response()
    {
        $completed = !$this->needsExecution();
        $message = tr('Invio fatture elettroniche in corso...');
        $icon = 'text-info';

        $errors = Fattura::where('hook_send', 1)
            ->where('codice_stato_fe', 'ERR')
            ->count();

        if ($completed) {
            if (empty($errors)) {
                $message = tr('Invio fatture elettroniche completato!');
            } else {
                $message = tr('Invio fatture elettroniche completato con errori');
                $icon = 'text-danger';
            }
        }

        return [
            'icon' => 'fa fa-envelope '.$icon,
            'message' => $message,
            'show' => !$completed || !empty($errors),
        ];
    }
}
