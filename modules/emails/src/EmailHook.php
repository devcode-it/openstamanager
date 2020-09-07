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

namespace Modules\Emails;

use Carbon\Carbon;
use Hooks\Manager;
use Notifications\EmailNotification;
use PHPMailer\PHPMailer\Exception;

class EmailHook extends Manager
{
    public function isSingleton()
    {
        return true;
    }

    public function needsExecution()
    {
        $diff = date('Y-m-d H:i:s', strtotime('-4 hours'));
        $failed = function ($query) use ($diff) {
            $query->where('failed_at', '<', $diff)
                ->orWhereNull('failed_at');
        };

        $accounts = Account::all();
        $remaining = Mail::whereNull('sent_at')
            ->where($failed)
            ->where('attempt', '<', 10)
            ->whereIn('id_account', $accounts->pluck('id'))
            ->count();

        return !empty($remaining);
    }

    public function execute()
    {
        $diff = date('Y-m-d H:i:s', strtotime('-4 hours'));
        $failed = function ($query) use ($diff) {
            $query->where('failed_at', '<', $diff)
                ->orWhereNull('failed_at');
        };

        $accounts = Account::all();
        $list = [];
        foreach ($accounts as $account) {
            $last_mail = $account->emails()
                ->whereNotNull('sent_at')
                ->orderBy('sent_at')
                ->first();

            // Controllo sul timeout dell'account
            $date = new Carbon($last_mail->sent_at);
            $now = new Carbon();
            $diff_milliseconds = $date->diffInMilliseconds($now);

            if (empty($last_mail) || $diff_milliseconds > $account->timeout) {
                $mail = Mail::whereNull('sent_at')
                    ->where('id_account', $account->id)
                    ->where($failed)
                    ->where('attempt', '<', 10)
                    ->orderBy('created_at')
                    ->first();

                if (!empty($mail)) {
                    $list[] = $mail;
                }
            }
        }

        foreach ($list as $mail) {
            $email = EmailNotification::build($mail);

            try {
                // Invio mail
                $email->send();
            } catch (Exception $e) {
            }
        }

        return $list;
    }

    public function response()
    {
        $yesterday = date('Y-m-d', strtotime('-1 days'));
        $user = auth()->getUser();

        $current = Mail::whereDate('sent_at', '>', $yesterday)
            ->where('attempt', '<', 10)
            ->where('created_by', $user->id)
            ->count();
        $total = Mail::where(function ($query) use ($yesterday) {
            $query->whereDate('sent_at', '>', $yesterday)
                ->orWhereNull('sent_at');
        })
            ->where('attempt', '<', 10)
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
