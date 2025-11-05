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

use Carbon\Carbon;
use Notifications\EmailNotification;
use Tasks\Manager;
class EmailHookTask extends Manager
{
    public function execute()
    {
        $result = [
            'response' => 1,
            'message' => tr('Email inviate correttamente!'),
        ];

        $diff = date('Y-m-d H:i:s', strtotime('-4 hours'));
        $failed = function ($query) use ($diff) {
            $query->where('failed_at', '<', $diff)
                ->orWhereNull('failed_at');
        };

        // Email da inviare per tutti gli account
        $accounts = Account::all();
        $remaining = Mail::whereNull('sent_at')
            ->where($failed)
            ->where('attempt', '<', setting('Numero massimo di tentativi'))
            ->whereIn('id_account', $accounts->pluck('id'))
            ->count();

        if (empty($remaining)) {
            $result = [
                'response' => 1,
                'message' => tr('Nessuna email da inviare'),
            ];
        } else {
            // Parametri per l'invio
            $numero_tentativi = setting('Numero massimo di tentativi');
            $numero_email = setting('Numero email da inviare in contemporanea per account');
            $numero_email = $numero_email < 1 ? 1 : $numero_email;

            // Selezione email per account
            $accounts = Account::all();
            $lista = collect();
            foreach ($accounts as $account) {
                // Ultima email inviata per l'account
                $last_mail = $account->emails()
                    ->whereNotNull('sent_at')
                    ->orderBy('sent_at')
                    ->first();

                // Controllo sul timeout dell'account
                $date = new Carbon($last_mail->sent_at);
                $now = new Carbon();
                $diff_milliseconds = $date->diffInMilliseconds($now);

                // Timeout per l'uso dell'account email
                if (empty($last_mail) || $diff_milliseconds > $account->timeout) {
                    $lista_account = Mail::whereNull('sent_at')
                        ->where('id_account', $account->id)
                        ->where($failed)
                        ->where('attempt', '<', $numero_tentativi)
                        ->orderBy('created_at')
                        ->take($numero_email)
                        ->get();

                    $lista = $lista->concat($lista_account);
                }
            }

            // Invio effettivo
            foreach ($lista as $mail) {
                try {
                    $email = EmailNotification::build($mail);
                    $email->send();
                } catch (\Exception $e) {
                    echo $e;

                    $result['response'] = 2;
                    $result['message'] = tr('Errore durante l\'invio delle email: _ERR_', [
                        '_ERR_' => $e->getMessage(),
                    ]).'<br>';
                }
            }
        }

        return $result;
    }
}
