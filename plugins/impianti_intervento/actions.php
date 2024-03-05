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

use Modules\Checklists\Check;

$operazione = filter('op');

switch ($operazione) {
    case 'add_impianto':
        if (post('id_impianto')) {
            $dbo->query('INSERT INTO my_impianti_interventi(idimpianto, idintervento) VALUES('.prepare(post('id_impianto')).', '.prepare($id_record).')');

            $checks_impianti = $dbo->fetchArray('SELECT * FROM zz_checks WHERE id_module = '.prepare(Modules::get('Impianti')['id']).' AND id_record = '.prepare(post('id_impianto')));
            foreach ($checks_impianti as $check_impianto) {
                $id_parent_new = null;
                if ($check_impianto['id_parent']) {
                    $parent = $dbo->selectOne('zz_checks', '*', ['id' => $check_impianto['id_parent']]);
                    $id_parent_new = $dbo->selectOne('zz_checks', '*', ['content' => $parent['content'], 'id_module' => $id_module, 'id_record' => $id_record])['id'];
                }
                $check = Check::build($user, $structure, $id_record, $check_impianto['content'], $id_parent_new, $check_impianto['is_titolo'], $check_impianto['order'], Modules::get('Impianti')['id'], post('id_impianto'));
                $check->id_module = $id_module;
                $check->id_plugin = $id_plugin;
                $check->note = $check_impianto['note'];
                $check->save();
            }

            flash()->info(tr('Impianto aggiunto correttamente!'));
        } else {
            flash()->warning(tr('Selezionare un impianto!'));
        }

        break;

    case 'update_impianto':
        $components = (array) post('componenti');
        $note = post('note');
        $id_impianto = post('id_impianto');
    
        $dbo->query('DELETE FROM my_componenti_interventi WHERE id_componente IN (SELECT id FROM my_componenti WHERE id_impianto = '.prepare($id_impianto).') AND id_intervento = '.prepare($id_record));
    
        foreach ($components as $component) {
            $dbo->query('INSERT INTO my_componenti_interventi(id_componente, id_intervento) VALUES ('.prepare($component).', '.prepare($id_record).')');
        }

        $dbo->update('my_impianti_interventi', [
            'note' => $note
        ], [
            'idintervento' => $id_record,
            'idimpianto' => $id_impianto
        ]);

        flash()->info(tr('Impianto modificato correttamente!'));

        break;

    case 'delete_impianto':
        $dbo->query('DELETE FROM my_impianti_interventi WHERE idintervento='.prepare($id_record).' AND idimpianto = '.prepare(post('id')));
        Check::deleteLinked([
            'id_module' => $id_module,
            'id_record' => $id_record,
            'id_module_from' => Modules::get('Impianti')['id'],
            'id_record_from' => post('id'),
        ]);

        $components = $dbo->fetchArray('SELECT * FROM my_componenti WHERE id_impianto = '.prepare($matricola));
        if (!empty($components)) {
            foreach ($components as $component) {
                $dbo->query('DELETE FROM my_componenti_interventi WHERE id_componente = '.prepare($component['id']).' AND id_intervento = '.prepare($id_record));
            }
        }

        flash()->info(tr('Impianto rimosso correttamente!'));

        break;
}
