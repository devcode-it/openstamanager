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

use Models\Module;
use Modules\Checklists\Check;

$operazione = filter('op');
$id_modulo_impianti = Module::where('name', 'Impianti')->first()->id;

switch ($operazione) {
    case 'add_impianto':
        if (post('id_impianto')) {
            $dbo->query('INSERT INTO my_impianti_interventi(idimpianto, idintervento) VALUES('.prepare(post('id_impianto')).', '.prepare($id_record).')');

            $checks_impianti = $dbo->fetchArray('SELECT * FROM zz_checks WHERE id_module = '.prepare($id_modulo_impianti).' AND id_record = '.prepare(post('id_impianto')));
            foreach ($checks_impianti as $check_impianto) {
                $id_parent_new = null;
                if ($check_impianto['id_parent']) {
                    $parent = $dbo->selectOne('zz_checks', '*', ['id' => $check_impianto['id_parent']]);
                    $id_parent_new = $dbo->selectOne('zz_checks', '*', ['content' => $parent['content'], 'id_module' => $id_module, 'id_record' => $id_record])['id'];
                }
                $check = Check::build($user, $structure, $id_record, $check_impianto['content'], $id_parent_new, $check_impianto['is_titolo'], $check_impianto['order'], $id_modulo_impianti, post('id_impianto'));
                $check->id_module = $id_module;
                $check->id_plugin = $id_plugin;
                $check->note = $check_impianto['note'];
                $check->id_immagine = $check_impianto['id_immagine'];
                $check->save();

                // Riporto anche i permessi della check
                $users = [];
                $utenti = $dbo->table('zz_check_user')->where('id_check', $check_impianto['id'])->get();
                foreach ($utenti as $utente) {
                    $users[] = $utente->id_utente;
                }
                $check->setAccess($users, null);
            }

            flash()->info(tr('Impianto aggiunto correttamente!'));
        } else {
            flash()->warning(tr('Selezionare un impianto!'));
        }

        break;

    case 'update_impianto':
        $components = (post('componenti') ? (array) post('componenti') : []);
        $note = post('note');
        $id_impianto = post('id_impianto');

        $dbo->query('DELETE FROM my_componenti_interventi WHERE id_componente IN (SELECT id FROM my_componenti WHERE id_impianto = '.prepare($id_impianto).') AND id_intervento = '.prepare($id_record));

        foreach ($components as $component) {
            $dbo->query('INSERT INTO my_componenti_interventi(id_componente, id_intervento) VALUES ('.prepare($component).', '.prepare($id_record).')');
        }

        $dbo->update('my_impianti_interventi', [
            'note' => $note,
        ], [
            'idintervento' => $id_record,
            'idimpianto' => $id_impianto,
        ]);

        flash()->info(tr('Impianto modificato correttamente!'));

        break;

    case 'delete_impianto':
        try {
            $id_impianto = post('id');

            if (empty($id_impianto)) {
                throw new Exception(tr('ID impianto non specificato'));
            }

            // Verifica che l'impianto esista prima di rimuoverlo
            $exists = $dbo->fetchOne('SELECT COUNT(*) as count FROM my_impianti_interventi WHERE idintervento='.prepare($id_record).' AND idimpianto = '.prepare($id_impianto));

            if ($exists['count'] == 0) {
                throw new Exception(tr('Impianto non trovato nell\'intervento'));
            }

            $result = $dbo->query('DELETE FROM my_impianti_interventi WHERE idintervento='.prepare($id_record).' AND idimpianto = '.prepare($id_impianto));

            // Verifica che l'eliminazione sia avvenuta
            $remaining = $dbo->fetchOne('SELECT COUNT(*) as count FROM my_impianti_interventi WHERE idintervento='.prepare($id_record).' AND idimpianto = '.prepare($id_impianto));
            if ($remaining['count'] > 0) {
                throw new Exception(tr('Errore durante l\'eliminazione dell\'impianto dal database'));
            }
            Check::deleteLinked([
                'id_module' => $id_module,
                'id_record' => $id_record,
                'id_module_from' => $id_modulo_impianti,
                'id_record_from' => $id_impianto,
            ]);

            $components = $dbo->fetchArray('SELECT * FROM my_componenti WHERE id_impianto = '.prepare($id_impianto));
            if (!empty($components)) {
                foreach ($components as $component) {
                    $dbo->query('DELETE FROM my_componenti_interventi WHERE id_componente = '.prepare($component['id']).' AND id_intervento = '.prepare($id_record));
                }
            }

            flash()->info(tr('Impianto rimosso correttamente!'));

            // Risposta JSON per il client
            $response = ['status' => 'success', 'message' => tr('Impianto rimosso correttamente!')];
        } catch (Exception $e) {
            flash()->error(tr('Errore durante la rimozione dell\'impianto: _MSG_', [
                '_MSG_' => $e->getMessage(),
            ]));

            // Risposta JSON di errore per il client
            $response = ['status' => 'error', 'message' => $e->getMessage()];
        }

        // Invia la risposta JSON se è stata impostata
        if (isset($response)) {
            header('Content-Type: application/json');
            echo json_encode($response);
        }

    case 'load_checklist':
        $checks = Check::where('id_module_from', $id_modulo_impianti)->where('id_record_from', post('id_impianto'))->where('id_module', $id_module)->where('id_record', $id_record)->where('id_parent', null)->get();

        $response = '';
        $has_images = $checks->where('id_immagine', '!=', null)->count();
        foreach ($checks as $check) {
            $response .= renderChecklist($check, 1, 0, $has_images);
        }

        /*echo json_encode([
            'checklist' => $response
        ]);*/

        echo $response;

        break;

    case 'check_impianto':
        try {
            $checked = (post('checked') ? 1 : 0);
            $idcheck = post('id');

            $dbo->update('zz_checks', [
                'checked' => $checked,
            ], [
                'id' => $idcheck,
            ]);
            $response = ['status' => 'success', 'message' => tr('Checklist aggiornata correttamente!')];
        } catch (Exception $e) {
            $response = ['status' => 'error', 'message' => $e->getMessage()];
        }

        echo $response;

        break;
}
