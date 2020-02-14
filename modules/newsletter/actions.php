<?php

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
            if (empty($anagrafica['email'])) {
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
