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

use Modules\Emails\Template;
use Modules\Newsletter\Lista;
use Modules\Newsletter\Newsletter;

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'add':
        $template = Template::find(filter('id_template'));
        $newsletter = Newsletter::build($user, $template, filter('name'));

        $id_record = $newsletter->id;

        flash()->info(tr('Nuova campagna newsletter creata!'));

        break;

    case 'update':
        $newsletter->name = filter('name');
        $newsletter->state = filter('state');
        $newsletter->completed_at = filter('completed_at');

        $newsletter->subject = filter('subject');
        $newsletter->content = filter('content');

        $newsletter->save();

        flash()->info(tr('Campagna newsletter salvata!'));

        break;

    case 'delete':
        $newsletter->delete();

        flash()->info(tr('Campagna newsletter rimossa!'));

        break;

    case 'send':
        $anagrafiche = $newsletter->anagrafiche;
        $template = $newsletter->template;

        $uploads = $newsletter->uploads()->pluck('id');

        foreach ($anagrafiche as $anagrafica) {
            if (empty($anagrafica['email']) || empty($anagrafica['enable_newsletter'])) {
                continue;
            }

            $mail = \Modules\Emails\Mail::build($user, $template, $anagrafica->id);

            $mail->addReceiver($anagrafica['email']);
            $mail->subject = $newsletter->subject;
            $mail->content = $newsletter->content;

            $mail->id_newsletter = $newsletter->id;

            foreach ($uploads as $upload) {
                $mail->addUpload($upload);
            }

            $mail->save();

            $newsletter->anagrafiche()->updateExistingPivot($anagrafica->id, ['id_email' => $mail->id]);
        }

        $newsletter->state = 'WAIT';
        $newsletter->save();

        flash()->info(tr('Campagna newsletter in invio!'));

        break;

    case 'block':
        $mails = $newsletter->emails;

        foreach ($mails as $mail) {
            if (empty($mail->sent_at)) {
                $newsletter->emails()->updateExistingPivot($mail->id, ['id_email' => null], false);

                $mail->delete();
            }
        }

        $newsletter->state = 'DEV';
        $newsletter->save();

        flash()->info(tr('Coda della campagna newsletter svuotata!'));

        break;

    case 'add_receivers':
        $receivers = post('receivers');

        $id_list = post('id_list');
        if (!empty($id_list)) {
            $list = Lista::find($id_list);
            $receivers = $list->anagrafiche->pluck('idanagrafica');
        }

        $newsletter->anagrafiche()->syncWithoutDetaching($receivers);

        flash()->info(tr('Aggiunti nuovi destinatari alla newsletter!'));

        break;

    case 'remove_receiver':
        $receiver = post('id');

        $newsletter->anagrafiche()->detach($receiver);

        flash()->info(tr('Destinatario rimosso dalla newsletter!'));

        break;
}
