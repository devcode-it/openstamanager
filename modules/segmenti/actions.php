<?php

switch (post('op')) {
    case 'update':
        $pattern = str_contains(post('pattern'), '#') ? post('pattern') : '####';
        $predefined = post('predefined');
        $module = post('module');

        if (empty(Modules::getSegments($module))) {
            $predefined = 1;
        }

        if ($predefined) {
            $dbo->query('UPDATE zz_segments SET predefined = 0 WHERE id_module = '.prepare($module));
        }

        $predefined_accredito = post('predefined_accredito');
        if ($predefined_accredito) {
            $dbo->query('UPDATE zz_segments SET predefined_accredito = 0 WHERE id_module = '.prepare($module));
        }

        $predefined_addebito = post('predefined_addebito');
        if ($predefined_addebito) {
            $dbo->query('UPDATE zz_segments SET predefined_addebito = 0 WHERE id_module = '.prepare($module));
        }

        $dbo->update('zz_segments', [
            'id_module' => $module,
            'name' => post('name'),
            'clause' => post('clause'),
            'pattern' => $pattern,
            'note' => post('note'),
            'position' => post('position'),
            'predefined' => $predefined,
            'is_fiscale' => post('is_fiscale'),
            'predefined_accredito' => $predefined_accredito,
            'predefined_addebito' => $predefined_addebito,
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
            $dbo->query('UPDATE zz_segments SET predefined = 0 WHERE id_module = '.prepare($module));
        }

        $dbo->insert('zz_segments', [
            'id_module' => $module,
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
