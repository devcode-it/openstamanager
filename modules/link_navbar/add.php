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
?>
<form action="" method="post" id="add-form">
    <input type="hidden" name="op" value="add">
    <input type="hidden" name="backto" value="record-edit">

    <div class="row">
        <div class="col-md-6">
            {[ "type": "text", "label": "<?php echo tr('Nome interno (slug univoco)'); ?>", "name": "name", "required": 1, "help": "<?php echo tr('Identificatore macchina, lowercase con trattini'); ?>" ]}
        </div>
        <div class="col-md-6">
            {[ "type": "text", "label": "<?php echo tr('Etichetta visibile'); ?>", "name": "label", "required": 1, "help": "<?php echo tr('Testo visibile nei dropdown'); ?>" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            {[ "type": "select", "label": "<?php echo tr('Tipo'); ?>", "name": "type", "required": 1, "values": "list=\"link\": \"<?php echo tr('Link URL'); ?>\", \"javascript\": \"<?php echo tr('JavaScript'); ?>\", \"module\": \"<?php echo tr('Modulo'); ?>\", \"plugin\": \"<?php echo tr('Plugin'); ?>\"" ]}
        </div>
        <div class="col-md-8">
            {[ "type": "text", "label": "<?php echo tr('Valore'); ?>", "name": "value", "required": 1, "help": "<?php echo tr('URL per Link, nome funzione globale per JavaScript, nome modulo/plugin'); ?>" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            {[ "type": "text", "label": "<?php echo tr('Icona'); ?>", "name": "icon", "value": "fa fa-link", "help": "<?php echo tr('Classe FontAwesome'); ?>" ]}
        </div>
        <div class="col-md-4">
            {[ "type": "select", "label": "<?php echo tr('Colore'); ?>", "name": "color", "values": "list=\"\": \"<?php echo tr('Nessuno'); ?>\", \"text-primary\": \"<?php echo tr('Primary'); ?>\", \"text-success\": \"<?php echo tr('Success'); ?>\", \"text-warning\": \"<?php echo tr('Warning'); ?>\", \"text-danger\": \"<?php echo tr('Danger'); ?>\", \"text-info\": \"<?php echo tr('Info'); ?>\", \"text-muted\": \"<?php echo tr('Muted'); ?>\"" ]}
        </div>
        <div class="col-md-4">
            {[ "type": "number", "label": "<?php echo tr('Ordine'); ?>", "name": "order", "value": "0" ]}
        </div>
    </div>

    <div class="modal-footer">
        <div class="col-md-12 text-right">
            <button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?></button>
        </div>
    </div>
</form>
