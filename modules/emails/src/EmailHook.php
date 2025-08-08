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

use Hooks\Manager;

class EmailHook extends Manager
{
    public function isSingleton()
    {
        return true;
    }

    public function needsExecution()
    {
        // Email fallite nelle ultime 4 ore
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

        return !empty($remaining);
    }

    public function execute()
    {
        return false;
    }

    public function response()
    {
        $yesterday = date('Y-m-d', strtotime('-1 days'));
        $user = auth()->getUser();

        // Numero di email inviate
        $current = Mail::whereDate('sent_at', '>', $yesterday)
            ->where('attempt', '<', setting('Numero massimo di tentativi'))
            ->where('created_by', $user->id)
            ->count();

        // Numero totale di email
        $total = Mail::where(function ($query) use ($yesterday) {
            $query->whereDate('sent_at', '>', $yesterday)
                ->orWhereNull('sent_at');
        })
            ->where('attempt', '<', setting('Numero massimo di tentativi'))
            ->where('created_by', $user->id)
            ->count();

        $message = $total != $current ? tr('Invio email in corso...') : tr('Invio email completato!');
        $message = empty($total) ? tr('Nessuna email presente...') : $message;

        return [
            'icon' => 'fa fa-envelope text-info',
            'message' => $message,
            'show' => ($total != $current),
            'progress' => [
                'current' => $current,
                'total' => $total,
            ],
        ];
    }
}
