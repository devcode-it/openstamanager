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

function renderChecklist($check, $level = 0)
{
    $user = auth()->getUser();
    $enabled = $check->assignedUsers ? $check->assignedUsers->pluck('id')->search($user->id) !== false : true;

    $result = '
<li id="check_'.$check->id.'" class="check-item'.(!empty($check->checked_at) ? ' done' : '').'" '.(!$enabled ? 'style="opacity: 0.4"' : '').' data-id="'.$check->id.'">
    <input type="checkbox" value="'.(!empty($check->checked_at) ? '1' : '0').'" '.(!empty($check->checked_at) ? 'checked' : '').'>

    <span class="text">'.$check->content.'</span>
    <span class="badge">'.(!empty($check->checked_at) ? timestampFormat($check->checked_at).' - '.$check->checkUser->username : '').'</span>';

    if ($level == 0) {
        $result .= '
    <span class="handle pull-right">
        <i class="fa fa-ellipsis-v"></i>
        <i class="fa fa-ellipsis-v"></i>
    </span>';
    }

    if (empty($check->user) || $check->user->id == $user->id) {
        $result .= '
    <div class="tools">
        <i class="fa fa-trash-o check-delete"></i>
    </div>';
    }

    $result .= '
    <ul class="todo-list">';

    $children = $check->children;
    foreach ($children as $child) {
        $result .= renderChecklist($child, $level + 1);
    }

    $result .= '
    </ul>
</li>';

    return $result;
}
