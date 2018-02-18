<?php

include_once __DIR__.'/../../core.php';

?>
<form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="backto" value="record-edit">

	<!-- DATI -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Dati'); ?></h3>
		</div>

		<div class="panel-body">
            <div class="pull-right">
                <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> <?php echo tr('Salva modifiche'); ?></button>
            </div>
            <div class="clearfix"></div><br>

            <div class="row">
                <div class="col-md-6">
                    {[ "type": "text", "label": "<?php echo tr('Nome account'); ?>", "name": "name", "value": "$name$", "required": 1 ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "checkbox", "label": "<?php echo tr('Indirizzo PEC'); ?>", "name": "pec",  "value": "$pec$" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "checkbox", "label": "<?php echo tr('Indirizzo predefinito'); ?>", "name": "main",  "value": "$main$" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    {[ "type": "text", "label": "<?php echo tr('Nome visualizzato'); ?>", "name": "from_name", "value": "$from_name$" ]}
                </div>

                <div class="col-md-6">
                    {[ "type": "email", "label": "<?php echo tr('Email mittente'); ?>", "name": "from_address", "value": "$from_address$" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    {[ "type": "text", "label": "<?php echo tr('Server SMTP'); ?>", "name": "server", "required": 1, "value": "$server$" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "text", "label": "<?php echo tr('Porta SMTP'); ?>", "name": "port", "required": 1, "class": "text-center", "value": "$port$" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "select", "label": "<?php echo tr('Sicurezza SMTP'); ?>", "name": "encryption", "values": "list=\"\": \"<?php echo tr('Nessuna'); ?>\", \"tls\": \"TLS\", \"ssl\": \"SSL\"", "value": "$encryption$" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    {[ "type": "text", "label": "<?php echo tr('Username SMTP'); ?>", "name": "username", "value": "$username$" ]}
                </div>

                <div class="col-md-6">
                    {[ "type": "password", "label": "<?php echo tr('Password SMTP'); ?>", "name": "password", "value": "$password$" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    {[ "type": "textarea", "label": "<?php echo tr('Note'); ?>", "name": "note", "value": "$note$" ]}
                </div>
            </div>

        </div>
    </div>

</form>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>
