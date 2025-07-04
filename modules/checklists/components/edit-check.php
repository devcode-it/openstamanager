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
use Modules\Checklists\ChecklistItem;
use Models\Upload;
use Models\Module;

$id_record = get('id_record');
$main_check = get('main_check');

if ($main_check) {
    $record = ChecklistItem::find($id_record);
} else {
    $record = Check::find($id_record);
}

?>
<form action="" method="post" id="edit_check-form" enctype="multipart/form-data">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="edit_check">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">
	<input type="hidden" name="main_check" value="<?php echo $main_check; ?>">

    <div class="row">
        <div class="col-md-12">
        <?php
            echo input([
                'type' => 'ckeditor',
                'label' => tr('Descrizione'),
                'name' => 'content',
                'required' => 1,
                'value' => htmlentities((string) $record->content),
            ]);
        ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            {[ "type": "checkbox", "label": "<?php echo tr('Utilizza come titolo'); ?>", "name": "is_titolo", "value": "<?php echo $record->is_titolo; ?>" ]}
        </div>
        <div class="col-md-4">
            {[ "type": "image", "label": "<?php echo tr('Immagine');?>", "name": "immagine", "class": "img-thumbnail", "value": "<?php echo $record->image;?>", "accept": "image/x-png,image/gif,image/jpeg" ]}
        </div>
    </div>
    <div class="row">
        <div class="col-md-12 text-right">
            <button type="button" class="btn btn-success" id="save-btn"><i class='fa fa-check'></i> <?php echo tr('Salva'); ?></button>
        </div>
    </div>
</form>

<script>
    init();
    $('#save-btn').click(function() {
        $('#save-btn').attr('disabled', true);
        $('#save-btn').html('<i class="fa fa-spinner fa-spin"></i> <?php echo tr('Salvataggio in corso...'); ?>');

        // Creare FormData per gestire i file
        var formData = new FormData(document.getElementById('edit_check-form'));

        $.ajax({
            url: '<?php echo $rootdir; ?>/modules/checklists/ajax.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                location.reload();
            },
            error: function() {
                $('#save-btn').attr('disabled', false);
                $('#save-btn').html('<i class="fa fa-check"></i> <?php echo tr('Salva'); ?>');
                alert('<?php echo tr('Errore durante il salvataggio'); ?>');
            }
        });
    });
</script>