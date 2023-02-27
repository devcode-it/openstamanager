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

include_once __DIR__.'/../../../core.php';
use Modules\Checklists\Check;

$id_record = get("id_record");
$record = Check::find($id_record);

?>

<div class="row">
    <div class="col-md-6">
        {[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "content", "required": 1, "value": "<?=$record->content?>" ]}
    </div>
</div>

<div class="row">
    <div class="col-md-12 text-right">
        <button type="button" class="btn btn-success" id="save-btn"><i class='fa fa-save'></i> <?php echo tr('Salva'); ?></button>
    </div>
</div>

<script>
    $('#save-btn').click(function() {
        $('#save-btn').attr('disabled', true);
        $('#save-btn').html('<i class="fa fa-spinner fa-spin"></i> <?php echo tr('Salvataggio in corso...'); ?>');

        $.post('<?php echo $rootdir; ?>/modules/checklists/ajax.php', {
            op: "edit_check",
            id_record: "<?=$id_record?>",
            content: $('#content').val()
        }, function(){
            location.reload();
        });
    });
</script>