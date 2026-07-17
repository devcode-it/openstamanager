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

namespace Modules\Emails;

use Notifications\EmailNotification;
use Tasks\Manager;

class EmailTask extends Manager
{
    public function needsExecution()
    {
        $max_tentativi = setting('Numero massimo di tentativi');
        $lista = Mail::where(function($q) {
            $q->whereNull('sent_at')->orWhereNotNull('failed_at');
        })
        ->where('attempt', '<', $max_tentativi)
        ->orderBy('created_at')
        ->limit(1)
        ->get();
        $remaining = sizeof($lista);

        return !empty($remaining);
    }

    public function execute()
    {
        $max_tentativi = setting('Numero massimo di tentativi');
        $result = [
            'response' => 1,
            'message' => tr('Email inviate correttamente!'),
        ];

        $lista = Mail::where(function($q) {
            $q->whereNull('sent_at')->orWhereNotNull('failed_at');
        })
        ->where('attempt', '<', $max_tentativi)
        ->orderBy('created_at')
        ->limit(setting('Numero email da inviare in contemporanea per account'))
        ->get();

        if (empty($lista)) {
            $result = [
                'response' => 1,
                'message' => tr('Nessuna email da inviare'),
            ];
        }

        foreach ($lista as $mail) {
            $mail = Mail::find($mail['id']);

            try {
                $email = EmailNotification::build($mail);
                $email->send();
            } catch (\Exception $e) {
                echo $e;

                $result['response'] = 2;
                $result['message'] = tr('Errore durante l\'invio delle email: _ERR_', [
                    '_ERR_' => $e->getMessage(),
                ]).'<br>';

                // Aggiorna l'email come fallita
                if (!empty($mail)) {
                    $mail->failed_at = date('Y-m-d H:i:s');
                    $mail->attempt = $mail->attempt + 1;
                    $mail->save();
                }
            }
        }

        return $result;
    }
}
