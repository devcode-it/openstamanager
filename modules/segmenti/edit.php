<?php

include_once __DIR__.'/../../core.php';

?><form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">

	<!-- DATI SEGMENTO -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Segmento'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="row">

				<div class="col-md-4">
					{[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "name", "required": 1, "value": "$name$" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "select", "label": "<?php echo tr('Modulo'); ?>", "name": "module", "required": 1, "values": "query=SELECT id, name AS descrizione FROM zz_modules WHERE ( enabled = 1 AND options != 'custom' ) OR id = <?php echo $records[0]['id_module']; ?> ORDER BY name ASC", "value": "$id_module$", "extra": "<?php echo ($records[0]['predefined']) ? 'readonly' : ''; ?>" ]}
				</div>

				<div class="col-md-4">
					<?php
                    //($records[0]['n_sezionali']<2) ? $records[0]['predefined']=1 : '';
                    ?>
					{[ "type": "checkbox", "label": "<?php echo tr('Predefinito'); ?>", "name": "predefined", "value": "$predefined$", "help": "<?php echo tr('Seleziona per rendere il segmento predefinito.'); ?>", "placeholder": "<?php echo tr('Segmento predefinito'); ?>", "extra": "<?php echo ($records[0]['predefined']) ? 'readonly' : ''; ?>"  ]}
				</div>

			</div>

			<div class='row'>

				<div class="col-md-4">
					{[ "type": "checkbox", "label": "<?php echo tr('Predefinito note di accredito'); ?>", "name": "predefined_accredito", "value": "$predefined_accredito$", "help": "<?php echo tr('Seleziona per rendere il segmento predefinito per le note di accredito.'); ?>", "placeholder": "<?php echo tr('Segmento predefinito per le note di accredito'); ?>"  ]}
				</div>
				<div class="col-md-4">
					{[ "type": "checkbox", "label": "<?php echo tr('Predefinito note di addebito'); ?>", "name": "predefined_addebito", "value": "$predefined_addebito$", "help": "<?php echo tr('Seleziona per rendere il segmento predefinito per le note di addebito.'); ?>", "placeholder": "<?php echo tr('Segmento predefinito per le note di addebito'); ?>"  ]}
				</div>

			</div>

			<div class="row">

				<div class="col-md-8">
					{[ "type": "textarea", "label": "<?php echo tr('Filtro'); ?>", "name": "clause", "required": 1, "value": "$clause$" ]}
				</div>

				<div class="col-md-4">
                    {[ "type": "select", "label": "<?php echo tr('Posizione'); ?>", "name": "position",   "required": 1, "values":"list=\"WHR\": \"WHERE\", \"HVN\": \"HAVING\"", "value": "$position$" ]}
				</div>

			</div>

			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Note'); ?>", "name": "note", "required": 0, "value": "$note$" ]}
				</div>
			</div>
		</div>
	</div>

	<!-- Campi extra -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Extra'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="row">

				<div class="col-md-3">
                    {[ "type": "text", "label": "<?php echo tr('Maschera'); ?>", "name": "pattern", "required": 0, "class": "alphanumeric-mask", "value": "$pattern$", "maxlength": 25, "placeholder":"####/YY", "extra": "<?php echo ($tot > 0) ? 'readonly' : ''; ?>" ]}
				</div>
			</div>

			<!-- Istruzioni per il contenuto -->
            <div class="box box-info">
                <div class="box-header">
                    <h3 class="box-title"><?php echo tr('Istruzioni per il campo _FIELD_', [
                        '_FIELD_' => tr('Maschera'),
                    ]); ?></h3>
                </div>

                <div class="box-body">
                    <p><?php echo tr('Le seguenti sequenze di testo vengono sostituite nel seguente modo'); ?>:</p>
                    <ul>
<?php
$list = [
    '####' => tr('Numero progressivo del documento, con zeri non significativi per raggiungere il numero desiderato di caratteri'),
    'YYYY' => tr('Anno corrente a 4 cifre'),
    'yy' => tr('Anno corrente a 2 cifre'),
];

foreach ($list as $key => $value) {
    echo '
                        <li>'.tr('_TEXT_: _FIELD_', [
                            '_TEXT_' => '<code>'.$key.'</code>',
                            '_FIELD_' => $value,
                        ]).'</li>';
}

?>
                    </ul>

                    <p><?php echo tr("E' inoltre possibile aggiungere altri caratteri fissi (come lettere, trattini, eccetera) prima e/o dopo le sequenze di cui sopra"); ?>.</p>
                </div>
            </div>

		</div>
	</div>

</form>

<?php

$array = preg_match('/(?<=FROM)\s([^\s]+)\s/', $records[0]['options'], $table);
if (strpos($table[0], 'co_documenti') !== false) {
    $righe = $dbo->fetchArray('SELECT COUNT(*) AS tot FROM '.$table[0].' WHERE id_segment = '.prepare($id_record));
    $tot = $righe[0]['tot'];
}

if ($tot > 0) {
    echo "<div class='alert alert-danger' style='margin:0px;'>";

    echo tr("Ci sono _TOT_ righe collegate al segmento per il modulo '_MODULO_'. Il comando elimina è stato disattivato, eliminare le righe per attivare il comando 'Elimina segmento'.", [
        '_TOT_' => $tot,
        '_MODULO_' => $records[0]['modulo'],
    ]);

    echo '</div>';
} elseif ($records[0]['predefined']) {
    echo "<div class='alert alert-danger' style='margin:0px;'>";

    echo tr("Questo è il segmento predefinito per il modulo '_MODULO_'. Il comando elimina è stato disattivato.", [
        '_MODULO_' => $records[0]['modulo'],
    ]);

    echo '</div>';
} elseif ($records[0]['n_sezionali'] < 2) {
    echo "<div class='alert alert-danger' style='margin:0px;'>";

    echo tr("Questo è l'unico segmento per il modulo '_MODULO_'. Il comando elimina è stato disattivato.", [
        '_MODULO_' => $records[0]['modulo'],
    ]);

    echo '</div>';
} else {
    echo '
<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> '.tr('Elimina').'
</a>';
}
?>
