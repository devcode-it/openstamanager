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

$rs = Preventivo::where('id_stato', '=', Stato::where('name', 'In lavorazione')->first()->id)->where('default_revision', '=', 1)->get();

if (count($rs) > 0) {
    echo "
<table class='table table-hover'>
    <tr>
        <th width='35%'>Preventivo</th>
        <th width='15%'>Cliente</th>
        <th width='10%'>Agente</th>
        <th width='10%'>Imponibile</th>
        <th width='10%'>Totale documento</th>
        <th width='10%'>Data inizio</th>
        <th width='10%'>Data conclusione</th>
    </tr>";

    foreach ($rs as $preventivo) {
        $data_accettazione = ($preventivo->data_accettazione != '0000-00-00') ? Translator::dateToLocale($preventivo->data_accettazione) : '';
        $data_conclusione = ($preventivo->data_conclusione != '0000-00-00') ? Translator::dateToLocale($preventivo->data_conclusione) : '';

        if ($data_conclusione != '' && strtotime((string) $preventivo->data_conclusione) < strtotime(date('Y-m-d'))) {
            $attr = ' class="danger"';
        } else {
            $attr = '';
        }

        $agente = Modules\Anagrafiche\Anagrafica::find($preventivo->id_agente);
        $imponibile = number_format($preventivo->totale_imponibile, 2, ',', '.');
        $totale = number_format($preventivo->totale, 2, ',', '.');

        echo '<tr '.$attr.'>';
        echo '<td><a href="'.base_path_osm().'/editor.php?id_module='.$id_module.'&id_record='.$preventivo->id.'">'.$preventivo->nome.'</a></td>';
        echo '<td>'.$preventivo->anagrafica->ragione_sociale.'</td>';
        echo '<td>'.($agente ? $agente->ragione_sociale : '').'</td>';
        echo '<td>'.$imponibile.'</td>';
        echo '<td>'.$totale.'</td>';
        echo '<td '.$attr.'>'.$data_accettazione.'</td>';
        echo '<td '.$attr.'>'.$data_conclusione.'</td>';
        echo '</tr>';
    }

    echo '
</table>';
} else {
    echo '
<p>'.tr('Non ci sono preventivi in lavorazione').'.</p>';
}
