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

use Modules\Articoli\Marca;

// Modelli (marche con parent = id_record)
$modelli = Marca::where('parent', $id_record)->get();

foreach ($modelli as $modello) {
    echo '
<tr>
    <td class="text-center">
        '.(!empty($modello->immagine) ? '<img src="'.base_path().'/files/marche/'.$modello->immagine.'" class="img-thumbnail" style="max-height: 30px;">' : '<i class="fa fa-image text-muted"></i>').'
    </td>
    <td>'.$modello->name.'</td>
    <td>'.(!empty($modello->link) ? '<a href="'.$modello->link.'" target="_blank">'.$modello->link.'</a>' : '').'</td>
    <td class="text-center">
        <span class="'.($modello->is_articolo ? 'text-success' : 'text-danger').'">
            <i class="fa fa-'.($modello->is_articolo ? 'check' : 'times').'"></i>
        </span>
    </td>
    <td class="text-center">
        <span class="'.($modello->is_impianto ? 'text-success' : 'text-danger').'">
            <i class="fa fa-'.($modello->is_impianto ? 'check' : 'times').'"></i>
        </span>
    </td>
    <td class="text-center">
        <div class="btn-group">
            <a class="btn btn-xs btn-warning" data-href="'.base_path().'/editor.php?id_module='.$id_module.'&id_record='.$modello->id.'" data-toggle="tooltip" title="'.tr('Modifica').'">
                <i class="fa fa-edit"></i>
            </a>
            <a class="btn btn-xs btn-danger ask" data-backto="record-edit" data-op="delete" data-id="'.$modello->id.'" data-toggle="tooltip" title="'.tr('Elimina').'">
                <i class="fa fa-trash"></i>
            </a>
        </div>
    </td>
</tr>';
}

// Se non ci sono modelli
if (count($modelli) == 0) {
    echo '
<tr>
    <td colspan="6" class="text-center">
        <em>'.tr('Nessun modello presente').'</em>
    </td>
</tr>';
}
