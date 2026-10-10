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

$id_segment = getSegmentPredefined($id_module);

if (empty($id_segment)) {
    echo '<div class="alert alert-warning">'.tr('Non è configurato un sezionale predefinito per Contratti fornitori. Configurarlo dal modulo Segmenti prima di creare il contratto.').'</div>';

    return;
}

echo '
<form action="" method="post" id="add-form">
    <input type="hidden" name="op" value="add">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="id_record" value="0">
    <input type="hidden" name="id_segment" value="'.$id_segment.'">

    <div class="row">
        <div class="col-md-6">
            {[ "type": "text", "label": "'.tr('Descrizione contratto').'", "name": "nome", "required": 1 ]}
        </div>

        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Fornitore').'", "name": "id_fornitore", "required": 1, "ajax-source": "fornitori" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Categoria').'", "name": "id_categoria", "values": "query=SELECT `id`, `nome` AS descrizione FROM `ac_categorie_contratti_fornitori` WHERE `enabled` = 1 ORDER BY `nome`" ]}
        </div>

        <div class="col-md-4">
            {[ "type": "date", "label": "'.tr('Data inizio').'", "name": "data_inizio", "value": "'.date('Y-m-d').'" ]}
        </div>

        <div class="col-md-4">
            {[ "type": "number", "label": "'.tr('Validità contratto').'", "name": "validita", "decimals": "0", "min-value": "0", "icon-after": "choice|period|manual", "help": "'.tr('Il campo Validità contratto viene utilizzato per il calcolo della Data di scadenza del contratto.').'" ]}
        </div>
    </div>

    <div class="modal-footer">
        <button type="submit" class="btn btn-primary">
            <i class="fa fa-plus"></i> '.tr('Aggiungi').'
        </button>
    </div>
</form>';
