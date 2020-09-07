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

include_once __DIR__.'/../../core.php';
use Notifications\EmailNotification;

switch (post('op')) {
    case 'send':

        $email = EmailNotification::build($mail);

        // Invio mail
        if ($email->send()) {
            $mail->sent_at = date('Y-m-d H:i:s');
            $mail->save();
            flash()->info(tr('Email inviata.'));
        } else {
            flash()->error(tr('Errore durante invio email.'));
        }

        break;

    case 'retry':
        $mail->attempt = 0;

        $mail->save();
        break;

    case 'delete':
        if (empty($mail->sent_at)) {
            $mail->delete();

            flash()->info(tr('Email rimossa dalla coda di invio.'));
        }

        break;
}
