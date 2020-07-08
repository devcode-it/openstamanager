<?php

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
            'timeout' => post('timeout'),
            'ssl_no_verify' => post('ssl_no_verify'),
            'predefined' => $predefined,
        ], ['id' => $id_record]);

        flash()->info(tr('Informazioni salvate correttamente!'));

        // Validazione indirizzo email mittente
        $check_email = Validate::isValidEmail(post('from_address'));

        // Controllo sulla validazione
        if (!empty($check_email['valid-format'])) {
            flash()->info(tr('Sintassi email verificata'));
        } else {
            flash()->error(tr("Attenzione: l'indirizzo email _EMAIL_ sembra non essere valido", [
                '_EMAIL_' => post('from_address'),
            ]));
        }

        // Controllo sulla verifica
        if (!empty($check_email['smtp-check'])) {
            flash()->info(tr('SMTP email verificato'));
        } else {
            flash()->warning(tr('SMTP email non verificato'));
        }

        if (isAjaxRequest()) {
            echo json_encode(['id' => $id_record]);
        }

        break;

    case 'test':
        $result = $account->testConnection();

        echo json_encode([
            'test' => $result,
        ]);

        break;

    case 'delete':
        $account->delete();

        flash()->info(tr('Account email eliminato!'));

        break;
}
