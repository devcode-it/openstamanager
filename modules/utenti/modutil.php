<?php

include_once __DIR__.'/../../core.php';

function menuSelection($element, $group_id, $depth, $perms_values, $perms_names)
{
    $dbo = database();

    ++$depth;
    $name = $element['title'];

    $submenus = $element['all_children'];

    if (!empty($submenus)) {
        $temp = '';
        foreach ($submenus as $submenu) {
            $temp .= menuSelection($submenu, $group_id, $depth, $perms_values, $perms_names);
        }
    }

    $result .= '
                <tr>
					<td>'.str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $depth).$name.'</td>
                    <td>
						<select name="permesso" class="form-control superselect" onchange="update_permissions('.$element['id'].', $(this).find(\'option:selected\').val())">';
    // Permessi
    $rsp = $dbo->fetchArray('SELECT permessi FROM zz_permissions WHERE idgruppo='.prepare($group_id).' AND idmodule='.prepare($element['id']));

    if (count($rsp) == 0) {
        $permessi = '-';
    } else {
        $permessi = $rsp[0]['permessi'];
    }

    for ($i = 0; $i < count($perms_values); ++$i) {
        $attr = ($perms_values[$i] == $permessi) ? ' selected="selected"' : '';
        $result .= '
							<option value="'.$perms_values[$i].'" '.$attr.'>'.$perms_names[$i].'</option>';
    }
    $result .= '
						</select>
					</td>
				</tr>
                '.$temp;

    return $result;
}
