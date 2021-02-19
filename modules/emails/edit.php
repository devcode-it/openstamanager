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

if (!$record['predefined']) {
    $attr = '';
} else {
    $attr = 'readonly';
    echo '<div class="alert alert-warning">'.tr('Alcune impostazioni non possono essere modificate per questo template.').'</div>';
}

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
                    {[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "name", "value": "$name$", "required": 1, "extra": "<?php echo $attr; ?>" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "span", "label": "<?php echo tr('Modulo del template'); ?>", "name": "module", "values": "query=SELECT id, title AS descrizione FROM zz_modules WHERE enabled = 1", "value": "<?php echo Modules::get($record['id_module'])['title']; ?>" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    {[ "type": "select", "label": "<?php echo tr('Indirizzo email'); ?>", "name": "smtp", "value": "$id_account$", "ajax-source": "smtp" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "checkbox", "label": "<?php echo tr('Richiedi notifica di lettura'); ?>", "name": "read_notify", "value": "$read_notify$", "placeholder": "<?php echo tr('Richiedi la notifica di lettura al destinatario.'); ?>" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    {[ "type": "text", "label": "<?php echo tr('Oggetto'); ?>", "name": "subject", "value": "$subject$" ]}
                </div>

                <div class="col-md-4">
					<?php $records[0]['icon'] = (empty($records[0]['icon'])) ? 'fa fa-envelope' : $records[0]['icon']; ?>
                    {[ "type": "text", "label": "<?php echo tr('Icona'); ?>", "name": "icon", "value": "<?php echo $records[0]['icon']; ?>" ,"help":"<?php echo tr('Es. \'fa fa-envelope\''); ?>" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    {[ "type": "text", "label": "<?php echo tr('CC'); ?>", "name": "cc", "value": "$cc$", "help": "<?php echo 'Copia carbone.'; ?>" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "text", "label": "<?php echo tr('CCN'); ?>", "name": "bcc", "value": "$bcc$", "help": "<?php echo 'Copia carbone nascosta.'; ?>" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "email", "label": "<?php echo tr('Rispondi a'); ?>", "name": "reply_to", "value": "$reply_to$", "help": "<?php echo 'Rispondi a questo indirizzo e-mail.'; ?>" ]}
                </div>
            </div>

<?php

// Stampe
$selected_prints = $dbo->fetchArray('SELECT id_print FROM em_print_template WHERE id_template = '.prepare($id_record));
$selected = array_column($selected_prints, 'id_print');

echo '

            <div class="row">
                <div class="col-md-12">
                    {[ "type": "select", "multiple": "1", "label": "'.tr('Stampe').'", "name": "prints[]", "value": "'.implode(',', $selected).'", "values": "query=SELECT id, title AS text FROM zz_prints WHERE id_module = '.prepare($record['id_module']).' AND enabled=1" ]}
                </div>
            </div>';

?>

            <div class="row">
                <div class="col-md-12">
                    {[ "type": "ckeditor", "label": "<?php echo tr('Contenuto'); ?>", "name": "body", "value": "$body$" ]}
                </div>
            </div>

<?php

// Variabili utilizzabili
$variables = include Modules::filepath($record['id_module'], 'variables.php');

echo '
<!-- Istruzioni per il contenuto -->
<div class="box box-info">
    <div class="box-header">
        <h3 class="box-title">'.tr('Variabili').'</h3>
    </div>

    <div class="box-body">';

if (!empty($variables)) {
    echo '
        <p>'.tr("Puoi utilizzare le seguenti variabili nell'oggetto e nel corpo della mail").':</p>
        <ul>';

    foreach ($variables as $variable => $value) {
        echo '
            <li><code>{'.$variable.'}</code></li>';
    }

    echo '
        </ul>';
} else {
    echo '
        <p><i class="fa fa-warning"></i> '.tr('Non sono state definite variabili da utilizzare nel template').'.</p>';
}

echo '
    </div>
</div>

<hr>';

?>

        </div>
    </div>

</form>
<?php

if (!$record['predefined']) {
    ?>
<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>

<?php
}
?>
