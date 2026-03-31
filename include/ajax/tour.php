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

header('Content-Type: application/json; charset=UTF-8');

$tour_file = base_dir().'/include/tour.php';
if (! file_exists($tour_file)) {
    echo json_encode(['success' => false, 'completed' => false, 'message' => 'File di gestione tour non trovato']);

    return;
}

require_once $tour_file;

if (! function_exists('saveTourCompleted') || ! function_exists('isTourCompleted')) {
    echo json_encode(['success' => false, 'completed' => false, 'message' => 'Funzioni di gestione tour non disponibili']);

    return;
}

$op = get('op');

switch ($op) {
    case 'save_tour_completed':
        $id_module = get('id_module');

        if (empty($id_module)) {
            echo json_encode(['success' => false, 'message' => 'ID modulo non specificato']);
            break;
        }

        $result = saveTourCompleted($id_module);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Tour salvato come completato']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Errore durante il salvataggio del tour']);
        }
        break;

    case 'is_tour_completed':
        $id_module = get('id_module');

        if (empty($id_module)) {
            echo json_encode(['completed' => false]);
            break;
        }

        $completed = isTourCompleted($id_module);
        echo json_encode(['completed' => $completed]);
        break;
}
