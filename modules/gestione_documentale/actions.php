<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'add':
        $dbo->insert('zz_documenti', [
            'idcategoria' => post('nome'),
            'nome' => post('idcategoria'),
            'data' => post('data'),
        ]);
        $id_record = $dbo->last_inserted_id();

        flash()->info(tr('Nuova documento aggiunto!'));

        break;

    case 'update':
        $dbo->update('zz_documenti', [
            'idcategoria' => post('nome'),
            'nome' => post('idcategoria'),
            'data' => post('data'),
        ], ['id' => $id_record]);

        flash()->info(tr('Informazioni salvate correttamente!'));
    break;

    case 'delete':
        $dbo->query('DELETE FROM zz_documenti WHERE id = '.prepare($id_record));

        Uploads::deleteLinked([
            'id_module' => $id_module,
            'id_record' => $id_record,
        ]);

        flash()->info(tr('Scheda e relativi files eliminati!'));

        break;
}
