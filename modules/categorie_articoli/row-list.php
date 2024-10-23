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
use Modules\Articoli\Articolo;
use Modules\Articoli\Categoria;

$subcategorie = Categoria::where('parent', '=', $id_record)->get();

foreach ($subcategorie as $sub) {
    $n_articoli = Articolo::where('id_sottocategoria', '=', $sub['id'])->count();

    echo '
		<tr>
			<td>'.$sub->getTranslation('title').'</td>
			<td>'.$sub->colore.'</td>
			<td>'.$sub->getTranslation('note').'</td>
			<td>
				<a class="btn btn-warning btn-sm" title="Modifica riga" onclick="launch_modal(\''.tr('Modifica sottocategoria').'\', \''.base_path().'/add.php?id_module='.$id_module.'&id_record='.$sub->id.'&id_original='.$id_record.'\');"><i class="fa fa-edit"></i></a>
				<a class="btn btn-sm btn-danger ask '.(($n_articoli > 0) ? 'disabled tip' : '').'" data-backto="record-edit" data-id="'.$sub['id'].'" title="'.(($n_articoli > 0) ? 'Sottocategoria collegata a '.$n_articoli.' articoli' : '').'">
					<i class="fa fa-trash"></i>
				</a>
			</td>
		</tr>';
}
