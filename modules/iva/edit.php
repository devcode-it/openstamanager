<?php

include_once __DIR__.'/../../core.php';

$esigibilita = [
    [
        'id' => 'I',
        'text' => tr('IVA ad esigibilità immediata'),
    ],
    [
        'id' => 'D',
        'text' => tr('IVA ad esigibilità differita'),
    ],
    [
        'id' => 'S',
        'text' => tr('Scissione dei pagamenti'),
    ],
];

$res = $dbo->fetchNum('SELECT `co_righe_documenti`.`id` FROM `co_righe_documenti` WHERE `co_righe_documenti`.`idiva`='.prepare($id_record).
' UNION SELECT `co_righe_preventivi`.`id` FROM `co_righe_preventivi` WHERE `co_righe_preventivi`.`idiva` = '.prepare($id_record).
' UNION SELECT `co_righe_contratti`.`id` FROM `co_righe_contratti` WHERE `co_righe_contratti`.`idiva` = '.prepare($id_record).
' UNION SELECT `dt_righe_ddt`.`id` FROM `dt_righe_ddt` WHERE `dt_righe_ddt`.`idiva` = '.prepare($id_record).
' UNION SELECT `or_righe_ordini`.`id` FROM `or_righe_ordini` WHERE `or_righe_ordini`.`idiva` = '.prepare($id_record).
' UNION SELECT `mg_articoli`.`id` FROM `mg_articoli` WHERE `mg_articoli`.`idiva_vendita` = '.prepare($id_record).
' UNION SELECT `an_anagrafiche`.`idanagrafica` AS `id` FROM `an_anagrafiche` WHERE `an_anagrafiche`.`idiva_vendite` = '.prepare($id_record).' OR `an_anagrafiche`.`idiva_acquisti` = '.prepare($id_record));
$is_readonly = 0;
if ($res) {
    $is_readonly = '1';
}

?><form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

	<!-- DATI -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Dati'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="row">
				<div class="col-md-12">
					{[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": "$descrizione$" ]}
				</div>
			</div>

			<div class="row">
                <div class="col-md-4">
					{[ "type": "checkbox", "label": "<?php echo tr('Esente'); ?>", "name": "esente", "id": "esente-edit", "value": "$esente$", "readonly": "<?php echo $is_readonly; ?>", "extra": "<?php echo $attr; ?>"]}
				</div>

				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Percentuale'); ?>", "name": "percentuale", "id": "percentuale-edit", "value": "$percentuale$", "icon-after": "<i class=\"fa fa-percent\"></i>", "disabled": <?php echo intval($record['esente']); ?>, "readonly": "<?php echo $is_readonly; ?>", "extra": "<?php echo $attr; ?>", "max-value": "100" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Indetraibile'); ?>", "name": "indetraibile", "value": "$indetraibile$", "icon-after": "<i class=\"fa fa-percent\"></i>", "readonly": "<?php echo $is_readonly; ?>", "extra": "<?php echo $attr; ?>", "max-value": "100" ]}
				</div>
			</div>

            <div class="row">
				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Codice'); ?>", "name": "codice", "value": "$codice$", "required": 1, "decimals":0, "min-value":"0", "max-value":"999", "maxlength": 3, "extra": "<?php echo $attr; ?>" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "select", "label": "<?php echo tr('Codice Natura (Fatturazione Elettronica)'); ?>", "name": "codice_natura_fe", "value": "$codice_natura_fe$", "required": <?php echo intval($record['esente']); ?>, "disabled": <?php echo intval(!$record['esente']); ?>, "values": "query=SELECT codice as id, CONCAT(codice, ' - ', descrizione) AS descrizione FROM fe_natura", "readonly": "<?php echo $is_readonly; ?>", "extra": "<?php echo $attr; ?>" ]}
				</div>

                <div class="col-md-4">
					{[ "type": "select", "label": "<?php echo tr('Esigibilità (Fatturazione Elettronica)'); ?>", "name": "esigibilita", "value": "$esigibilita$", "values": <?php echo json_encode($esigibilita); ?>, "required": 1, "readonly": "<?php echo $is_readonly; ?>", "extra": "<?php echo $attr; ?>" ]}
				</div>
			</div>

            <div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Dicitura fissa in fattura'); ?>", "name": "dicitura", "value": "$dicitura$" ]}
				</div>
			</div>
		</div>
	</div>

</form>


<script>
$(document).ready(function() {
    $('#esente-edit').change(function() {
        var checkbox = $(this).parent().find('[type=hidden]');

        if (checkbox.val() == 1) {
            $("#percentuale-edit").prop("disabled", true);
            $("#codice_natura_fe").prop("required", true);
            $("#codice_natura_fe").prop("disabled", false);
        } else {
            $("#percentuale-edit").prop("disabled", false);
            $("#codice_natura_fe").prop("required", false);
            $("#codice_natura_fe").val("").change();
            $("#codice_natura_fe").prop("disabled", true);
        }
    });
});
</script>


<?php

if ($res) {
    echo '
    <div class="alert alert-warning">
        <p>'.tr('Ci sono '.count($res).' documenti collegati a questa aliquota IVA. Non è possibile eliminarla o modificarne alcuni campi.').'</p>
    </div>';
} else {
    echo '
    <a class="btn btn-danger ask" data-backto="record-list">
        <i class="fa fa-trash"></i> '.tr('Elimina').'
    </a>';
}
