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

use Plugins\ReceiptFE\Interaction;
use Plugins\ReceiptFE\Ricevuta;

switch (filter('op')) {
    case 'import':
        $list = Interaction::getReceiptList();

        $results = [];
        foreach ($list as $element) {
            $name = $element['name'];
            $fattura = Ricevuta::process($name);

            $numero_esterno = $fattura ? $fattura->numero_esterno : null;
            $results[] = [
                'file' => $name,
                'fattura' => $numero_esterno,
            ];
        }

        echo json_encode($results);

        break;

    case 'save':
        $content = file_get_contents($_FILES['blob']['tmp_name']);
        $file = Ricevuta::store($_FILES['blob']['name'], $content);

        $name = $file;

        // no break
    case 'prepare':
        $name = $name ?: get('name');
        $fattura = Ricevuta::process($name);

        $numero_esterno = $fattura ? $fattura->numero_esterno : null;

        echo json_encode([
            'file' => $name,
            'fattura' => $fattura,
        ]);

        break;

    case 'list':
        include __DIR__.'/rows.php';

        break;

    case 'delete':
        $file_id = get('file_id');

        $directory = Ricevuta::getImportDirectory();
        $files = Interaction::getFileList();
        $file = $files[$file_id];

        if (!empty($file)) {
            delete($directory.'/'.$file['name']);
        }

        break;

    case 'process':
        $name = get('name');

        // Processo il file ricevuto
        if (Interaction::isEnabled()) {
            $process_result = Interaction::processReceipt($name);
            if (!empty($process_result)) {
                flash()->error($process_result);
            }
        }

        break;
}
