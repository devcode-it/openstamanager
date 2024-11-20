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

use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\Sede;
use Modules\Articoli\Articolo;

$rs = $dbo->fetchArray('SELECT `mg_articoli`.`id`, `mg_articoli_lang`.`title` as descrizione, `codice`, `um`, mg_scorte_sedi.threshold_qta, mg_scorte_sedi.id_sede FROM `mg_articoli` LEFT JOIN `mg_articoli_lang` ON (`mg_articoli`.`id` = `mg_articoli_lang`.`id_record` AND `mg_articoli_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') INNER JOIN `mg_scorte_sedi` ON `mg_articoli`.`id` = `mg_scorte_sedi`.`id_articolo` WHERE `attivo` = 1 AND `deleted_at` IS NULL ORDER BY `codice` ASC');
$anagrafica_azienda = Anagrafica::find(setting('Azienda predefinita'));

if (!empty($rs)) {
    echo '
<table class="table table-hover table-striped">
    <tr>
        <th>'.tr('Articolo').'</th>
        <th width="25%">'.tr('Sede').'</th>
        <th class="text-center" width="14%">'.tr('Soglia minima').'</th>
        <th class="text-center" width="14%">'.tr('Q.tà').'</th>
    </tr>';

foreach ($rs as $r) {
    $articolo = Articolo::find($r['id']);
    $giacenze = $articolo->getGiacenze();
    if ($giacenze[$r['id_sede']][0] < $r['threshold_qta']) {
        if (!empty($r['id_sede'])) {
            $sede = Sede::find($r['id_sede'])->nomesede;
        } else {
            $sede = 'Sede Legale';
        }
        echo '
        <tr>
            <td>
                '.Modules::link('Articoli', $r['id'], $r['codice'].' - '.$r['descrizione']).'
            </td>
            <td>
                '.$sede.'
            </td>
            <td class="text-right">
                '.Translator::numberToLocale($r['threshold_qta'], 'qta').' '.$articolo->um.'
            </td>
            <td class="text-right">
                '.Translator::numberToLocale($giacenze[$r['id_sede']][0], 'qta').' '.$articolo->um.'
            </td>
        </tr>';
    }
}

    echo '
</table>';
} else {
    echo '<div class=\'alert alert-info\' >'.tr('Non ci sono articoli in esaurimento.')."</div>\n";
}
