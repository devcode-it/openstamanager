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

namespace Modules\StatoEmail;

use Modules\Emails\Mail;
use Tasks\Manager;

/**
 * Task dedicato all'eliminazione automatica della coda di invio dopo i giorni indicati nelle impostazioni.
 */
class EliminaMailTask extends Manager
{
    public function needsExecution()
    {
        if (setting('Numero di giorni mantenimento coda di invio') > 0) {
            $count = Mail::where('sent_at', '<', database()->raw('DATE_SUB(NOW(), INTERVAL '.setting('Numero di giorni mantenimento coda di invio').' DAY)'))
                ->whereNotNull('id_newsletter')
                ->count();

            return $count > 0;
        }

        return false;
    }

    public function execute()
    {
        $result = [
            'response' => 1,
            'message' => tr('Email eliminate correttamente!'),
        ];

        if (setting('Numero di giorni mantenimento coda di invio') > 0) {
            $emails = Mail::where('sent_at', '<', database()->raw('DATE_SUB(NOW(), INTERVAL '.setting('Numero di giorni mantenimento coda di invio').' DAY)'))
                ->whereNotNull('id_newsletter')
                ->get();

            if ($emails->isEmpty()) {
                $result = [
                    'response' => 1,
                    'message' => tr('Nessuna email da eliminare'),
                ];
            }

            foreach ($emails as $email) {
                $email->delete();
            }
        } else {
            $result = [
                'response' => 2,
                'message' => tr('Eliminazione automatica disattivata da impostazioni'),
            ];
        }

        return $result;
    }
}
