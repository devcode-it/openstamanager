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

        $is_parent = ($depth === 0);
        $parent_class = $is_parent ? 'module-parent' : '';
        $indent = str_repeat('&nbsp;&nbsp;', $depth);

        $icon = !empty($element['icon']) ? '<i class="'.$element['icon'].' text-primary"></i> ' : '';

        $result = '
                    <tr class="'.$parent_class.'">
                        <td style="padding: 4px 8px;">'.$indent.$icon.'<span>'.$element['title'].'</span></td>
                        <td style="padding: 4px 8px; width: 200px;">
                            <select name="permesso_'.$element['id'].'" id="permesso_'.$element['id'].'" class="form-control form-control-sm superselect openstamanager-input select-input" style="width: 100%;" onchange="update_permissions('.$element['id'].', $(this).find(\'option:selected\').val(), $(this).find(\'option:selected\').data(\'color\'))">';

        foreach ($permessi_disponibili as $id => $nome) {
            switch ($id) {
                case 'rw':
                    $bgcolor = 'green';
                    $icon_class = 'fa fa-check-circle';
                    break;
                case 'r':
                    $bgcolor = 'orange';
                    $icon_class = 'fa fa-eye';
                    break;
                case '-':
                    $bgcolor = 'red';
                    $icon_class = 'fa fa-ban';
                    break;
                default:
                    $bgcolor = '';
                    $icon_class = '';
                    break;
            }
            $attr = ($id == $permessi) ? ' selected="selected"' : '';

            $result .= '
                                <option data-color="text-'.$bgcolor.'" _bgcolor_="'.$bgcolor.'" value="'.$id.'" '.$attr.'><i class="'.$icon_class.'"></i> '.$nome.'</option>';
        }

        $result .= '
                            </select>
                        </td>
                    </tr>';

        $submenus = $element['all_children'];
        if (!empty($submenus)) {
            foreach ($submenus as $submenu) {
                $result .= menuSelection($submenu, $group_id, $depth, $permessi_disponibili);
            }
        }

        return $result;
    }
}
