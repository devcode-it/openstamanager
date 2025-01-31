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

// Inerentemente al tracciato XML prodotto per la FE
// la descrizione del bene o servizio valorizzata all'interno del nodo con ID 2.2.1.4 può avere una lunghezza che varia tra 1 - 1000 caratteri
echo '
    <div class="row">
        <div class="col-md-12">
            {[ "type": "textarea", "label": "'.tr('Descrizione').'", "name": "descrizione", "id": "descrizione_riga", "value": '.json_encode($result['descrizione']).', "required": 1, "extra": "rows=\"4\"", "charcounter": 1 ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            {[ "type": "textarea", "label": "'.tr('Note interne').'", "name": "note", "value": '.json_encode($result['note']).', "help": "'.tr('Queste note saranno utilizzate solo a scopo interno').'", "extra": "rows=\"2\"" ]}
        </div>
    </div>';

if ($module->name == 'Preventivi' && $options['op'] == 'manage_descrizione') {
    echo '
    <div class="row">
        <div class="col-md-6">
            {[ "type": "checkbox", "label": "'.tr('Utilizza come titolo del gruppo').'", "name": "is_titolo", "value": '.json_encode($result['is_titolo']).', "help": "'.tr(' ').'" ]}
        </div>
    </div>';
}
