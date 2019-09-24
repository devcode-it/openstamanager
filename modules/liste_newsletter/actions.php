<?php

use Modules\Newsletter\Lista;

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'add':
        $lista = Lista::build(filter('name'));
        $id_record = $lista->id;

        flash()->info(tr('Nuova lista newsletter creata!'));

        break;

    case 'update':
        $lista->name = filter('name');
        $lista->description = filter('description');

        $query = filter('query');
        if (check_query($query)) {
            $lista->query = $query;
        }

        $lista->save();

        flash()->info(tr('Lista newsletter salvata!'));

        break;

    case 'delete':
        $lista->delete();

        flash()->info(tr('Lista newsletter rimossa!'));

        break;

    case 'add_receivers':
        $receivers = post('receivers');

        $lista->anagrafiche()->syncWithoutDetaching($receivers);

        flash()->info(tr('Aggiunti nuovi destinatari alla newsletter!'));

        break;

    case 'remove_receiver':
        $receiver = post('id');

        $lista->anagrafiche()->detach($receiver);

        flash()->info(tr('Destinatario rimosso dalla newsletter!'));

        break;
}
