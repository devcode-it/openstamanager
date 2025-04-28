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

<form action="" id="import-form" method="post" enctype="multipart/form-data">
    <div class="row">
        <div class="col-md-12">
            {[ "type": "file", "label": "<?php echo tr('File JSON'); ?>", "name": "file", "required": 1, "accept": ".json" ]}
            <p class="help-block"><?php echo tr('Seleziona un file JSON contenente la struttura del modulo da importare'); ?></p>
        </div>
    </div>

    <!-- PULSANTI -->
    <div class="row">
        <div class="col-md-12 text-right">
            <button type="button" class="btn btn-primary" id="import-button">
                <i class="fa fa-upload"></i> <?php echo tr('Importa'); ?>
            </button>
        </div>
    </div>
</form>

<script>
$(document).ready(function() {
    $('#import-button').on('click', function() {
        var form = $('#import-form')[0];
        var formData = new FormData(form);
        
        // Verifica che sia stato selezionato un file
        var fileInput = $('input[name="file"]')[0];
        if (fileInput.files.length === 0) {
            swal(globals.translations.error, globals.translations.file_required, "error");
            return;
        }
        
        // Aggiungi l'operazione
        formData.append('op', 'import_module');
        
        // Disabilita il pulsante durante l'upload
        $(this).prop('disabled', true);
        
        $.ajax({
            url: globals.rootdir + '/actions.php?id_module=' + globals.id_module + '&id_record=' + globals.id_record,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                try {
                    var data = JSON.parse(response);
                    if (data.success) {
                        swal(globals.translations.import_module, globals.translations.import_success, "success")
                        .then(function() {
                            // Ricarica la pagina per mostrare le modifiche
                            location.reload();
                        });
                    } else {
                        swal(globals.translations.error, data.message || globals.translations.import_error, "error");
                    }
                } catch (e) {
                    swal(globals.translations.error, globals.translations.import_error, "error");
                    console.error(e, response);
                }
            },
            error: function() {
                swal(globals.translations.error, globals.translations.import_error, "error");
            },
            complete: function() {
                // Riabilita il pulsante
                $('#import-button').prop('disabled', false);
            }
        });
    });
});
</script>
