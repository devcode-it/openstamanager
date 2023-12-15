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

if (!function_exists('menuSelection')) {
    function menuSelection($element, $group_id, $depth, $permessi_disponibili)
    {
        $dbo = database();
        ++$depth;

        // Permessi impostati per il gruppo
        $permesso_salvato = $dbo->fetchOne('SELECT permessi FROM zz_permissions WHERE idgruppo = '.prepare($group_id).' AND idmodule = '.prepare($element['id']));

        $permessi = $permesso_salvato ? $permesso_salvato['permessi'] : '-';

        $result = '
                    <tr>
                        <td>'.str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $depth).'<span>'.$element['title'].'</span></td>
                        <td>
                            <select name="permesso_'.$element['id'].'" id="permesso_'.$element['id'].'" class="form-control superselect openstamanager-input select-input" onchange="update_permissions('.$element['id'].', $(this).find(\'option:selected\').val(), $(this).find(\'option:selected\').data(\'color\'))">';

        foreach ($permessi_disponibili as $id => $nome) {
            switch ($id) {
                case 'rw':
                    $bgcolor = 'green';
                break;
                case 'r':
                    $bgcolor = 'orange';
                break;
                case '-':
                    $bgcolor = 'red';
                break;
                default:
                break;
            }
            $attr = ($id == $permessi) ? ' selected="selected"' : '';

            $result .= '
                                <option data-color="text-'.$bgcolor.'" _bgcolor_="'.$bgcolor.'" value="'.$id.'" '.$attr.'>'.$nome.'</option>';
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
}
