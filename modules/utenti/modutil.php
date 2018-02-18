<?php

include_once __DIR__.'/../../core.php';

function menuSelection($element, $depth, $perms_values, $perms_names)
{
    global $rootdir;
    global $id_module;
    global $id_record;

    $dbo = Database::getConnection();

    ++$depth;
    $name = $element['title'];

    $submenus = $dbo->fetchArray("SELECT * FROM zz_modules WHERE enabled='1' AND parent=".prepare($element['id']).' ORDER BY `order` ASC');

    if ($submenus != null && count($submenus) != 0) {
        $temp = '';
        foreach ($submenus as $submenu) {
            $temp .= menuSelection($submenu, $depth, $perms_values, $perms_names);
        }
    }
    $result .= '
                <tr>
					<td>'.str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $depth).$name.'</td>
                    <td>
						<select name="permesso" class="form-control superselect" onchange="update_permissions('.$element['id'].', $(this).find(\'option:selected\').val())">';
    // Permessi
    $rsp = $dbo->fetchArray('SELECT permessi FROM zz_permissions WHERE idgruppo='.prepare($id_record).' AND idmodule='.prepare($element['id']));

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
