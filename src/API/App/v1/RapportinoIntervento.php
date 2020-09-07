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

namespace API\App\v1;

use API\Interfaces\CreateInterface;
use API\Interfaces\RetrieveInterface;
use API\Resource;
use Modules\Emails\Mail;
use Modules\Emails\Template;
use Notifications\EmailNotification;

class RapportinoIntervento extends Resource implements RetrieveInterface, CreateInterface
{
    public function retrieve($request)
    {
        $database = database();
        $id_record = $request['id'];

        $template = Template::where('name', 'Rapportino intervento')->first();
        $module = $template->module;
        $account = $template->account;

        $body = $module->replacePlaceholders($id_record, $template['body']);
        $subject = $module->replacePlaceholders($id_record, $template['subject']);
        $email = $module->replacePlaceholders($id_record, '{email}');

        $prints = $database->fetchArray('SELECT id, title, EXISTS(SELECT id_print FROM em_print_template WHERE id_template = '.prepare($template['id']).' AND em_print_template.id_print = zz_prints.id) AS selected FROM zz_prints WHERE id_module = '.prepare($module->id).' AND enabled = 1');

        return [
            'sender' => $account['from_name'].'<'.$account['from_address'].'>',
            'email' => $email,
            'subject' => $subject,
            'body' => $body,
            'prints' => $prints,
            'read_notify' => $template->read_notify,
        ];
    }

    public function create($request)
    {
        $data = $request['data'];
        $id_record = $data['id'];

        $template = Template::where('name', 'Rapportino intervento')->first();
        $mail = Mail::build($this->getUser(), $template, $id_record);

        // Rimozione allegati predefiniti
        $mail->resetPrints();

        // Destinatari
        $receivers = $data['receivers'];
        foreach ($receivers as $receiver) {
            $mail->addReceiver($receiver['email'], $receiver['tipo']);
        }

        // Contenuti
        $mail->subject = $data['subject'];
        $mail->content = $data['body'];
        $mail->read_notify = $data['read_notify'];

        // Stampe da allegare
        $prints = $data['prints'];
        foreach ($prints as $print_id) {
            $mail->addPrint($print_id);
        }

        $mail->save();

        // Tentativo di invio diretto
        $email = EmailNotification::build($mail);
        $email_success = $email->send();

        // Rimozione email in casi di errore
        if (!$email_success) {
            $mail->delete();
        }

        return [
            'sent' => $email_success,
        ];
    }
}
