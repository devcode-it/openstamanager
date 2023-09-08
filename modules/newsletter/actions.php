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
use Modules\ListeNewsletter\Lista;
use Modules\Newsletter\Newsletter;
use Notifications\EmailNotification;
use PHPMailer\PHPMailer\Exception;

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
        $newsletter->content = $_POST['content']; // post('content', true);

        $newsletter->save();

        flash()->info(tr('Campagna newsletter salvata!'));

        if($newsletter->state = "OK") {
            $newsletter->completed_at = $newsletter -> updated_at;
        };
        
        $newsletter->save();

        break;

    case 'delete':
        $newsletter->delete();

        flash()->info(tr('Campagna newsletter rimossa!'));

        break;

    case 'send':
        $newsletter = Newsletter::find($id_record);

        $destinatari = $newsletter->destinatari();
        $count = $destinatari->count();
        for ($i = 0; $i < $count; ++$i) {
            $destinatario = $destinatari->skip($i)->first();

            if (empty($destinatario->id_email)) {
                $mail = $newsletter->inviaDestinatario($destinatario);

                // Aggiornamento riferimento per la newsletter
                if (!empty($mail)) {
                    $destinatario->id_email = $mail->id;
                    $destinatario->save();
                }
            }
        }

        // Aggiornamento stato newsletter
        $newsletter->state = 'WAIT';
        $newsletter->save();

        flash()->info(tr('Campagna newsletter in invio!'));

        break;

    case 'send-line':
        $receiver_id = post('id');
        $receiver_type = post('type');
        $test = post('test');

        // Individuazione destinatario interessato
        $newsletter = Newsletter::find($id_record);
        $destinatario = $newsletter->destinatari()
            ->where('record_type', '=', $receiver_type)
            ->where('record_id', '=', $receiver_id)
            ->first();

        // Generazione email e tentativo di invio
        $inviata = false;
        if (!empty($destinatario)) {
            if ($test) {
                $mail = $newsletter->inviaDestinatario($destinatario, true);

                try {
                    $email = EmailNotification::build($mail, true);
                    $email->send();

                    $inviata = true;
                } catch (Exception $e) {
                    // $mail->delete();
                }
            } else {
                $mail = $newsletter->inviaDestinatario($destinatario);

                // Aggiornamento riferimento per la newsletter
                if (!empty($mail)) {
                    $destinatario->id_email = $mail->id;
                    $destinatario->save();
                    $inviata = true;
                }
            }
        }

        echo json_encode([
            'result' => $inviata,
        ]);

        break;

    case 'block':
        $mails = $newsletter->emails;

        foreach ($mails as $mail) {
            if (!empty($mail->sent_at)) {
                continue;
            }

            // Rimozione riferimento email dalla newsletter
            $database->update('em_newsletter_receiver', [
                'id_email' => $null,
            ], [
                'id_email' => $mail->id,
                'id_newsletter' => $newsletter->id,
            ]);

            // Rimozione email
            $mail->delete();
        }

        // Aggiornamento stato newsletter
        $newsletter->state = 'DEV';
        $newsletter->save();

        flash()->info(tr('Coda della campagna newsletter svuotata!'));

        break;

    case 'add_receivers':
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

            // Dati di registrazione
            $data = [
                'record_type' => $type,
                'record_id' => $id,
                'id_newsletter' => $newsletter->id,
            ];

            // Aggiornamento destinatari
            $registrato = $database->select('em_newsletter_receiver', '*', $data);
            if (empty($registrato)) {
                $database->insert('em_newsletter_receiver', $data);
            }
        }

        // Selezione da lista newsletter
        $id_list = post('id_list');
        if (!empty($id_list)) {
            //Aggiornamento della lista
            $lista = Lista::find($id_list);
            $query = $lista->query;
            if (check_query($query)) {
                $lista->query = html_entity_decode($query);
            }
            $lista->save();

            // Rimozione preventiva dei record duplicati dalla newsletter
            $database->query('DELETE em_newsletter_receiver.* FROM em_newsletter_receiver
                INNER JOIN em_list_receiver ON em_list_receiver.record_type = em_newsletter_receiver.record_type AND em_list_receiver.record_id = em_newsletter_receiver.record_id
            WHERE em_newsletter_receiver.id_newsletter = '.prepare($newsletter->id).' AND em_list_receiver.id_list = '.prepare($id_list));

            // Copia dei record della lista newsletter
            $database->query('INSERT INTO em_newsletter_receiver (id_newsletter, record_type, record_id) SELECT '.prepare($newsletter->id).', record_type, record_id FROM em_list_receiver WHERE id_list = '.prepare($id_list));
        }

        /*
        // Controllo indirizzo e-mail presente
        $destinatari = $newsletter->destinatari();
        foreach ($destinatari as $destinatario) {
            $anagrafica = $destinatario instanceof Anagrafica ? $destinatario : $destinatario->anagrafica;

            if (!empty($destinatario->email)) {
                $check = Validate::isValidEmail($destinatario->email);

                if (empty($check['valid-format'])) {
                    $errors[] = $destinatario->email;
                }
            } else {
                $descrizione = $anagrafica->ragione_sociale;

                if ($destinatario instanceof Sede) {
                    $descrizione .= ' ['.$destinatario->nomesede.']';
                } elseif ($destinatario instanceof Referente) {
                    $descrizione .= ' ['.$destinatario->nome.']';
                }

                $errors[] = tr('Indirizzo e-mail mancante per "_NOME_"', [
                    '_NOME_' => $descrizione,
                ]);
            }
        }

        if (!empty($errors)) {
            $message = '<ul>';
            foreach ($errors as $error) {
                $message .= '<li>'.$error.'</li>';
            }
            $message .= '</ul>';
        }*/

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
