<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'add':
        $dbo->insert('zz_emails', [
            'name' => post('name'),
            'id_module' => post('module'),
            'id_smtp' => post('smtp'),
            'subject' => post('subject'),
        ]);

        $id_record = $dbo->lastInsertedID();

        flash()->info(tr('Aggiunto nuovo template per le email!'));

        break;

    case 'update':
        $dbo->update('zz_emails', [
            'name' => post('name'),
            'id_smtp' => post('smtp'),
            'icon' => post('icon'),
            'subject' => post('subject'),
            'reply_to' => post('reply_to'),
            'cc' => post('cc'),
            'bcc' => post('bcc'),
            'body' => $_POST['body'], // post('body'),
            'read_notify' => post('read_notify'),
        ], ['id' => $id_record]);

        $dbo->sync('zz_email_print', ['id_email' => $id_record], ['id_print' => (array) post('prints')]);

        flash()->info(tr('Informazioni salvate correttamente!'));

        break;

    case 'delete':
        $dbo->query('UPDATE zz_emails SET deleted_at = NOW() WHERE id='.prepare($id_record));

        flash()->info(tr('Template delle email eliminato!'));

        break;
}
