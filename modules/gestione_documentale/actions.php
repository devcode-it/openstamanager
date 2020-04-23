<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'add':
        $dbo->insert('do_documenti', [
            'idcategoria' => post('idcategoria'),
            'nome' => post('nome'),
            'data' => post('data'),
        ]);
        $id_record = $dbo->lastInsertedID();

        flash()->info(tr('Nuova documento aggiunto!'));

        break;

    case 'update':
        $dbo->update('do_documenti', [
            'idcategoria' => post('idcategoria'),
            'nome' => post('nome'),
            'data' => post('data'),
        ], ['id' => $id_record]);

        flash()->info(tr('Informazioni salvate correttamente!'));
    break;

    case 'delete':
        $dbo->query('DELETE FROM do_documenti WHERE id = '.prepare($id_record));

        Uploads::deleteLinked([
            'id_module' => $id_module,
            'id_record' => $id_record,
        ]);

        flash()->info(tr('Scheda e relativi files eliminati!'));

        break;
}
