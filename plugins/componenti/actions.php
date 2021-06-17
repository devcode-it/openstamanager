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

use Carbon\Carbon;

$operazione = filter('op');

switch ($operazione) {
    case 'update':
        $articolo = (array)post('id_articolo');
        $data_installazione = (array)post('data_installazione');
        $data_disinstallazione = (array)post('data_disinstallazione');
        $data_registrazione = (array)post('data_registrazione');
        $note = (array)post('note');

        $key = key($articolo);

        if(post('sostituito')){
            $field_articolo = 'pre_id_articolo';
        } else{
            $field_articolo = 'id_articolo';
        }

        $dbo->update('my_componenti_articoli', [
            $field_articolo => $articolo[$key],
            'data_installazione' => $data_installazione[$key] ?: null,
            'data_disinstallazione' => $data_disinstallazione[$key] ?: null,
            'data_registrazione' => $data_registrazione[$key] ?: null,
            'note' => $note[$key],
        ], ['id' => $key]);
        
        flash()->info(tr('Salvataggio completato!'));
        $dbo->commitTransaction();
        header('Location: '.$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'#tab_'.$id_plugin);
        exit;

        break;

    case 'add':
        $dbo->insert('my_componenti_articoli', [
            'id_impianto' => $id_record,
            'data_registrazione' => Carbon::now(),
            'id_articolo' => post('id_articolo'),
        ]);

        flash()->info(tr('Salvataggio completato!'));

        break;

    case 'sostituisci':
        $old_id = get('id_old');
        $old = $dbo->selectOne('my_componenti_articoli', '*', ['id' => $old_id]);

        if(!empty($old['id_articolo'])){

            if(empty($old['data_disinstallazione'])){
                $data = Carbon::now();
            } else{
                $data = $old['data_disinstallazione'];
            }

            $dbo->update('my_componenti_articoli', [
                'pre_id_articolo' => $old['id_articolo'],
                'id_articolo' => 0,
                'data_disinstallazione' => $data,
            ],[
                'id' => $old_id,
            ]);

            $dbo->query('CREATE TEMPORARY TABLE tmp SELECT * FROM my_componenti_articoli WHERE id= '.prepare($old_id));
            $dbo->query('ALTER TABLE tmp DROP id');
            $dbo->query('INSERT INTO my_componenti_articoli SELECT NULL,tmp. * FROM tmp');
            $new_id = $dbo->lastInsertedID();
            $dbo->query('DROP TEMPORARY TABLE tmp');

            $dbo->update('my_componenti_articoli', [
                'id_articolo' => $old['id_articolo'],
                'pre_id_articolo' => 0,
                'data_registrazione' => Carbon::now(),
                'data_installazione' => $data,
                'data_disinstallazione' => null,
            ], [
                'id' => $new_id,
            ]);

            flash()->info(tr('Informazioni salvate correttamente!'));
        } else{
            flash()->warning(tr('Inserire un articolo prima di effettuare la sostituzione!'));
        }

        $dbo->commitTransaction();
        header('Location: '.$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'#tab_'.$id_plugin);
        exit;

        break;

    case 'delete':
        $dbo->query('DELETE FROM my_componenti_articoli WHERE id='.prepare(get('id')));

        flash()->info(tr('Componente eliminato!'));

        $dbo->commitTransaction();
        header('Location: '.$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'#tab_'.$id_plugin);
        exit;

        break;
}
