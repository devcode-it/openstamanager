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
use Models\Module;

?><form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">

	<!-- DATI SEGMENTO -->
	<div class="card card-primary">
		<div class="card-header">
			<h3 class="card-title"><?php echo tr('Segmento'); ?></h3>
		</div>

		<div class="card-body">
			<div class="row">

				<div class="col-md-3">
					{[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "name", "required": 1, "value": "$title$" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Modulo'); ?>", "name": "module", "required": 1, "values": "query=SELECT `zz_modules`.`id`, `title` AS descrizione FROM `zz_modules` LEFT JOIN `zz_modules_lang` ON(`zz_modules`.`id` = `zz_modules_lang`.`id_record` AND `zz_modules_lang`.`id_lang` = <?php echo Models\Locale::getDefault()->id; ?>) WHERE (`enabled` = 1 AND `options` != 'custom' ) OR `zz_modules`.`id` = <?php echo $record['id_module']; ?> ORDER BY `title` ASC", "value": "<?php echo $record['id_module']; ?>", "extra": "<?php echo ($record['predefined']) ? 'readonly' : ''; ?>" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "checkbox", "label": "<?php echo tr('Predefinito'); ?>", "name": "predefined", "value": "$predefined$", "help": "<?php echo tr('Seleziona per rendere il segmento predefinito.'); ?>", "placeholder": "<?php echo tr('Segmento predefinito'); ?>", "extra": "<?php echo $record['predefined'] || ($record['is_sezionale'] == 0) ? 'readonly' : ''; ?>"  ]}
				</div>

                <div class="col-md-2">
					{[ "type": "checkbox", "label": "<?php echo tr('Tipologia'); ?>", "help": "<?php echo tr('Se sezionale verrà utilizzato il contatore'); ?>", "name": "is_sezionale", "value": "$is_sezionale$", "extra": "readonly", "values": "Sezionale,Segmento" ]}
				</div>

                <div class="col-md-2">
                    {[ "type": "text", "label": "<?php echo tr('Maschera'); ?>", "name": "pattern", "value": "$pattern$", "maxlength": 25, "placeholder":"####/YYYY", "extra": "<?php echo ($tot > 0) ? 'readonly' : ''; ?>", "extra": "<?php echo (!$record['is_sezionale']) ? 'readonly' : ''; ?>" ]}
				</div>
			</div>

			<div class="row">

				<div class="col-md-8">
					{[ "type": "textarea", "label": "<?php echo tr('Filtro'); ?>", "name": "clause", "required": 1, "value": "$clause$", "extra": "<?php echo ($record['is_sezionale']) ? 'readonly' : ''; ?>" ]}
				</div>

				<div class="col-md-4">
                    {[ "type": "select", "label": "<?php echo tr('Posizione'); ?>", "name": "position", "required": 1, "values":"list=\"WHR\": \"WHERE\", \"HVN\": \"HAVING\"", "value": "$position$", "extra": "<?php echo ($record['is_sezionale']) ? 'readonly' : ''; ?>" ]}
				</div>

			</div>
<?php

$previous = $_SESSION['module_'.$record['id_module']]['id_segment'];
$previous_module = $_SESSION['module_'.$record['id_module']]['id_segment'];
$_SESSION['module_'.$id_module]['id_segment'] = $id_record;
$_SESSION['module_'.$record['id_module']]['id_segment'] = $id_record;

$current_module = Module::find($record['id_module']);
$total = Util\Query::readQuery($current_module);
$module_query = Modules::replaceAdditionals($record['id_module'], $total['query']);

echo '
            <p><strong>'.tr('Query risultante').':</strong></p>
            <p>'.htmlentities((string) $module_query).'</p>';

$_SESSION['module_'.$id_module]['id_segment'] = $previous;
$_SESSION['module_'.$record['id_module']]['id_segment'] = $previous_module;

?>
			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Note'); ?>", "name": "note", "value": "$note$" ]}
				</div>
			</div>
<?php
        if (!empty($record['is_fiscale'])) {
            ?>
            <div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Dicitura fissa'); ?>", "name": "dicitura_fissa", "value": "$dicitura_fissa$" ]}
				</div>
			</div>
<?php
        }
echo '
            <div class="row">
				<div class="col-md-12">
                    {[ "type": "select", "label": "'.tr('Gruppi con accesso').'", "name": "gruppi[]", "multiple": "1", "values": "query=SELECT `zz_groups`.`id`, `title` AS descrizione FROM `zz_groups` LEFT JOIN `zz_groups_lang` ON (`zz_groups`.`id` = `zz_groups_lang`.`id_record` AND `zz_groups_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') ORDER BY `zz_groups`.`id` ASC", "value": "';
$results = $dbo->fetchArray('SELECT GROUP_CONCAT(DISTINCT `id_gruppo` SEPARATOR \',\') AS gruppi FROM `zz_group_segment` WHERE `id_segment`='.prepare($id_record));

echo $results[0]['gruppi'].'"';

echo ', "help": "'.tr('Gruppi di utenti in grado di visualizzare questo segmento').'" ]}
				</div>
			</div>';
?>
		</div>
	</div>

<?php
if ($record['is_sezionale']) {
    ?>
	<!-- Campi extra -->
	<div class="card card-primary">
		<div class="card-header">
			<h3 class="card-title"><?php echo tr('Sezionale'); ?></h3>
		</div>

		<div class="card-body">
			<div class="row">
                <div class="col-md-6">
                    {[ "type": "checkbox", "label": "<?php echo tr('Sezionale fiscale'); ?>", "name": "is_fiscale", "value": "$is_fiscale$", "extra": "<?php echo ($tot > 0 || ($record['modulo'] != 'Fatture di vendita' && $record['modulo'] != 'Fatture di acquisto')) ? 'readonly' : ''; ?>"  ]}
				</div>
            </div>

			<div class="row">
                <div class="col-md-3">
                    {[ "type": "checkbox", "label": "<?php echo tr('Predefinito note di credito'); ?>", "name": "predefined_accredito", "value": "$predefined_accredito$", "help": "<?php echo tr('Seleziona per rendere il sezionale predefinito per le note di credito'); ?>", "placeholder": "<?php echo tr('Sezionale predefinito per le note di credito'); ?>", "extra": "<?php echo ($record['modulo'] != 'Fatture di vendita' && $record['modulo'] != 'Fatture di acquisto') ? 'readonly' : ''; ?>"  ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "checkbox", "label": "<?php echo tr('Predefinito note di debito'); ?>", "name": "predefined_addebito", "value": "$predefined_addebito$", "help": "<?php echo tr('Seleziona per rendere il sezionale predefinito per le note di debito'); ?>", "placeholder": "<?php echo tr('Sezionale predefinito per le note di debito'); ?>", "extra": "<?php echo ($record['modulo'] != 'Fatture di vendita' && $record['modulo'] != 'Fatture di acquisto') ? 'readonly' : ''; ?>"  ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "checkbox", "label": "<?php echo tr('Utilizzabile per autofatture'); ?>", "name": "autofatture", "value": "$autofatture$", "help":  "<?php echo tr('Seleziona per rendere utilizzabile il sezionale per le autofatture'); ?>", "extra": "<?php echo ($record['modulo'] != 'Fatture di vendita' && $record['modulo'] != 'Fatture di acquisto') ? 'readonly' : ''; ?>" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "checkbox", "label": "<?php echo tr('Utilizzabile per fatture elettroniche'); ?>", "name": "for_fe", "value": "$for_fe$", "help":  "<?php echo tr('Seleziona per rendere utilizzabile il sezionale per le fatture elettroniche'); ?>", "extra": "<?php echo ($record['modulo'] != 'Fatture di vendita' && $record['modulo'] != 'Fatture di acquisto') ? 'readonly' : ''; ?>" ]}
                </div>
			</div>

			<!-- Istruzioni per il contenuto -->
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title"><?php echo tr('Istruzioni per il campo _FIELD_', [
                        '_FIELD_' => tr('Maschera'),
                    ]); ?></h3>
                </div>

                <div class="card-body">
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
    } ?>
                    </ul>

                    <p><?php echo tr("E' inoltre possibile aggiungere altri caratteri fissi (come lettere, trattini, eccetera) prima e/o dopo le sequenze di cui sopra"); ?>.</p>
                </div>
            </div>

		</div>
	</div>
<?php
}
?>
</form>

<?php
if ($tot > 0) {
    echo "<div class='alert alert-danger' style='margin:0px;'>";

    echo tr("Ci sono _TOT_ righe collegate al segmento per il modulo '_MODULO_'. Il comando elimina è stato disattivato, eliminare le righe per attivare il comando 'Elimina segmento'.", [
        '_TOT_' => $tot,
        '_MODULO_' => $record['modulo'],
    ]);

    echo '</div>';
} elseif ($records['predefined']) {
    echo "<div class='alert alert-danger' style='margin:0px;'>";

    echo tr("Questo è il segmento predefinito per il modulo '_MODULO_'. Il comando elimina è stato disattivato.", [
        '_MODULO_' => $record['modulo'],
    ]);

    echo '</div>';
} elseif ($record['n_sezionali'] < 2) {
    echo "<div class='alert alert-danger' style='margin:0px;'>";

    echo tr("Questo è l'unico segmento per il modulo '_MODULO_'. Il comando elimina è stato disattivato.", [
        '_MODULO_' => $record['modulo'],
    ]);

    echo '</div>';
} else {
    echo '
<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> '.tr('Elimina').'
</a>';
}
?>
