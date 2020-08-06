<?php

include_once __DIR__.'/../../core.php';

$module = Modules::get($id_module);

if ($module['name'] == 'Fatture di vendita') {
    $dir = 'entrata';
    $tipo_anagrafica = tr('Cliente');
} else {
    $dir = 'uscita';
    $tipo_anagrafica = tr('Fornitore');
}

$id_anagrafica = !empty(get('idanagrafica')) ? get('idanagrafica') : $user['idanagrafica'];

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
			{[ "type": "select", "label": "<?php echo $tipo_anagrafica; ?>", "name": "idanagrafica", "id": "idanagrafica_add", "required": 1, "ajax-source": "<?php echo $module['name'] == 'Fatture di vendita' ? 'clienti' : 'fornitori'; ?>", "value": "<?php echo $id_anagrafica; ?>", "icon-after": "add|<?php echo Modules::get('Anagrafiche')['id']; ?>|tipoanagrafica=<?php echo $tipo_anagrafica; ?>" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-6">
			{[ "type": "select", "label": "<?php echo tr('Tipo fattura'); ?>", "name": "idtipodocumento", "required": 1, "values": "query=SELECT id, descrizione FROM co_tipidocumento WHERE dir='<?php echo $dir; ?>'" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "select", "label": "<?php echo tr('Sezionale'); ?>", "name": "id_segment", "required": 1, "values": "query=SELECT id, name AS descrizione FROM zz_segments WHERE id_module='<?php echo $id_module; ?>' ORDER BY name", "value": "<?php echo $_SESSION['module_'.$id_module]['id_segment']; ?>" ]}
		</div>
	</div>

    <div class="box box-warning hidden" id="info">
        <div class="box-header with-border">
            <h3 class="box-title"><?php echo tr('Fatture in stato Bozza del cliente'); ?></h3>
            <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse">
                    <i class="fa fa-minus"></i>
                </button>
            </div>
        </div>
        <div class="box-body" id="info-content">
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
        var data = $(this).selectData();

        if (data !== undefined) {
            if (!data.id){
                $("#info").addClass("hidden");
                return;
            }

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
                    $("#info").removeClass("hidden");

                    if (results.length === 0){
                        $("#info-content").html("<p>'.tr('Nessuna fattura in stato Bozza presente per il cliente corrente').'</p>")
                    } else {
                        var content = "";

                        results.forEach(function(item) {
                            content += "<li>" + item + "</li>";
                        });

                        $("#info-content").html("<p>'.tr('Sono presenti le seguenti fatture in stato Bozza per il cliente corrente').':</p><ul>" + content + "</ul>")
                    }
                }
            });
        }
    })
})
</script>';
}
