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

?><form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">


   
    <div class="row">
        <div class="col-md-6">
            {[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": "$descrizione$" ]}
        </div>

        <div class="col-md-3">
            {[ "type": "select", "label": "<?php echo tr('Direzione'); ?>", "name": "dir", "value": "$dir$", "values": "list=\"\": \"Non specificato\", \"entrata\": \"<?php echo tr('Entrata'); ?>\", \"uscita\": \"<?php echo tr('Uscita'); ?>\"", "required": 1 ]}
        </div>

        <div class="col-md-3">
            {[ "type": "select", "label": "<?php echo tr('Codice tipo documento FE'); ?>", "name": "codice_tipo_documento_fe", "value": "$codice_tipo_documento_fe$", "values": "query=SELECT codice AS id, CONCAT_WS(' - ', codice, descrizione) AS descrizione FROM fe_tipi_documento", "required": 1 ]}
        </div>

        <div class="col-md-4">
            {[ "type": "checkbox", "label": "<?php echo tr('Tipo documento predefinito'); ?>", "name": "predefined", "value": "<?php echo intval($record['predefined']); ?>", "help":"<?php echo tr('Impostare questo tipo di documento predefinto per le fatture'); ?>." ]}
        </div>

        <div class="col-md-4">
            {[ "type": "checkbox", "label": "<?php echo tr('Attivo'); ?>", "name": "enabled", "value": "<?php echo intval($record['enabled']); ?>" ]}
        </div>

        <div class="col-md-4">
            {[ "type": "checkbox", "label": "<?php echo tr('Reversed'); ?>", "name": "reversed", "value": "<?php echo intval($record['reversed']); ?>", "readonly": 1 ]}
        </div>

        <div class="col-md-12">
            {[ "type": "text", "label": "<?php echo tr('Help'); ?>", "name": "help", "value": "$help$" ]}
        </div>

    </div>
   

</form>

<?php
// Collegamenti diretti (numerici)
$numero_documenti = $dbo->fetchNum('SELECT id FROM co_documenti WHERE idtipodocumento='.prepare($id_record));

if (!empty($numero_documenti)) {
    echo '
<div class="alert alert-danger">
    '.tr('Ci sono _NUM_ documenti collegati', [
        '_NUM_' => $numero_documenti,
    ]).'.
</div>';
}
?>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>
