<?php

include_once __DIR__.'/../../core.php';

use Modules\Listini\Listino;

switch (post('op')) {
    case 'update':
        $listino->nome = post('nome');
        $listino->note = post('note');

        $listino->percentuale = post('prc_guadagno');
        $listino->percentuale_combinato = post('prc_combinato');

        $listino->save();

        flash()->info(tr('Informazioni salvate correttamente!'));
        break;

    case 'add':
        $listino = Listino::build(post('nome'), post('prc_guadagno'));

        $listino->percentuale_combinato = post('prc_combinato');

        $listino->save();
        $id_record = $listino->id;

        flash()->info(tr('Nuovo listino aggiunto!'));
        break;

    case 'delete':
        $listino->delete();

        flash()->info(tr('Listino eliminato!'));
        break;
}
