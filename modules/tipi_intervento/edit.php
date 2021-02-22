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
			{[ "type": "number", "label": "<?php echo tr('Tempo standard'); ?>", "name": "tempo_standard", "help": "<?php echo tr('Valore compreso tra 0,25 - 24 ore. <br><small>Esempi: <em><ul><li>60 minuti = 1 ora</li><li>30 minuti = 0,5 ore</li><li>15 minuti = 0,25 ore</li></ul></em></small> Suggerisce il tempo solitamente impiegato per questa tipologia di attivita'); ?>.", "min-value": "0", "max-value": "24", "class": "text-center", "value": "$tempo_standard$", "icon-after": "ore"  ]}
		</div>

	</div>

	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Addebiti unitari al cliente'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="row">
				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Addebito orario'); ?>", "name": "costo_orario", "required": 1, "value": "$costo_orario$", "icon-after": "<i class='fa fa-euro'></i>", "help": "<?php echo tr('Addebito al cliente'); ?>" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Addebito km'); ?>", "name": "costo_km", "required": 1, "value": "$costo_km$", "icon-after": "<i class='fa fa-euro'></i>", "help": "<?php echo tr('Costo al Cliente per KM'); ?>" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Addebito diritto ch.'); ?>", "name": "costo_diritto_chiamata", "required": 1, "value": "$costo_diritto_chiamata$", "icon-after": "<i class='fa fa-euro'></i>", "help": "<?php echo tr('Addebito al Cliente per il diritto di chiamata'); ?>" ]}
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
					{[ "type": "number", "label": "<?php echo tr('Costo orario'); ?>", "name": "costo_orario_tecnico", "required": 1, "value": "$costo_orario_tecnico$", "icon-after": "<i class='fa fa-euro'></i>", "help": "<?php echo tr('Costo interno'); ?>" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Costo km'); ?>", "name": "costo_km_tecnico", "required_tecnico": 1, "value": "$costo_km_tecnico$", "icon-after": "<i class='fa fa-euro'></i>", "help": "<?php echo tr('Costo interno per  KM'); ?>" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "number", "label": "<?php echo tr('Costo diritto ch.'); ?>", "name": "costo_diritto_chiamata_tecnico", "required": 1, "value": "$costo_diritto_chiamata_tecnico$", "icon-after": "<i class='fa fa-euro'></i>", "help": "<?php echo tr('Costo interno per il diritto di chiamata'); ?>" ]}
				</div>
			</div>
		</div>
	</div>
</form>

<?php
//Permetto eliminazione tipo intervento solo se questo non è utilizzado da nessun'altra parte nel gestionale
//UNION SELECT `in_tariffe`.`idtipointervento` FROM `in_tariffe` WHERE `in_tariffe`.`idtipointervento` = '.prepare($id_record).'
//UNION SELECT `co_contratti_tipiintervento`.`idtipointervento` FROM `co_contratti_tipiintervento` WHERE `co_contratti_tipiintervento`.`idtipointervento` = '.prepare($id_record).'
$elementi = $dbo->fetchArray('SELECT `in_interventi`.`idtipointervento`, id, codice AS numero, data_richiesta AS data, "Intervento" AS tipo_documento FROM `in_interventi` WHERE `in_interventi`.`idtipointervento` = '.prepare($id_record).'
UNION
SELECT `in_interventi_tecnici`.`idtipointervento`, idintervento AS id, codice AS numero, orario_inizio AS data, "Sessione intervento" AS tipo_documento FROM `in_interventi_tecnici` LEFT JOIN in_interventi ON in_interventi_tecnici.idintervento=in_interventi.id WHERE `in_interventi_tecnici`.`idtipointervento` = '.prepare($id_record).'
UNION
SELECT `an_anagrafiche`.`idtipointervento_default` AS `idtipointervento`, idanagrafica AS id, codice, "0000-00-00" AS data, "Anagrafica" AS tipo_documento FROM `an_anagrafiche` WHERE `an_anagrafiche`.`idtipointervento_default` = '.prepare($id_record).'
UNION
SELECT `co_preventivi`.`idtipointervento`, id, numero, data_bozza AS data, "Preventivo" AS tipo_documento FROM `co_preventivi` WHERE `co_preventivi`.`idtipointervento` = '.prepare($id_record).'
UNION
SELECT `co_promemoria`.`idtipointervento`, idcontratto AS id, numero, data_richiesta AS data, "Promemoria contratto" AS tipo_documento FROM `co_promemoria` LEFT JOIN co_contratti ON co_promemoria.idcontratto=co_contratti.id WHERE `co_promemoria`.`idtipointervento` = '.prepare($id_record).'
ORDER BY `idtipointervento`');

if (!empty($elementi)) {
    echo '
<div class="box box-warning collapsable collapsed-box">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-warning"></i> '.tr('Documenti collegati: _NUM_', [
            '_NUM_' => count($elementi),
        ]).'</h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button>
        </div>
    </div>
    <div class="box-body">
        <ul>';

    foreach ($elementi as $elemento) {
        $descrizione = tr('_DOC_ num. _NUM_ del _DATE_', [
            '_DOC_' => $elemento['tipo_documento'],
            '_NUM_' => $elemento['numero'],
            '_DATE_' => Translator::dateToLocale($elemento['data']),
        ]);

        if (in_array($elemento['tipo_documento'], ['Intervento'])) {
            $modulo = 'Interventi';
        }
        if (in_array($elemento['tipo_documento'], ['Sessione intervento'])) {
            $modulo = 'Interventi';
        }
        if (in_array($elemento['tipo_documento'], ['Anagrafica'])) {
            $modulo = 'Anagrafiche';
        }
        if (in_array($elemento['tipo_documento'], ['Preventivo'])) {
            $modulo = 'Preventivi';
        }
        if (in_array($elemento['tipo_documento'], ['Promemoria contratto'])) {
            $modulo = 'Contratti';
        }
        $id = $elemento['id'];

        echo '
            <li>'.Modules::link($modulo, $id, $descrizione).'</li>';
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
