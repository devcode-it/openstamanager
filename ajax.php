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

include_once __DIR__.'/core.php';

use Models\Hook;
use Modules\CategorieFiles\Categoria;

switch (filter('op')) {
    // Imposta un valore ad un array di $_SESSION
    // esempio: push di un valore in $_SESSION['dashboard']['idtecnici']
    // iversed: specifica se rimuovere dall'array il valore trovato e applicare quindi una deselezione (valori 0 o 1, default 1)
    case 'session_set_array':
        $array = explode(',', get('session'));
        $value = "'".get('value')."'";
        $inversed = get('inversed');

        $found = false;

        // Ricerca valore nell'array
        foreach ($_SESSION[$array[0]][$array[1]] as $idx => $val) {
            // Se il valore esiste lo tolgo
            if ($val == $value) {
                $found = true;

                if ((int) $inversed == 1) {
                    unset($_SESSION[$array[0]][$array[1]][$idx]);
                }
            }
        }

        if (!$found) {
            array_push($_SESSION[$array[0]][$array[1]], $value);
        }

        // print_r($_SESSION[$array[0]][$array[1]]);

        break;

        // Imposta un valore ad una sessione
    case 'session_set':
        $array = explode(',', get('session'));
        $value = get('value', true);
        $clear = get('clear');

        if ($clear == 1 || $value == '') {
            unset($_SESSION[$array[0]][$array[1]]);
        } else {
            $_SESSION[$array[0]][$array[1]] = $value;
        }

        break;

    case 'list_attachments':
        $category = get('id_category') ? Categoria::find(get('id_category'))->name : null;
        $upload_only = get('upload_only') === 'true' ? 'true' : 'false';
        $disable_edit = get('disable_edit') === 'true' ? 'true' : 'false';
        $key = get('key');
        echo '{( "name": "filelist_and_upload", "id_module": "'.$id_module.'", "id_record": "'.$id_record.'", "id_plugin": "'.$id_plugin.'", "category": "'.$category.'", "upload_only": "'.$upload_only.'", "disable_edit": "'.$disable_edit.'", "key": "'.$key.'" )}';

        break;

    case 'checklists':
        include base_dir().'/plugins/checks.php';

        break;

    case 'active_users':
        $posizione = get('id_module');
        if (!empty($id_record)) {
            $posizione .= ', '.get('id_record');
        }

        $user = auth_osm()->getUser();
        $interval = setting('Timeout notifica di presenza (minuti)') * 60;

        $dbo->query('UPDATE zz_semaphores SET updated = NOW() WHERE id_utente = :user_id AND posizione = :position', [
            ':user_id' => $user['id'],
            ':position' => $posizione,
        ]);

        // Rimozione record scaduti
        $dbo->query('DELETE FROM zz_semaphores WHERE DATE_ADD(updated, INTERVAL :interval SECOND) <= NOW()', [
            ':interval' => $interval,
        ]);

        $datas = $dbo->fetchArray('SELECT DISTINCT username FROM zz_semaphores INNER JOIN zz_users ON zz_semaphores.id_utente=zz_users.id WHERE zz_semaphores.id_utente != :user_id AND posizione = :position', [
            ':user_id' => $user['id'],
            ':position' => $posizione,
        ]);

        echo json_encode($datas);

        break;

    case 'hooks':
        $hooks = Hook::all();

        $results = [];
        foreach ($hooks as $hook) {
            if ($hook->permission != '-') {
                $results[] = [
                    'id' => $hook->id,
                    'name' => $hook->name,
                    'content' => $hook->response(),
                ];
            }
        }

        echo json_encode($results);

        break;

    case 'flash':
        $response = flash()->getMessages();

        echo json_encode($response);

        break;

    case 'summable-results':
        $ids = post('ids') ?: [];
        $results = Util\Query::getSums($structure, [
            'id' => $ids,
        ]);

        echo json_encode($results);

        break;

    case 'avg-results':
        $ids = post('ids') ?: [];
        $results = Util\Query::getAverages($structure, [
            'id' => $ids,
        ]);

        echo json_encode($results);

        break;

    case 'calcola_sconto_combinato':
        $prc_combinato = post('prc_combinato');

        if (!empty($prc_combinato)) {
            $sconto = parseScontoCombinato($prc_combinato);
            echo json_encode([
                'success' => true,
                'sconto' => $sconto,
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => tr('Sconto combinato non valido'),
            ]);
        }

        break;
}
