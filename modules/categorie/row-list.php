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

use Models\Module;
use Modules\Articoli\Articolo;
use Modules\Articoli\Categoria;

$subcategorie = Categoria::where('parent', '=', $id_record)->get();

foreach ($subcategorie as $sub) {
    $n_articoli = Articolo::where('id_sottocategoria', '=', $sub['id'])->count();

    echo '
<tr>
<td class="align-middle"><strong>'.$sub->getTranslation('title').'</strong></td>
<td class="text-center align-middle">
<span class="badge" style="background-color: '.$sub->colore.'; width: 20px; height: 20px; display: inline-block; vertical-align: middle;"></span> 
<span class="text-muted">'.$sub->colore.'</span>
</td>
<td class="text-center align-middle">
'.($sub->is_articolo ? '<span class="badge badge-success"><i class="fa fa-check"></i></span>' : '<span class="badge badge-secondary"><i class="fa fa-times"></i></span>').'
</td>
<td class="text-center align-middle">
'.($sub->is_impianto ? '<span class="badge badge-primary"><i class="fa fa-check"></i></span>' : '<span class="badge badge-secondary"><i class="fa fa-times"></i></span>').'
</td>
<td class="align-middle">
<small>'.nl2br(htmlentities(substr($sub->getTranslation('note'), 0, 100))).(strlen($sub->getTranslation('note')) > 100 ? '...' : '').'</small>
</td>
<td class="text-center align-middle">
<div class="btn-group">
<button type="button" class="btn btn-warning btn-sm" title="'.tr('Modifica').'" onclick="launch_modal(\''.tr('Modifica sottocategoria').'\', \''.base_path().'/add.php?id_module='.$id_module.'&id_record='.$sub->id.'&id_original='.$id_record.'\');">
<i class="fa fa-edit"></i>
</button>
<button type="button" class="btn btn-sm btn-danger ask '.(($n_articoli > 0) ? 'disabled tip' : '').'" data-backto="record-edit" data-id="'.$sub['id'].'" title="'.(($n_articoli > 0) ? 'Sottocategoria collegata a '.$n_articoli.' articoli' : tr('Elimina')).'">
<i class="fa fa-trash"></i>
</button>
</div>
</td>
</tr>';
}
