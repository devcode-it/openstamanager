<?php

include_once __DIR__.'/../../core.php';

?>
<form action="" method="post" id="edit-form">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">

    <div class="row">
		<div class="col-md-6">
			{[ "type": "select", "label": "<?php echo tr('Modulo'); ?>", "name": "module_id", "values": "query=SELECT id, name as text FROM zz_modules WHERE enabled = 1", "value": "<?php echo $records[0]['id_module'] ?>" ]}
		</div>

        <div class="col-md-6">
			{[ "type": "select", "label": "<?php echo tr('Plugin'); ?>", "name": "plugin_id", "values": "query=SELECT id, name as text FROM zz_plugins WHERE enabled = 1", "value": "$id_plugin$" ]}
		</div>
    </div>

	<div class="row">
		<div class="col-md-6">
			{[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "name", "required": 1, "value": "$name$" ]}
		</div>

        <div class="col-md-6">
			{[ "type": "text", "label": "<?php echo tr('Nome HTML'); ?>", "name": "html_name", "required": 1, "value": "$html_name$" ]}
		</div>
	</div>

    <div class="row">
		<div class="col-md-6">
			{[ "type": "checkbox", "label": "<?php echo tr('Mostra alla creazione record'); ?>", "name": "on_add","value": "$on_add$" ]}
		</div>

        <div class="col-md-6">
			{[ "type": "checkbox", "label": "<?php echo tr('Mostra di sopra'); ?>", "name": "top", "value": "$top$" ]}
		</div>
	</div>

    <div class="row">
		<div class="col-md-12">
			{[ "type": "textarea", "label": "<?php echo tr('Contenuto'); ?>", "name": "content", "required": 1, "value": "$content$" ]}
		</div>
	</div>
</form>

<!-- Istruzioni per il contenuto -->
<div class="box box-info">
    <div class="box-header">
        <h3 class="box-title"><?php echo tr('Istruzioni per il campo _FIELD_', [
            '_FIELD_' => tr('Contenuto'),
        ]); ?></h3>
    </div>

    <div class="box-body">
        <p><?php echo tr('Le seguenti sequenze di testo vengono sostituite nel seguente modo'); ?>:</p>
        <ul>
<?php
$list = [
    'name' => tr('Nome'),
    'html_name' => tr('Nome HTML'),
];

foreach ($list as $key => $value) {
    echo '
            <li>'.tr('_TEXT_ con il valore del campo "_FIELD_"', [
                '_TEXT_' => '<code>|'.$key.'|</code>',
                '_FIELD_' => $value,
            ]).'</li>';
}

echo '
            <li>'.tr('_TEXT_ con il valore impostato per il record', [
                '_TEXT_' => '<code>|value|</code>',
            ]).'</li>';

?>
        </ul>
    </div>
</div>

<hr>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>
