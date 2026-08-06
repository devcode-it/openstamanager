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

?><form action="" method="post" id="add-form">
    <input type="hidden" name="op" value="add">
    <input type="hidden" name="backto" value="record-edit">

    <div class="row">
        <div class="col-md-3">
            {[ "type": "text", "label": "<?php echo tr('Numero'); ?>", "name": "numero", "required": 1, "value": "<?php echo date('Y').'/'.str_pad((string) (($dbo->fetchOne('SELECT MAX(`id`) AS max_id FROM `ns_note_spese`')['max_id'] ?? 0) + 1), 4, '0', STR_PAD_LEFT); ?>" ]}
        </div>

        <div class="col-md-3">
            {[ "type": "date", "label": "<?php echo tr('Data'); ?>", "name": "data", "required": 1, "value": "<?php echo date('Y-m-d'); ?>" ]}
        </div>

        <div class="col-md-6">
            {[ "type": "select", "label": "<?php echo tr('Persona'); ?>", "name": "id_anagrafica", "required": 1, "values": "query=SELECT `an_anagrafiche`.`id`, `ragione_sociale` AS descrizione FROM `an_anagrafiche` WHERE `an_anagrafiche`.`deleted_at` IS NULL ORDER BY `ragione_sociale`" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            {[ "type": "text", "label": "<?php echo tr('Oggetto'); ?>", "name": "oggetto", "required": 1, "placeholder": "<?php echo tr('Esempio: Trasferta cliente, rimborso carburante, pedaggi...'); ?>" ]}
        </div>
    </div>

    <div class="modal-footer">
        <div class="col-md-12 text-right">
            <button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?></button>
        </div>
    </div>
</form>
