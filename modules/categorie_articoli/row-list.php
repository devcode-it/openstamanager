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

$subcategorie = $dbo->fetchArray('SELECT * FROM `mg_categorie` LEFT JOIN `mg_categorie_lang` ON (`mg_categorie`.`id`=`mg_categorie_lang`.`id_record` AND `mg_categorie_lang`.`id_lang`='.prepare(setting('Lingua')).') WHERE `parent`='.prepare($id_record).' ORDER BY `name` ASC ');

foreach ($subcategorie as $sub) {
    $n_articoli = $dbo->fetchNum('SELECT * FROM `mg_articoli` LEFT JOIN `mg_articoli_lang` ON (`mg_articoli`.`id` = `mg_articoli_lang`.`id_record` AND `mg_articoli_lang`.`id_lang` = '.prepare(setting('Lingua')).') WHERE `id_sottocategoria`='.prepare($sub['id']).' AND deleted_at IS NULL');
    echo '
				<tr>
					<td>'.$sub['name'].'</td>
					<td>'.$sub['colore'].'</td>
					<td>'.$sub['nota'].'</td>
					<td>
						<a class="btn btn-warning btn-sm" title="Modifica riga" onclick="launch_modal(\''.tr('Modifica sottocategoria').'\', \''.base_path().'/add.php?id_module='.$id_module.'&id_record='.$sub['id'].'&id_original='.$id_record.'\');"><i class="fa fa-edit"></i></a>
                        <a class="btn btn-sm btn-danger ask '.(($n_articoli > 0) ? 'disabled tip' : '').'" data-backto="record-edit" data-id="'.$sub['id'].'" title="'.(($n_articoli > 0) ? 'Sottocategoria collegata a '.$n_articoli.' articoli' : '').'">
                            <i class="fa fa-trash"></i>
                        </a>
					</td>
				</tr>';
}
