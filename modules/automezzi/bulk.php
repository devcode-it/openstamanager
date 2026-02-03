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

use Util\Zip;

switch (post('op')) {
    case 'export_bulk':
        $dir = base_dir().'/files/export_viaggi/';
        directory($dir.'tmp/');

        // Rimozione dei contenuti precedenti
        $files = glob($dir.'/*.zip');
        foreach ($files as $file) {
            delete($file);
        }

        // Selezione dei viaggi da stampare
        $viaggi = $dbo->fetchArray('SELECT id FROM an_sedi WHERE id IN('.implode(',', array_map(prepare(...), $id_records)).')');
        $_SESSION[$id_module]['data_inizio'] = post('data_inizio');
        $_SESSION[$id_module]['data_fine'] = post('data_fine');

        if (!empty($viaggi)) {
            foreach ($viaggi as $r) {
                $print = Prints::getModulePredefinedPrint($id_module);
                Prints::render($print['id'], $r['id'], $dir.'tmp/', false, false);
            }

            $dir = slashes($dir);
            $file = slashes($dir.'viaggi_'.time().'.zip');

            // Creazione zip
            if (extension_loaded('zip')) {
                Zip::create($dir.'tmp/', $file);

                // Invio al browser dello zip
                download($file);

                // Rimozione dei contenuti
                delete($dir.'tmp/');
            }
        }

        break;
}

$operations['export_bulk'] = [
    'text' => '<span><i class="fa fa-file-archive-o"></i> '.tr('Esporta viaggi'),
    'data' => [
        'title' => tr('Vuoi davvero esportare queste stampe viaggio in un archivio ZIP?'),
        'msg' => '{[ "type": "date", "label": "'.tr('Data inizio').'", "name": "data_inizio", "required": 1, "value": "-now-" ]}
        {[ "type": "date", "label": "'.tr('Data fine').'", "name": "data_fine", "required": 1, "value": "-now-" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => true,
    ],
];

return $operations;
