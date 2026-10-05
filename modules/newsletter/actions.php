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
use Modules\Emails\Template;
use Modules\ListeNewsletter\Lista;
use Modules\Newsletter\Newsletter;
use Notifications\EmailNotification;
use PHPMailer\PHPMailer\Exception;

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'add':
        $template = Template::find(post('id_template'));
        $newsletter = Newsletter::build($user, $template, filter('name'));
        $id_record = $newsletter->id;

        flash()->info(tr('Nuova campagna newsletter creata!'));

        break;

    case 'update':
        $newsletter->name = filter('name');
        $newsletter->state = filter('state');
        $newsletter->completed_at = filter('completed_at');

        $newsletter->subject = filter('subject');
        $newsletter->content = post('content');

        $newsletter->save();

        flash()->info(tr('Campagna newsletter salvata!'));

        if ($newsletter['state'] === 'OK') {
            $newsletter->completed_at = $newsletter->updated_at;
        }

        $newsletter->save();

        break;

    case 'delete':
        if ($newsletter->state != 'DEV') {
            flash()->error(tr('È possibile eliminare la newsletter solo quando è in stato di bozza!'));
            break;
        }

        $newsletter->delete();

        flash()->info(tr('Campagna newsletter rimossa!'));

        break;

    case 'send':
        @set_time_limit(0);
        @ini_set('max_execution_time', 0);
        ignore_user_abort(true);

        $newsletter = Newsletter::find($id_record);
        if (empty($newsletter)) {
            flash()->error(tr('Newsletter non trovata!'));
            break;
        }

        $template = $newsletter->template;
        $uploads = $newsletter->uploads()->pluck('id')->toArray();
        $user = auth_osm()->getUser();

        $last_id = 0;
        $batch_size = 100;

        while (true) {
            $destinatari = $newsletter->destinatari()
                ->where('id', '>', $last_id)
                ->orderBy('id', 'asc')
                ->limit($batch_size)
                ->get();

            if ($destinatari->isEmpty()) {
                break;
            }

            foreach ($destinatari as $destinatario) {
                $last_id = $destinatario->id;

                if (empty($destinatario->id_email)) {
                    $mail = $newsletter->inviaDestinatario($destinatario, false, $uploads, $template, $user);

                    // Aggiornamento riferimento per la newsletter
                    if (!empty($mail)) {
                        $destinatario->id_email = $mail->id;
                        $destinatario->save();
                    }
                }
            }

            if ($destinatari->count() < $batch_size) {
                break;
            }
        }

        // Aggiornamento stato newsletter
        $newsletter->state = 'WAIT';
        $newsletter->save();
        $newsletter->fixStato();

        flash()->info(tr('Campagna newsletter in invio!'));

        break;

    case 'send_batch':
        @set_time_limit(0);
        @ini_set('max_execution_time', 0);
        ignore_user_abort(true);

        $newsletter = Newsletter::find($id_record);
        if (empty($newsletter)) {
            echo json_encode(['error' => tr('Newsletter non trovata')]);
            break;
        }

        $last_id = (int) post('last_id');
        $batch_size = (int) post('batch_size') ?: 50;
        if ($batch_size < 1) {
            $batch_size = 50;
        }

        $total = $newsletter->destinatari()->count();
        $processed = (int) post('processed');

        $template = $newsletter->template;
        $uploads = $newsletter->uploads()->pluck('id')->toArray();
        $user = auth_osm()->getUser();

        $destinatari = $newsletter->destinatari()
            ->where('id', '>', $last_id)
            ->orderBy('id', 'asc')
            ->limit($batch_size)
            ->get();

        if ($destinatari->isEmpty()) {
            $newsletter->state = 'WAIT';
            $newsletter->save();
            $newsletter->fixStato();

            echo json_encode([
                'completed' => true,
                'total' => $total,
                'processed' => $total,
                'last_id' => $last_id,
            ]);
            break;
        }

        $current_last_id = $last_id;
        foreach ($destinatari as $destinatario) {
            $current_last_id = $destinatario->id;
            ++$processed;

            if (empty($destinatario->id_email)) {
                $mail = $newsletter->inviaDestinatario($destinatario, false, $uploads, $template, $user);

                if (!empty($mail)) {
                    $destinatario->id_email = $mail->id;
                    $destinatario->save();
                }
            }
        }

        $completed = ($destinatari->count() < $batch_size);
        if ($completed) {
            $newsletter->state = 'WAIT';
            $newsletter->save();
            $newsletter->fixStato();
        }

        echo json_encode([
            'completed' => $completed,
            'total' => $total,
            'processed' => min($processed, $total),
            'last_id' => $current_last_id,
        ]);

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
                } catch (Exception) {
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
                'id_email' => null,
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
        if ($newsletter->state != 'DEV') {
            flash()->error(tr('È possibile aggiungere i destinatari solo quando la newsletter è in stato di bozza!'));
            break;
        }

        $tipo_anagrafica = prepare(Anagrafica::class);
        $tipo_sede = prepare(Sede::class);
        $tipo_referente = prepare(Referente::class);
        $id_newsletter = prepare($newsletter->id);

        // Selezione manuale
        $id_receivers = post('receivers');
        if (!empty($id_receivers)) {
            foreach ($id_receivers as $id_receiver) {
                [$tipo, $id] = explode('_', (string) $id_receiver);
                if ($tipo == 'anagrafica') {
                    $type = Anagrafica::class;
                    $entity = Anagrafica::find($id);
                } elseif ($tipo == 'sede') {
                    $type = Sede::class;
                    $entity = Sede::find($id);
                } else {
                    $type = Referente::class;
                    $entity = Referente::find($id);
                }

                if (empty($entity)) {
                    continue;
                }

                $email = strtolower(trim((string) $entity->email));

                // Dati di registrazione
                $data = [
                    'record_type' => $type,
                    'record_id' => $id,
                    'id_newsletter' => $newsletter->id,
                ];

                // Aggiornamento destinatari
                $registrato = $database->select('em_newsletter_receiver', '*', [], $data);
                if (empty($registrato)) {
                    // Controllo se l'indirizzo email è già presente tra i destinatari di questa newsletter
                    if (!empty($email)) {
                        $email_prep = prepare($email);
                        $emailExists = !empty($database->fetchOne("
                            SELECT 1 FROM em_newsletter_receiver nr
                            LEFT JOIN an_anagrafiche a ON nr.record_type = {$tipo_anagrafica} AND a.id = nr.record_id
                            LEFT JOIN an_sedi s ON nr.record_type = {$tipo_sede} AND s.id = nr.record_id
                            LEFT JOIN an_referenti r ON nr.record_type = {$tipo_referente} AND r.id = nr.record_id
                            WHERE nr.id_newsletter = {$id_newsletter}
                            AND LOWER(TRIM(COALESCE(a.email, s.email, r.email))) = {$email_prep}
                            LIMIT 1
                        "));

                        if ($emailExists) {
                            continue;
                        }
                    }

                    $database->insert('em_newsletter_receiver', $data);
                }
            }
        }

        // Selezione da lista newsletter
        $id_list = post('id_list');
        if (!empty($id_list)) {
            // Aggiornamento della lista
            $lista = Lista::find($id_list);
            $query = $lista->query;
            if (check_query($query)) {
                $lista->query = html_entity_decode((string) $query);
            }
            $lista->save();

            $id_list_prep = prepare($id_list);

            // Rimozione preventiva dei record duplicati dalla newsletter
            $database->query('DELETE em_newsletter_receiver.* FROM em_newsletter_receiver
                INNER JOIN em_list_receiver ON em_list_receiver.record_type = em_newsletter_receiver.record_type AND em_list_receiver.record_id = em_newsletter_receiver.record_id
            WHERE em_newsletter_receiver.id_newsletter = '.$id_newsletter.' AND em_list_receiver.id_list = '.$id_list_prep);

            // Copia dei record della lista newsletter evitando duplicazioni di email
            $database->query("
                INSERT INTO em_newsletter_receiver (id_newsletter, record_type, record_id)
                SELECT {$id_newsletter}, sub.record_type, sub.record_id
                FROM (
                    SELECT 
                        lr.record_type,
                        lr.record_id,
                        LOWER(TRIM(COALESCE(a.email, s.email, r.email))) AS email_clean,
                        ROW_NUMBER() OVER(
                            PARTITION BY CASE 
                                WHEN TRIM(COALESCE(a.email, s.email, r.email)) != '' 
                                THEN LOWER(TRIM(COALESCE(a.email, s.email, r.email)))
                                ELSE CONCAT(lr.record_type, '_', lr.record_id)
                            END 
                            ORDER BY lr.id ASC
                        ) AS rn
                    FROM em_list_receiver lr
                    LEFT JOIN an_anagrafiche a ON lr.record_type = {$tipo_anagrafica} AND a.id = lr.record_id
                    LEFT JOIN an_sedi s ON lr.record_type = {$tipo_sede} AND s.id = lr.record_id
                    LEFT JOIN an_referenti r ON lr.record_type = {$tipo_referente} AND r.id = lr.record_id
                    WHERE lr.id_list = {$id_list_prep}
                ) sub
                WHERE sub.rn = 1
                AND (
                    sub.email_clean = '' 
                    OR sub.email_clean IS NULL 
                    OR sub.email_clean NOT IN (
                        SELECT LOWER(TRIM(COALESCE(cur_a.email, cur_s.email, cur_r.email)))
                        FROM em_newsletter_receiver cur_nr
                        LEFT JOIN an_anagrafiche cur_a ON cur_nr.record_type = {$tipo_anagrafica} AND cur_a.id = cur_nr.record_id
                        LEFT JOIN an_sedi cur_s ON cur_nr.record_type = {$tipo_sede} AND cur_s.id = cur_nr.record_id
                        LEFT JOIN an_referenti cur_r ON cur_nr.record_type = {$tipo_referente} AND cur_r.id = cur_nr.record_id
                        WHERE cur_nr.id_newsletter = {$id_newsletter}
                        AND TRIM(COALESCE(cur_a.email, cur_s.email, cur_r.email)) != ''
                    )
                )
            ");
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
                    $descrizione .= ' ['.$destinatario->nome_sede.']';
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
        if ($newsletter->state != 'DEV') {
            flash()->error(tr('È possibile rimuovere i destinatari solo quando la newsletter è in stato di bozza!'));
            break;
        }

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
        if ($newsletter->state != 'DEV') {
            flash()->error(tr('È possibile rimuovere i destinatari solo quando la newsletter è in stato di bozza!'));
            break;
        }

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
