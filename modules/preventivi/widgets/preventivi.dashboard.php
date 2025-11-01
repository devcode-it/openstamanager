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

include_once __DIR__.'/../../../core.php';
use Models\Module;
use Modules\Preventivi\Preventivo;
use Modules\Preventivi\Stato;

$id_module = Module::where('name', 'Preventivi')->first()->id;

$rs = Preventivo::where('idstato', '=', Stato::where('name', 'In lavorazione')->first()->id)->where('default_revision', '=', 1)->get();

if (!empty($rs)) {
    echo "
<table class='table table-hover'>
    <tr>
        <th width='70%'>Preventivo</th>
        <th width='15%'>Data inizio</th>
        <th width='15%'>Data conclusione</th>
    </tr>";

    foreach ($rs as $preventivo) {
        $data_accettazione = ($preventivo->data_accettazione != '0000-00-00') ? Translator::dateToLocale($preventivo->data_accettazione) : '';
        $data_conclusione = ($preventivo->data_conclusione != '0000-00-00') ? Translator::dateToLocale($preventivo->data_conclusione) : '';

        if ($data_conclusione != '' && strtotime((string) $preventivo->data_conclusione) < strtotime(date('Y-m-d'))) {
            $attr = ' class="danger"';
        } else {
            $attr = '';
        }

        echo '<tr '.$attr.'><td><a href="'.base_path_osm().'/editor.php?id_module='.$id_module.'&id_record='.$preventivo->id.'">'.$preventivo->nome."</a><br><small class='help-block'>".$preventivo->ragione_sociale.'</small></td>';
        echo '<td '.$attr.'>'.$data_accettazione.'</td>';
        echo '<td '.$attr.'>'.$data_conclusione.'</td></tr>';
    }

    echo '
</table>';
} else {
    echo '
<p>'.tr('Non ci sono preventivi in lavorazione').'.</p>';
}
