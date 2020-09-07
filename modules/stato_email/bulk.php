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

use Modules\Emails\Mail;

switch (post('op')) {
    case 'delete-bulk':
        $i = 0;
        foreach ($id_records as $id_record) {
            if (isset($id_record)) {
                $mail = Mail::find($id_record);
                if (empty($mail->sent_at)) {
                    $mail->delete();
                    ++$i;
                }
            }
        }

        if ($i > 0) {
            flash()->info(tr('Email rimosse dalla coda di invio'));
        } else {
            flash()->warning(tr('Nessuna email rimossa dalla coda di invio'));
        }

        break;
}

$operations['delete-bulk'] = [
    'text' => '<span><i class="fa fa-trash"></i> '.tr('Elimina email selezionate e non ancora inviate').'</span>',
    'data' => [
        'msg' => tr('Vuoi davvero eliminare dalla coda di invio le email selezionate?'),
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-danger',
    ],
];

return $operations;
