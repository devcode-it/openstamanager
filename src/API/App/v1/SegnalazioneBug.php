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

namespace API\App\v1;

use API\Interfaces\CreateInterface;
use API\Interfaces\RetrieveInterface;
use API\Resource;
use Modules\Emails\Account;
use Modules\Emails\Mail;
use Modules\Emails\Template;
use Notifications\EmailNotification;

class SegnalazioneBug extends Resource implements RetrieveInterface, CreateInterface
{
    protected static $bug_email = 'info@openstamanager.com';

    public function retrieve($request)
    {
        $account = Account::where('predefined', true)->first();

        return [
            'sender' => [
                'name' => $account['from_name'],
                'email' => $account['from_address'],
            ],
            'receiver' => self::$bug_email,
        ];
    }

    public function create($request)
    {
        $account = Account::where('predefined', true)->first();

        // Preparazione email
        $mail = new EmailNotification($account);

        // Destinatario
        $mail->AddAddress(self::$bug_email);

        // Oggetto
        $mail->subject = 'Segnalazione bug App OSM '.$request['version'];

        $infos = [

        ];

        $body = '';
        foreach ($infos as $key => $value) {
            $body .= '<p>'.$key.': '.$value.'</p>';
        }

        // Contenuti
        $mail->content = $body;

        // Tentativo di invio diretto
        $email_success = $mail->send();

        return [
            'sent' => $email_success,
        ];
    }
}
