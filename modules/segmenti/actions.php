<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'update':
        $pattern = str_contains(post('pattern'), '#') ? post('pattern') : '####';
        $predefined = post('predefined');

        if (empty(Modules::getSegments($id_module))) {
            $predefined = 1;
        }

        if ($predefined) {
            $dbo->query('UPDATE zz_segments SET predefined = 0 WHERE id_module = '.prepare($id_module));
        }

        $dbo->update('zz_segments', [
            'id_module' => post('module'),
            'name' => post('name'),
            'clause' => post('clause'),
            'pattern' => $pattern,
            'note' => post('note'),
            'position' => post('position'),
            'predefined' => $predefined,
        ], ['id' => $id_record]);

        flash()->info(tr('Modifiche salvate correttamente'));

        break;

    case 'add':
        $pattern = str_contains(post('pattern'), '#') ? post('pattern') : '####';
        $predefined = post('predefined');

        $module = post('module');

        if (empty(Modules::getSegments($module))) {
            $predefined = 1;
        }

        if ($predefined) {
            $dbo->query('UPDATE zz_segments SET predefined = 0 WHERE id_module = '.prepare($id_module));
        }

        $dbo->insert('zz_segments', [
            'id_module' => post('module'),
            'name' => post('name'),
            'clause' => '1=1',
            'pattern' => $pattern,
            'note' => post('note'),
            'predefined' => $predefined,
        ]);

        $id_record = $dbo->lastInsertedID();

        flash()->info(tr('Nuovo segmento aggiunto'));

        break;

    case 'delete':
        $dbo->query('DELETE FROM zz_segments WHERE id='.prepare($id_record));

        // TODO
        // eliminare riferimento sulle fatture eventuali collegate a questo segmento?

        flash()->info(tr('Segmento eliminato'));

        break;
}
