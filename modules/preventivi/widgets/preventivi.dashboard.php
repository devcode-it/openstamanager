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

include_once __DIR__.'/../../../core.php';

$id_module = Modules::get('Preventivi')['id'];

$rs = $dbo->fetchArray("SELECT *, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=co_preventivi.idanagrafica) AS ragione_sociale FROM co_preventivi WHERE idstato=(SELECT id FROM co_statipreventivi WHERE descrizione='In lavorazione') AND default_revision = 1 ORDER BY data_conclusione ASC");

if (!empty($rs)) {
    echo "
<table class='table table-hover'>
    <tr>
        <th width='70%'>Preventivo</th>
        <th width='15%'>Data inizio</th>
        <th width='15%'>Data conclusione</th>
    </tr>";

    foreach ($rs as $preventivo) {
        $data_accettazione = ($preventivo['data_accettazione'] != '0000-00-00') ? Translator::dateToLocale($preventivo['data_accettazione']) : '';
        $data_conclusione = ($preventivo['data_conclusione'] != '0000-00-00') ? Translator::dateToLocale($preventivo['data_conclusione']) : '';

        if (strtotime($preventivo['data_conclusione']) < strtotime(date('Y-m-d')) && $data_conclusione != '') {
            $attr = ' class="danger"';
        } else {
            $attr = '';
        }

        echo '<tr '.$attr.'><td><a href="'.base_path().'/editor.php?id_module='.$id_module.'&id_record='.$preventivo['id'].'">'.$preventivo['nome']."</a><br><small class='help-block'>".$preventivo['ragione_sociale'].'</small></td>';
        echo '<td '.$attr.'>'.$data_accettazione.'</td>';
        echo '<td '.$attr.'>'.$data_conclusione.'</td></tr>';
    }

    echo '
</table>';
} else {
    echo '
<p>'.tr('Non ci sono preventivi in lavorazione').'.</p>';
}
