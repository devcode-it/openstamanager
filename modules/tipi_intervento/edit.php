<?php

include_once __DIR__.'/../../core.php';

?><form action="" method="post">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">

	<div class="row">
		<div class="col-md-4">
			{[ "type": "span", "label": "<?php echo tr('Codice'); ?>", "name": "codice", "value": "$codice$" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": "$descrizione$" ]}
		</div>

		<div class="col-md-2">
			{[ "type": "number", "label": "<?php echo tr('Tempo standard'); ?>", "name": "tempo_standard", "help": "<?php echo tr('Valore compreso tra 0,25 - 24 ore. <br><small>Esempi: <em><ul><li>60 minuti = 1 ora</li><li>30 minuti = 0,5 ore</li><li>15 minuti = 0,25 ore</li></ul></em></small> Suggerisce il tempo solitamente impiegato per questa tipologia di attivita'); ?>.", "maxlength": 5, "min-value": "0", "max-value": "24", "class": "text-center", "value": "$tempo_standard$", "icon-after": "ore"  ]}
		</div>

	</div>

	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Addebiti unitari al cliente'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="row">
				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Addebito orario'); ?>", "name": "costo_orario", "required": 1, "value": "$costo_orario$", "icon-after": "<i class='fa fa-euro'></i>" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Addebito km'); ?>", "name": "costo_km", "required": 1, "value": "$costo_km$", "icon-after": "<i class='fa fa-euro'></i>" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Addebito diritto ch.'); ?>", "name": "costo_diritto_chiamata", "required": 1, "value": "$costo_diritto_chiamata$", "icon-after": "<i class='fa fa-euro'></i>" ]}
				</div>
			</div>
		</div>
	</div>


	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Costi unitari del tecnico'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="row">
				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Costo orario'); ?>", "name": "costo_orario_tecnico", "required": 1, "value": "$costo_orario_tecnico$", "icon-after": "<i class='fa fa-euro'></i>" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Costo km'); ?>", "name": "costo_km_tecnico", "required_tecnico": 1, "value": "$costo_km_tecnico$", "icon-after": "<i class='fa fa-euro'></i>" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Costo diritto ch.'); ?>", "name": "costo_diritto_chiamata_tecnico", "required": 1, "value": "$costo_diritto_chiamata_tecnico$", "icon-after": "<i class='fa fa-euro'></i>" ]}
				</div>
			</div>
		</div>
	</div>
</form>

<?php
 // Permetto eliminazione tipo intervento solo se questo non è utilizzado da nessun'altra parte a gestionale
$elementi = $dbo->fetchArray('SELECT `in_interventi`.`idtipointervento`  FROM `in_interventi` WHERE `in_interventi`.`idtipointervento` = '.prepare($id_record).'
UNION
SELECT `an_anagrafiche`.`idtipointervento_default` AS `idtipointervento` FROM `an_anagrafiche` WHERE `an_anagrafiche`.`idtipointervento_default` = '.prepare($id_record).'
UNION
SELECT `co_preventivi`.`idtipointervento` FROM `co_preventivi` WHERE `co_preventivi`.`idtipointervento` = '.prepare($id_record).'
UNION
SELECT `co_promemoria`.`idtipointervento` FROM `co_promemoria` WHERE `co_promemoria`.`idtipointervento` = '.prepare($id_record).'
UNION
SELECT `in_tariffe`.`idtipointervento` FROM `in_tariffe` WHERE `in_tariffe`.`idtipointervento` = '.prepare($id_record).'
UNION
SELECT `in_interventi_tecnici`.`idtipointervento` FROM `in_interventi_tecnici` WHERE `in_interventi_tecnici`.`idtipointervento` = '.prepare($id_record).'
UNION
SELECT `co_contratti_tipiintervento`.`idtipointervento` FROM `co_contratti_tipiintervento` WHERE `co_contratti_tipiintervento`.`idtipointervento` = '.prepare($id_record).'
ORDER BY `idtipointervento`');

if (!empty($elementi)) {
    echo '
    <div class="alert alert-danger">
        '.tr('Ci sono _NUM_ records collegati', [
            '_NUM_' => count($elementi),
        ]).'.
    </div>';
} else {
    echo '
<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> '.tr('Elimina').'
</a>';
}
