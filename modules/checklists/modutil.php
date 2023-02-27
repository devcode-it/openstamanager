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

function renderChecklist($check, $level = 1, $parent = 0) {

    global $structure;

    $user = auth()->getUser();
    $enabled = $check->assignedUsers ? $check->assignedUsers->pluck('id')->search($user->id) !== false : true;

    $margin = ($level*20);

    $result = '
    <tr id="check_'.$check->id.'" data-id="'.$check->id.'" class="sortablerow sonof_'.$parent.'" >
        <td style="padding-top:0px;padding-bottom:0px;border-top:0px;">
            <table class="table" style="margin-bottom:0px;">
                <tr>';

    $result .= '
                    <td style="width:40px;text-align:center;border-top:0px;border-left:3px solid #eaeaea;">
                        <input type="checkbox" class="checkbox" data-id="'.$check->id.'" value="'.(!empty($check->checked_at) ? '1' : '0').'" '.(!empty($check->checked_at) ? 'checked' : '').' '.(!$enabled ? 'disabled' : '').'>
                    </td>'; 

    $result .= '
                    <td style="border-top:0px;">
                        <span class="text">'.$check->content.' </span>';

    if (intval($check->assignedUsers->pluck('id')->toArray())>0){
        $result .= '    <span class="label label-info pull-right" style="padding:6px 8px;" data-toggle="tooltip" title="Assegnato a '. implode(', ', $check->assignedUsers->pluck('username')->toArray()).'"><i class="fa fa-user"></i></span>';
    }else{
        $result .= '    <span class="label label-danger pull-right"  style="padding:6px 8px;">'. tr('Nessun utente assegnato').'</span>';
    }

    if(!empty($check->checked_at)){
    $result .= '
                        <span class="label label-default pull-right" style="margin-right:5px;padding:6px 8px;">'.(!empty($check->checked_at) ? tr('Verificato da _NAME_ il _DATE_', [
                            '_NAME_' => $check->checkUser->username,
                            '_DATE_' => timestampFormat($check->checked_at),
                        ]) : '').'
                        </span>';
    }

    $result .= '
                    </td>'; 

    $result .= '
                    <td style="width:40px;text-align:center;border-top:0px;">
                        <div class="input-group-btn">
                            <button class="btn btn-warning btn-xs '.(!$enabled ? 'disabled' : '').'" onclick="edit_check(\''.$check->id.'\')"><i class="fa fa-edit"></i></button>
                            <button class="btn btn-danger btn-xs '.(!$enabled ? 'disabled' : '').'" onclick="delete_check(\''.$check->id.'\')"><i class="fa fa-trash"></i></button>
                        </div>
                    </td>';



    $result .= '
                </tr>';

    if(sizeof($check->children)>0){
        $result .= '
                <tr>
                    <td colspan="4" style="padding-left:'.$margin.'px;padding-right:0px;padding-top:0px;padding-bottom:0px;border-top:0px;">
                        <table class="table" style="margin-bottom:0px;">
                            <tbody class="sort" data-sonof="'.$check->id.'">';
            $children = $structure->checks()->where('id_parent', $check->id)->orderBy('order')->get();
            foreach ($children as $child) {
                $result .= renderChecklist($child, $level + 1, $check->id);
            }
        $result .= '
                            </tbody>
                        </table>
                    </td>
                </tr>';
    }

    $result .= '
            </table>
        </td>

        <td style="width:40px;text-align:center;border-top:0px;">
            <button class="btn btn-xs btn-default handle '.(!$enabled ? 'disabled' : '').'" title="Modifica ordine delle righe" draggable="true">
                <i class="fa fa-sort"></i>
            </button>
        </td>

    </tr>';

    return $result;
}

function renderChecklistHtml($check, $level = 0)
{
    $user = auth()->getUser();
    $enabled = $check->assignedUsers ? $check->assignedUsers->pluck('id')->search($user->id) !== false : true;

    $width = 10+20*$level;

    $result = '
    <tr>
        <td class="text-center" style="width:30px;">
            '.(!empty($check->checked_at)?'<img src="'.ROOTDIR.'/templates/interventi/custom/check.png" style="width:10px;">':'').'
        </td>
        <td style="padding-left:'.$width.'px;">
            <span class="text"><b>'.$check->content.'</b>'.(!empty($check->value)?': '.$check->value:'').'</span>
        </td>
    </tr>';

    $children = $check->children;
    foreach ($children as $child) {
        $result .= renderChecklistHtml($child, $level + 1);
    }

    return $result;
}