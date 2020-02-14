<?php

include_once __DIR__.'/../../core.php';

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
                <div class="col-md-6">
                    {[ "type": "text", "label": "<?php echo tr('Nome account'); ?>", "name": "name", "value": "$name$", "required": 1 ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "checkbox", "label": "<?php echo tr('Indirizzo PEC'); ?>", "name": "pec", "value": "$pec$" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "checkbox", "label": "<?php echo tr('Indirizzo predefinito'); ?>", "name": "predefined", "value": "$predefined$", "help": "<?php echo tr('Account da utilizzare per l\'invio di tutte le email dal gestionale.'); ?>" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    {[ "type": "text", "label": "<?php echo tr('Nome visualizzato'); ?>", "name": "from_name", "value": "$from_name$" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "email", "label": "<?php echo tr('Email mittente'); ?>", "name": "from_address", "value": "$from_address$", "required": 1 ]}
                </div>

				<div class="col-md-3">
                    {[ "type": "checkbox", "label": "<?php echo tr('Non verificare il certificato SSL'); ?>", "name": "ssl_no_verify", "value": "$ssl_no_verify$" ]}
                </div>


            </div>

            <div class="row">
                <div class="col-md-6">
                    {[ "type": "text", "label": "<?php echo tr('Server SMTP'); ?>", "name": "server", "required": 1, "value": "$server$" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "number", "label": "<?php echo tr('Porta SMTP'); ?>", "name": "port", "required": 1, "class": "text-center", "decimals":"0", "max-value":"65535", "value": "$port$" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "select", "label": "<?php echo tr('Sicurezza SMTP'); ?>", "name": "encryption", "values": "list=\"\": \"<?php echo tr('Nessuna'); ?>\", \"tls\": \"TLS\", \"ssl\": \"SSL\"", "value": "$encryption$" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    {[ "type": "text", "label": "<?php echo tr('Username SMTP'); ?>", "name": "username", "value": "$username$" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "password", "label": "<?php echo tr('Password SMTP'); ?>", "name": "password", "value": "$password$" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "number", "label": "<?php echo tr('Timeout coda di invio (millisecondi)'); ?>", "name": "timeout", "value": "$timeout$", "decimals": 0 ]}
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

<?php
// Collegamenti diretti
// Template email collegati a questo account
$elementi = $dbo->fetchArray('SELECT `id`, `name` FROM `em_templates` WHERE `id_account` = '.prepare($id_record));

if (!empty($elementi)) {
    echo '
<div class="box box-warning collapsable collapsed-box">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-warning"></i> '.tr('Template email collegati: _NUM_', [
            '_NUM_' => count($elementi),
        ]).'</h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button>
        </div>
    </div>
    <div class="box-body">
        <ul>';

    foreach ($elementi as $elemento) {
        echo '
            <li>'.Modules::link('Template email', $elemento['id'], $elemento['name']).'</li>';
    }

    echo '
        </ul>
    </div>
</div>';
} else {
    echo '
<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> '.tr('Elimina').'
</a>';
}
?>
