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

echo '
<form action="" method="post" role="form">
    <input type="hidden" name="id_parent" value="'.$id_parent.'">
    <input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="addprovvigione">

	<!-- Fix creazione da Anagrafica -->
    <input type="hidden" name="id_record" value="0">

	<div class="row">
		<div class="col-md-4">
			{[ "type": "select", "label": "'.tr('Articolo').'", "name": "articolo", "ajax-source": "articoli", "value": "'.$id_parent.'", "disabled": "1" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "select", "label": "'.tr('Agente').'", "name": "idagente", "values": "query=SELECT `an_anagrafiche`.`idanagrafica` AS id, CONCAT(`ragione_sociale`, IF(`citta` IS NULL OR `citta` = \'\', \'\', CONCAT(\' (\', `citta`, \')\')), IF(`deleted_at` IS NULL, \'\', \' ('.tr('eliminata').')\')) AS descrizione, `idtipointervento_default` FROM `an_anagrafiche` INNER JOIN (`an_tipianagrafiche_anagrafiche` INNER JOIN `an_tipianagrafiche` ON `an_tipianagrafiche_anagrafiche`.`idtipoanagrafica`=`an_tipianagrafiche`.`id` LEFT JOIN `an_tipianagrafiche_lang` ON (`an_tipianagrafiche`.`id`=`an_tipianagrafiche_lang`.`id_record` AND `an_tipianagrafiche_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).')) ON `an_anagrafiche`.`idanagrafica`=`an_tipianagrafiche_anagrafiche`.`idanagrafica` WHERE `deleted_at` IS NULL AND `name`=\'Agente\' AND `an_anagrafiche`.`idanagrafica` NOT IN (SELECT `idagente` FROM `co_provvigioni`) ORDER BY `ragione_sociale`", "required": 1, "icon-after": "add|'.(new Module())->getByField('name', 'Anagrafiche', Models\Locale::getPredefined()->id).'|tipoanagrafica=Agente&readonly_tipo=1" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "number", "label": "'.tr('Provvigione').'", "name": "provvigione", "icon-after": "choice|untprc|UNT" ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> '.tr('Aggiungi').'</button>
		</div>
	</div>
</form>';
