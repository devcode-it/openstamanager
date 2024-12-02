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

$creation = file_put_contents('manifest.json', '');

if (!$creation) {
    $manifest = '{

        "dir" : "ltr",
        "lang" : "'.((empty($lang) || $lang == '|lang|') ? 'it-IT' : str_replace('_', '-', $lang)).'",
        "name" : "'.tr('OpenSTAManager').'",
        "scope" : "'.((empty(base_path()) || base_path() == '/') ? '' : '.').'",
        "display" : "fullscreen",
        "start_url" : "'.((empty(base_path()) || base_path() == '/') ? '/' : './').'",
        "short_name" : "OSM",
        "theme_color" : "transparent",
        "description" : "'.tr('OpenSTAManager').'",
        "orientation" : "any",
        "background_color" : "transparent",
        "generated" : "true",
        "icons" : [
            {
                "src": "'.App::getPaths()['img'].'/logo_completo.png",
                "type": "image/png",
                "sizes": "489x91"
            }
        ]
}';

    file_put_contents('manifest.json', $manifest);
} else {
    echo '
    <div class="card card-center card-danger card-solid text-center">
			<div class="card-header with-border">
				<h3 class="card-title">'.tr('Permessi di scrittura mancanti').'</h3>
			</div>
			<div class="card-body">
                <p>'.tr('Sembra che non ci siano i permessi di scrittura sul file _FILE_', [
        '_FILE_' => '<b>manifest.json</b>',
    ]).'</p>
            </div>
    </div>';
}
