<?php

use Notifications\EmailNotification;

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'add':
        $dbo->insert('em_accounts', [
            'name' => post('name'),
            'from_name' => post('from_name'),
            'from_address' => post('from_address'),
        ]);

        $id_record = $dbo->lastInsertedID();

        flash()->info(tr('Nuovo account email aggiunto!'));

        break;

    case 'update':
        $predefined = post('predefined');
        if (!empty($predefined)) {
            $dbo->query('UPDATE em_accounts SET predefined = 0');
        }

        $dbo->update('em_accounts', [
            'name' => post('name'),
            'note' => post('note'),
            'server' => post('server'),
            'port' => post('port'),
            'username' => post('username'),
            'password' => post('password'),
            'from_name' => post('from_name'),
            'from_address' => post('from_address'),
            'encryption' => post('encryption'),
            'pec' => post('pec'),
            'ssl_no_verify' => post('ssl_no_verify'),
            'predefined' => $predefined,
        ], ['id' => $id_record]);

        flash()->info(tr('Informazioni salvate correttamente!'));

        // Validazione indirizzo email mittente
        $check_email = Validate::isValidEmail(post('from_address'));

        // Se $check_email non è null e la riposta è negativa --> mostro il messaggio di avviso.
        if (!empty($check_email)) {
            flash()->info(tr('Sintassi email verificata'));

            if (is_object($check_email) && $check_email->smtp) {
                if ($check_email->smtp_check) {
                    flash()->info(tr('SMTP email verificato'));
                } elseif (!$check_email->smtp_check) {
                    flash()->warning(tr('SMTP email non verificato'));
                } else {
                    flash()->error(tr("Attenzione: l'SMTP email _EMAIL_ sembra non essere valido", [
                        '_EMAIL_' => $check_email->email,
                    ]));
                }
            }
        } else {
            flash()->error(tr("Attenzione: l'indirizzo email _EMAIL_ sembra non essere valido", [
                '_EMAIL_' => $check_email->email,
            ]));

            if (is_object($check_email) && !empty($check_email->error->info)) {
                flash()->error($check_email->error->info);
            }
        }

        if (isAjaxRequest()) {
            echo json_encode(['id' => $id_record]);
        }

        break;

    case 'test':
        $mail = new EmailNotification($id_record);

        echo json_encode([
            'test' => $mail->testSMTP(),
        ]);

        break;

    case 'delete':
        $dbo->query('UPDATE em_accounts SET deleted_at = NOW() WHERE id='.prepare($id_record));

        flash()->info(tr('Account email eliminato!'));

        break;
}
