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
?>

<div class="modal-body">
    <form id="form-scelte-gdpr">
        <div class="form-group">
            <h4><?php echo tr('Scelte GDPR'); ?></h4>
            <p class="text-muted"><?php echo tr('Seleziona le tue preferenze per il trattamento dei dati personali'); ?></p>
        </div>

        <!-- MARKETING GENERICO -->
        <div class="form-group">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h5 class="panel-title">
                        <strong><?php echo tr('MARKETING GENERICO'); ?></strong>
                        <span class="label label-warning"><?php echo tr('Facoltativo'); ?></span>
                    </h5>
                </div>
                <div class="panel-body">
                    <p><?php echo tr('Acconsento all\'invio di comunicazioni promozionali e newsletter via Email/SMS/WhatsApp.'); ?></p>
                    <div class="btn-group btn-group-sm" role="group">
                        <label class="btn btn-default active">
                            <input type="radio" name="marketing_generico" value="1" checked> <?php echo tr('Acconsento'); ?>
                        </label>
                        <label class="btn btn-default">
                            <input type="radio" name="marketing_generico" value="0"> <?php echo tr('Non acconsento'); ?>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- PROFILAZIONE -->
        <div class="form-group">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h5 class="panel-title">
                        <strong><?php echo tr('PROFILAZIONE'); ?></strong>
                        <span class="label label-warning"><?php echo tr('Facoltativo'); ?></span>
                    </h5>
                </div>
                <div class="panel-body">
                    <p><?php echo tr('Acconsento all\'analisi dei miei acquisti per ricevere offerte dedicate e personalizzate.'); ?></p>
                    <div class="btn-group btn-group-sm" role="group">
                        <label class="btn btn-default active">
                            <input type="radio" name="profilazione" value="1" checked> <?php echo tr('Acconsento'); ?>
                        </label>
                        <label class="btn btn-default">
                            <input type="radio" name="profilazione" value="0"> <?php echo tr('Non acconsento'); ?>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo tr('Annulla'); ?></button>
    <button type="button" class="btn btn-primary" id="btn-continua-firma">
        <i class="fa fa-arrow-right"></i> <?php echo tr('Continua'); ?>
    </button>
</div>

<script>
$(document).ready(function() {
    $('#btn-continua-firma').on('click', function() {
        // Raccogliere i dati dalle scelte GDPR
        var marketing_generico = $('input[name="marketing_generico"]:checked').val();
        var profilazione = $('input[name="profilazione"]:checked').val();
        // Chiudere la modal attuale
        $('#modals > div').modal('hide');

        // Aprire la modal di firma con i dati GDPR usando openModal
        var href = '<?php echo $module->fileurl('modals/firma_gdpr.php'); ?>?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>&anteprima=1&marketing_generico=' + marketing_generico + '&profilazione=' + profilazione;

        openModal('<?php echo tr('Firma GDPR'); ?>', href);
    });
});
</script>

