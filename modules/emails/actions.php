<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'add':
        $dbo->insert('em_templates', [
            'name' => post('name'),
            'id_module' => post('module'),
            'id_account' => post('smtp'),
            'subject' => post('subject'),
        ]);

        $id_record = $dbo->lastInsertedID();

        flash()->info(tr('Aggiunto nuovo template per le email!'));

        break;

    case 'update':
        $dbo->update('em_templates', [
            'name' => post('name'),
            'id_account' => post('smtp'),
            'icon' => post('icon'),
            'subject' => post('subject'),
            'reply_to' => post('reply_to'),
            'cc' => post('cc'),
            'bcc' => post('bcc'),
            'body' => $_POST['body'], // post('body'),
            'read_notify' => post('read_notify'),
        ], ['id' => $id_record]);

        $dbo->sync('em_print_template', ['id_template' => $id_record], ['id_print' => (array) post('prints')]);

        flash()->info(tr('Informazioni salvate correttamente!'));

        break;

    case 'delete':
        $dbo->query('UPDATE em_templates SET deleted_at = NOW() WHERE id='.prepare($id_record));

        flash()->info(tr('Template delle email eliminato!'));

        break;

    case 'copy':
        $dbo->query('CREATE TEMPORARY TABLE tmp SELECT * FROM em_templates WHERE id= '.prepare($id_record));
        $dbo->query('ALTER TABLE tmp DROP id');
        $dbo->query('INSERT INTO em_templates SELECT NULL,tmp. * FROM tmp');
        $id_record = $dbo->lastInsertedID();
        $dbo->query('DROP TEMPORARY TABLE tmp');

        $dbo->query('UPDATE em_templates SET name = CONCAT (name, " (copia)") WHERE id = '.prepare($id_record));

        flash()->info(tr('Template duplicato correttamente!'));

        break;
}
