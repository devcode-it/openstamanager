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

$module = module($id_module);

if ($module['name'] == 'Fatture di vendita') {
    $dir = 'entrata';
    $tipo_anagrafica = tr('Cliente');
} else {
    $dir = 'uscita';
    $tipo_anagrafica = tr('Fornitore');
}

$id_anagrafica = !empty(get('idanagrafica')) ? get('idanagrafica') : $user['idanagrafica'];

$idtipodocumento = $dbo->selectOne('co_tipidocumento', ['id'], [
    'predefined' => 1,
    'dir' => $dir,
])['id'];

?>
<form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="dir" value="<?php echo $dir; ?>">

    <!-- Fix creazione da Anagrafica -->
    <input type="hidden" name="id_record" value="">

	<div class="row">

        <?php
            if ($dir == 'uscita') {
                echo '
            <div class="col-md-3">
                {[ "type": "text", "label": "'.tr('N. fattura del fornitore').'", "required": 1, "name": "numero_esterno","class": "text-center", "value": "" ]}
            </div>';
                $size = 3;
            } else {
                $size = 6;
            }
        ?>

		<div class="col-md-<?php echo $size; ?>">
			 {[ "type": "date", "label": "<?php echo tr('Data'); ?>", "name": "data", "required": 1, "value": "-now-" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "select", "label": "<?php echo $tipo_anagrafica; ?>", "name": "idanagrafica", "id": "idanagrafica_add", "required": 1, "ajax-source": "<?php echo $module['name'] == 'Fatture di vendita' ? 'clienti' : 'fornitori'; ?>", "value": "<?php echo $id_anagrafica; ?>", "icon-after": "add|<?php echo module('Anagrafiche')['id']; ?>|tipoanagrafica=<?php echo $tipo_anagrafica; ?>" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-6">
			{[ "type": "select", "label": "<?php echo tr('Tipo documento'); ?>", "name": "idtipodocumento", "required": 1, "values": "query=SELECT id, CONCAT(codice_tipo_documento_fe, ' - ', descrizione) AS descrizione FROM co_tipidocumento WHERE enabled = 1 AND dir = '<?php echo $dir; ?>' ORDER BY codice_tipo_documento_fe", "value": "<?php echo $idtipodocumento; ?>" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "select", "label": "<?php echo tr('Sezionale'); ?>", "name": "id_segment", "required": 1, "values": "query=SELECT id, name AS descrizione FROM zz_segments WHERE id_module='<?php echo $id_module; ?>' ORDER BY name", "value": "<?php echo $_SESSION['module_'.$id_module]['id_segment']; ?>" ]}
		</div>
	</div>
    
    <div id="info" class="hidden">
        <div  class="row">
            <div class="col-md-6 ">
                <div id="info-title-bozza" class="box">
            
                    <div class="box-header with-border">
                        <h3 class="box-title"><?php echo tr('Fatture in stato Bozza del cliente'); ?></h3>
                        <div class="box-tools pull-right">
                            <button type="button" class="btn btn-box-tool" data-widget="collapse">
                                <i class="fa fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="box-body" id="info-content-bozza"></div>
                </div>
            </div>
            <div class="col-md-6">
                <div id="info-title-scadute" class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title"><?php echo tr('Fatture con termini di pagamento trascorsi'); ?></h3>
                        <div class="box-tools pull-right">
                            <button type="button" class="btn btn-box-tool" data-widget="collapse">
                                <i class="fa fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="box-body" id="info-content-scadute"></div>
                </div>
            </div>
        </div>
    </div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?></button>
		</div>
	</div>
</form>

<?php

if ($dir == 'entrata') {
    echo '
<script>
$(document).ready(function () {
    $("#idanagrafica_add").change(function () {
        let data = $(this).selectData();

        if (data !== undefined) {

            $("#info").removeClass("hidden");

            $.ajax({
                url: globals.rootdir + "/actions.php",
                type: "POST",
                dataType: "json",
                data: {
                    id_module: globals.id_module,
                    id_anagrafica: data.id,
                    op: "fatture_bozza",
                },
                success: function (results) {
                    
                    $("#info").removeClass("box-info");
                    $("#info").removeClass("box-warning");
                    if (results.length === 0){
                        $("#info-title-bozza").addClass("box-info");
                        $("#info-title-bozza").removeClass("box-warning");
                        $("#info-content-bozza").html("<p>'.tr('Per il cliente selezionato non è presente alcuna fattura in stato Bozza').'</p>")
                    } else {
                        let content = "";

                        results.forEach(function(item) {
                            content += "<li>" + item + "</li>";
                        });
                        $("#info-title-bozza").addClass("box-warning");
                        $("#info-title-bozza").removeClass("box-info");
                        $("#info-content-bozza").html("<p>'.tr('Attenzione: per il cliente selezionato sono presenti le seguenti fatture in stato Bozza').':</p><ul>" + content + "</ul>")
                    }
                }
            });


            $.ajax({
                url: globals.rootdir + "/actions.php",
                type: "POST",
                dataType: "json",
                data: {
                    id_module: globals.id_module,
                    id_anagrafica: data.id,
                    op: "fatture_scadute",
                },
                success: function (results) {
                    $("#info").removeClass("box-info");
                    $("#info").removeClass("box-warning");
                    if (results.length === 0){
                        $("#info-title-scadute").addClass("box-info");
                        $("#info-title-scadute").removeClass("box-warning");
                        $("#info-content-scadute").html("<p>'.tr('Per il cliente selezionato non è presente alcuna fattura Scaduta').'</p>")
                    } else {
                        let content = "";

                        results.forEach(function(item) {
                            content += "<li>" + item + "</li>";
                        });
                        $("#info-title-scadute").addClass("box-warning");
                        $("#info-title-scadute").removeClass("box-info");
                        $("#info-content-scadute").html("<p>'.tr('Attenzione: per il cliente selezionato le seguenti fatture presentamento una o più rate scadute').':</p><ul>" + content + "</ul>")
                    }
                }
            });


        }else{

            $("#info").addClass("hidden");
            return;
        }
    })
})
</script>';
}
