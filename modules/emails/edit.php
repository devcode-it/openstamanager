<?php

include_once __DIR__.'/../../core.php';

echo '
<script src="'.$rootdir.'/assets/dist/js/ckeditor/ckeditor.js"></script>';

?>
<form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="backto" value="record-edit">

	<!-- DATI -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Dati') ?></h3>
		</div>

		<div class="panel-body">
            <div class="pull-right">
                <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> <?php echo tr('Salva modifiche'); ?></button>
            </div>
            <div class="clearfix"></div><br>

            <div class="row">
                <div class="col-md-6">
                    {[ "type": "text", "label": "<?php echo tr('Nome') ?>", "name": "name", "value": "$name$", "required": 1 ]}
                </div>

                <div class="col-md-2">
                    {[ "type": "span", "label": "<?php echo tr('Modulo del template') ?>", "name": "module", "values": "query=SELECT id, title AS descrizione FROM zz_modules WHERE enabled = 1", "value": "<?php echo Modules::get($records[0]['id_module'])['title']; ?>" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "checkbox", "label": "<?php echo tr('Notifica di lettura') ?>", "name": "read_notify",  "value": "$read_notify$", "placeholder": "<?php echo tr('Abilita la notifica di lettura') ?>" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    {[ "type": "email", "label": "<?php echo tr('Oggetto') ?>", "name": "subject", "value": "$subject$" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "text", "label": "<?php echo tr('Icona') ?>", "name": "icon", "value": "$icon$" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    {[ "type": "text", "label": "<?php echo tr('CC') ?>", "name": "cc", "value": "$cc$" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "text", "label": "<?php echo tr('BCC') ?>", "name": "port", "value": "$bcc$" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "text", "label": "<?php echo tr('Reply to') ?>", "name": "reply_to", "value": "$reply_to$" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    {[ "type": "textarea", "label": "<?php echo tr('Contenuto') ?>", "name": "body", "value": "$body$" ]}
                </div>
            </div>

        </div>
    </div>

</form>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>

<script>
    $(document).ready(function(){
        CKEDITOR.replace("body");
    });
</script>
