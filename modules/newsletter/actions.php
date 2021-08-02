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

use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\Referente;
use Modules\Anagrafiche\Sede;
use Modules\Emails\Mail;
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
        $newsletter->content = $_POST['content']; //filter('content');

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

            $mail = Mail::build($user, $template, $anagrafica->id);

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
        $destinatari = [];

        // Selezione manuale
        $id_receivers = post('receivers');
        foreach ($id_receivers as $id_receiver) {
            list($tipo, $id) = explode('_', $id_receiver);
            if ($tipo == 'anagrafica') {
                $type = Anagrafica::class;
            } elseif ($tipo == 'sede') {
                $type = Sede::class;
            } else {
                $type = Referente::class;
            }

            $destinatari[] = [
                'record_type' => $type,
                'record_id' => $id,
            ];
        }

        // Selezione da lista newsletter
        $id_list = post('id_list');
        if (!empty($id_list)) {
            $list = Lista::find($id_list);
            $receivers = $list->getDestinatari();
            $receivers = $receivers->map(function ($item, $key) {
                return [
                    'record_type' => get_class($item),
                    'record_id' => $item->id,
                ];
            });

            $destinatari = $receivers->toArray();
        }

        // Aggiornamento destinatari
        foreach ($destinatari as $destinatario) {
            $data = array_merge($destinatario, [
                'id_newsletter' => $newsletter->id,
            ]);

            $registrato = $database->select('em_newsletter_receiver', '*', $data);
            if (empty($registrato)) {
                $database->insert('em_newsletter_receiver', $data);
            }
        }

        // Controllo indirizzo e-mail aggiunto
        foreach ($newsletter->anagrafiche as $anagrafica) {
            if (!empty($anagrafica['email'])) {
                $check = Validate::isValidEmail($anagrafica['email']);

                if (empty($check['valid-format'])) {
                    $errors[] = $anagrafica['email'];
                }
            } else {
                $errors[] = tr('Indirizzo e-mail mancante per "_EMAIL_"', [
                    '_EMAIL_' => $anagrafica['ragione_sociale'],
                ]);
            }
        }

        if (!empty($errors)) {
            $message = '<ul>';
            foreach ($errors as $error) {
                $message .= '<li>'.$error.'</li>';
            }
            $message .= '</ul>';
        }

        if (!empty($message)) {
            flash()->warning(tr('Attenzione questi indirizzi e-mail non sembrano essere validi: _EMAIL_ ', [
                '_EMAIL_' => $message,
            ]));
        } else {
            flash()->info(tr('Nuovi destinatari aggiunti correttamente alla newsletter!'));
        }

        break;

    case 'remove_receiver':
        $receiver_id = post('id');
        $receiver_type = post('type');

        $database->delete('em_newsletter_receiver', [
            'record_type' => $receiver_type,
            'record_id' => $receiver_id,
            'id_newsletter' => $newsletter->id,
        ]);

        flash()->info(tr('Destinatario rimosso dalla newsletter!'));

        break;

    case 'remove_all_receivers':
        $database->delete('em_newsletter_receiver', [
            'id_newsletter' => $newsletter->id,
        ]);

        flash()->info(tr('Tutti i destinatari sono stati rimossi dalla newsletter!'));

        break;

    // Duplica newsletter
    case 'copy':
        $new = $newsletter->replicate();
        $new->state = 'DEV';
        $new->completed_at = null;
        $new->save();

        $id_record = $new->id;

        flash()->info(tr('Newsletter duplicata correttamente!'));

        break;
}
