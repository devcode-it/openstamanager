<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

function menuSelection($element, $group_id, $depth, $permessi_disponibili)
{
    $dbo = database();
    ++$depth;

    $result = '
                <tr>
					<td>'.str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $depth).$element['title'].'</td>
                    <td>
						<select name="permesso_'.$element['id'].'" id="permesso_'.$element['id'].'" class="form-control superselect openstamanager-input select-input" onchange="update_permissions('.$element['id'].', $(this).find(\'option:selected\').val())">';

    // Permessi impostati per il gruppo
    $permesso_salvato = $dbo->fetchOne('SELECT permessi FROM zz_permissions WHERE idgruppo = '.prepare($group_id).' AND idmodule = '.prepare($element['id']));
    $permessi = $permesso_salvato ? $permesso_salvato['permessi'] : '-';
    foreach ($permessi_disponibili as $id => $nome) {
        $attr = ($id == $permessi) ? ' selected="selected"' : '';

        $result .= '
							<option value="'.$id.'" '.$attr.'>'.$nome.'</option>';
    }

    $result .= '
						</select>
					</td>
				</tr>';

    $submenus = $element['all_children'];
    if (!empty($submenus)) {
        foreach ($submenus as $submenu) {
            $result .= menuSelection($submenu, $group_id, $depth, $permessi_disponibili, $perms_names);
        }
    }

    return $result;
}
