<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'add':
        $dbo->insert('zz_smtp', [
            'name' => $post['name'],
            'from_name' => $post['from_name'],
            'from_address' => $post['from_address'],
        ]);

        $id_record = $dbo->lastInsertedID();

        App::flash()->info(tr('Nuovo account email aggiunto!'));

        break;

    case 'update':
        $dbo->update('zz_smtp', [
            'name' => $post['name'],
            'note' => $post['note'],
            'server' => $post['server'],
            'port' => $post['port'],
            'username' => $post['username'],
            'password' => $post['password'],
            'from_name' => $post['from_name'],
            'from_address' => $post['from_address'],
            'encryption' => $post['encryption'],
            'pec' => $post['pec'],
            'main' => $post['main'],
        ], ['id' => $id_record]);

        if (!empty($post['main'])) {
            $dbo->query('UPDATE zz_smtp SET main = 0 WHERE id != '.prepare($id_record));
        }

        App::flash()->info(tr('Informazioni salvate correttamente!'));

        // Validazione indirizzo email mittente
        $check_email = Validate::isValidEmail($post['from_address']);

        // Se $check_email non è null e la riposta è negativa --> mostro il messaggio di avviso.
        if (!empty($check_email)) {
            App::flash()->info(tr('Sintassi email verificata'));

            if (is_object($check_email) && $check_email->smtp) {
                if ($check_email->smtp_check) {
                    App::flash()->info(tr('SMTP email verificato'));
                } elseif (!$check_email->smtp_check) {
                    App::flash()->warning(tr('SMTP email non verificato'));
                } else {
                    App::flash()->error(tr("Attenzione: l'SMTP email _EMAIL_ sembra non essere valido", [
                        '_EMAIL_' => $check_email->email,
                    ]));
                }
            }
        } else {
            App::flash()->error(tr("Attenzione: l'indirizzo email _EMAIL_ sembra non essere valido", [
                '_EMAIL_' => $check_email->email,
            ]));

            if (is_object($check_email) && !empty($check_email->error->info)) {
                App::flash()->error($check_email->error->info);
            }
        }

        $mail = new Mail($id_record);
        if ($mail->testSMTP()) {
            App::flash()->info(tr('Connessione SMTP riuscita'));
        } else {
            App::flash()->error(tr('Connessione SMTP non riuscita'));
        }

        break;

    case 'delete':
        $dbo->query('UPDATE zz_smtp SET deleted = 1 WHERE id='.prepare($id_record));

        App::flash()->info(tr('Account email eliminato!'));

        break;
}
