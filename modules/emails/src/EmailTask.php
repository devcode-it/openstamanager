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
use PHPMailer\PHPMailer\Exception;
use Tasks\Manager;

class EmailTask extends Manager
{
    public function needsExecution()
    {
        $lista = database()->fetchArray('SELECT * FROM em_emails WHERE (sent_at IS NULL OR failed_at IS NOT NULL) AND attempt<'.prepare(setting('Numero massimo di tentativi')).' ORDER BY created_at');
        $remaining = sizeof($lista);

        return !empty($remaining);
    }

    public function execute()
    {
        $lista = database()->fetchArray('SELECT * FROM em_emails WHERE (sent_at IS NULL OR failed_at IS NOT NULL) AND attempt<'.prepare(setting('Numero massimo di tentativi')).' ORDER BY created_at LIMIT 0,'.setting('Numero email da inviare in contemporanea per account'));

        foreach ($lista as $mail) {
            $mail = Mail::find($mail['id']);

            try {
                $email = EmailNotification::build($mail);
                $email->send();
            } catch (Exception $e) {
                echo $e;
            }
        }

        return $lista;
    }
}
