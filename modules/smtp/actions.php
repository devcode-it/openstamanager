<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'add':
        $dbo->insert('zz_smtp', [
            'name' => $post['name'],
            'from_name' => $post['from_name'],
            'from_address' => $post['from_address'],
        ]);

        $id_record = $dbo->last_inserted_id();

        $_SESSION['infos'][] = tr('Nuovo account email aggiunto!');

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

        $_SESSION['infos'][] = tr('Informazioni salvate correttamente!');
        
        //validazione indirizzo email mittente.
        $check_email = Validate::isValidEmail($post['from_address'],1,1);
        //print_r($check_email);
        //exit();
        //se $check_email non è null, l'indirizzo email è settato e la riposta è negativa --> mostro il messaggio di avviso.
        if ((!is_null($check_email)) and (!$check_email->smtp_check) and (!empty($post['from_address']))){
            $_SESSION['errors'][] = tr('Attenzione questo indirizzo non sembra essere valido: ').$post['from_address'];
        }


        break;

    case 'delete':
        $dbo->query('UPDATE zz_smtp SET deleted = 1 WHERE id='.prepare($id_record));

        $_SESSION['infos'][] = tr('Account email eliminato!');

        break;
}
