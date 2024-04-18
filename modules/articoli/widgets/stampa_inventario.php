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

include_once __DIR__.'/../../../core.php';

// Trovo id_print della stampa
$id_print = $dbo->fetchOne('SELECT 
			`zz_prints`.`id` 
		FROM 
			`zz_prints`
			LEFT JOIN `zz_prints_lang` ON (`zz_prints`.`id` = `zz_prints_lang`.`id_record` AND `zz_prints_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
			INNER JOIN `zz_modules` ON `zz_prints`.`id_module`=`zz_modules`.`id` 
			LEFT JOIN `zz_modules_lang` ON (`zz_modules`.`id` = `zz_modules_lang`.`id_record` AND `zz_modules_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') 
		WHERE 
			`zz_modules_lang`.`title`="Articoli" AND `zz_prints_lang`.`title`="Inventario magazzino"')['id'];

echo '
<form action="'.base_path().'/pdfgen.php?id_print='.$id_print.'" method="post" target="_blank">

	<div class="row">

        <div class="col-md-4">
			{[ "type": "select", "label": "'.tr('Articoli da stampare').'", "name": "tipo", "required": "1", "values": "list=\"full\": \"'.tr('Tutti').'\", \"nozero\": \"'.tr('Solo esistenti').'\"", "value": "nozero", "help": "'.tr("''Solo esistenti'' indica articoli (attivi o disattivi) con quantità totale maggiore di 0").'." ]}
		</div>

		<div class="col-md-4">
			{[ "type": "select", "label": "'.tr('Prezzo di acquisto').'", "name": "acquisto", "required": "1", "values": "list=\"standard\": \"'.tr('Scheda articolo').'\", \"first\": \"'.tr('Primo acquisto').'\", \"last\": \"'.tr('Ultimo acquisto').'\", \"media\": \"'.tr('Media ponderata').'\"", "value": "standard" ]}
		</div>

		<div class="col-md-2">
			<p style="line-height:14px;">&nbsp;</p>

			<button type="submit" class="btn btn-primary btn-block">
			<i class="fa fa-print"></i> '.tr('Stampa').'
			</button>
		</div>

	</div>

</form>

<script>$(document).ready(init)</script>';
