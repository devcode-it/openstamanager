<?php

include_once __DIR__.'/../../core.php';

echo '
<script src="'.$rootdir.'/assets/dist/js/ckeditor/ckeditor.js"></script>';

?>
<form action="" method="post" id="edit-form">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="backto" value="record-edit">

	<!-- DATI -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Dati'); ?></h3>
		</div>

		<div class="panel-body">
            <div class="row">
                <div class="col-md-8">
                    {[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "name", "value": "$name$", "required": 1 ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "span", "label": "<?php echo tr('Modulo del template'); ?>", "name": "module", "values": "query=SELECT id, title AS descrizione FROM zz_modules WHERE enabled = 1", "value": "<?php echo Modules::get($records[0]['id_module'])['title']; ?>" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    {[ "type": "select", "label": "<?php echo tr('Indirizzo email'); ?>", "name": "smtp",  "value": "$id_smtp$", "ajax-source": "smtp" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "checkbox", "label": "<?php echo tr('Notifica di lettura'); ?>", "name": "read_notify",  "value": "$read_notify$", "placeholder": "<?php echo tr('Abilita la notifica di lettura'); ?>" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    {[ "type": "text", "label": "<?php echo tr('Oggetto'); ?>", "name": "subject", "value": "$subject$" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "text", "label": "<?php echo tr('Icona'); ?>", "name": "icon", "value": "$icon$" ,"help":"<?php echo tr('Es. \'fa fa-envelope\''); ?>" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    {[ "type": "text", "label": "<?php echo tr('CC'); ?>", "name": "cc", "value": "$cc$" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "text", "label": "<?php echo tr('BCC'); ?>", "name": "bcc", "value": "$bcc$" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "email", "label": "<?php echo tr('Reply to'); ?>", "name": "reply_to", "value": "$reply_to$" ]}
                </div>
            </div>

<?php

// Stampe
$selected_prints = $dbo->fetchArray('SELECT id_print FROM zz_email_print WHERE id_email = '.prepare($id_record));
$selected = array_column($selected_prints, 'id_print');

echo '

            <div class="row">
                <div class="col-md-12">
                    {[ "type": "select", "multiple": "1", "label": "'.tr('Stampe').'", "name": "prints[]", "value": "'.implode(',', $selected).'", "values": "query=SELECT id, title AS text FROM zz_prints WHERE id_module = '.prepare($records[0]['id_module']).'" ]}
                </div>
            </div>';

?>

            <div class="row">
                <div class="col-md-12">
                    {[ "type": "textarea", "label": "<?php echo tr('Contenuto'); ?>", "name": "body", "value": "$body$" ]}
                </div>
            </div>

<?php

// Variabili utilizzabili
$module_dir = DOCROOT.'/modules/'.Modules::get($records[0]['id_module'])['directory'];
$variables = [];

if (file_exists($module_dir.'/custom/variables.php')) {
    $variables = include $module_dir.'/custom/variables.php';
} elseif (file_exists($module_dir.'/variables.php')) {
    $variables = include $module_dir.'/variables.php';
}

if (sizeof($variables) > 0) {
    echo '
            <div class="alert alert-info">
                <p>'.tr("Puoi utilizzare le seguenti variabili nell'oggetto e nel corpo della mail").':</p>
                <ul>';

    foreach ($variables as $variable => $value) {
        echo '
                    <li>{'.$variable.'}</li>';
    }

    echo '
                </ul>
            </div>';
} else {
    echo '
            <div class="alert alert-warning">
                <i class="fa fa-warning"></i> '.tr('Non sono state definite variabili da utilizzare nel template').'.
            </div>';
}

?>

        </div>
    </div>

</form>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>

<script>
    $(document).ready(function(){
        CKEDITOR.replace("body", {
            toolbar: globals.ckeditorToolbar
        });
    });
</script>
