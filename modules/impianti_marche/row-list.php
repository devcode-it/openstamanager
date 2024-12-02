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
use Modules\Impianti\Marca;

$modelli = Marca::where('parent', '=', $id_record)->get();

foreach ($modelli as $modello) {
    $n_impianti = $dbo->fetchNum('SELECT * FROM `my_impianti` WHERE `id_modello`='.prepare($modello->id));
    echo '
		<tr>
			<td>'.$modello->getTranslation('title').'</td>
			<td>
				<a class="btn btn-warning btn-sm" title="Modifica riga" onclick="launch_modal(\''.tr('Modifica modello').'\', \''.base_path().'/add.php?id_module='.$id_module.'&id_record='.$modello->id.'&id_original='.$id_record.'\');"><i class="fa fa-edit"></i></a>
				<a class="btn btn-sm btn-danger ask '.(($n_impianti > 0) ? 'disabled tip' : '').'" data-backto="record-edit" data-id="'.$modello->id.'" title="'.(($n_impianti > 0) ? 'Modello collegata a '.$n_impianti.' impianti' : '').'">
					<i class="fa fa-trash"></i>
				</a>
			</td>
		</tr>';
}
