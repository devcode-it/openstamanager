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

include_once __DIR__.'/../../core.php';

use Modules\Interventi\Stato;

if (empty($record['firma_file'])) {
    $frase = tr('Anteprima e firma');
    $info_firma = '';
} else {
    $frase = tr('Nuova anteprima e firma');
    $info_firma = ''.tr('Firmato il _DATE_ alle _TIME_ da _PERSON_', [
        '_DATE_' => Translator::dateToLocale($record['firma_data']),
        '_TIME_' => Translator::timeToLocale($record['firma_data']),
        '_PERSON_' => '<b>'.$record['firma_nome'].'</b>',
    ]).'';
}

// Duplica intervento
echo '
<button type="button" class="btn btn-primary " onclick="duplicaIntervento()">
    <i class="fa fa-copy"></i> '.tr('Duplica attività').'
</button>

<button type="button" class="btn btn-primary '.(!empty($info_firma) ? 'tip' : '').'" title="'.$info_firma.'" onclick="anteprimaFirma()" '.($record['flag_completato'] ? 'disabled' : '').'>
    <i class="fa fa-'.(!empty($info_firma) ? 'refresh' : 'desktop').'"></i> '.$frase.'...
</button>

<script>
function duplicaIntervento() {
    openModal("'.tr('Duplica attività').'", "'.$module->fileurl('modals/duplicazione.php').'?id_module='.$id_module.'&id_record='.$id_record.'");
}

function anteprimaFirma() {
    openModal("'.tr('Anteprima e firma').'", "'.$module->fileurl('modals/anteprima_firma.php').'?id_module='.$id_module.'&id_record='.$id_record.'&anteprima=1");
}
</script>';

if (!$is_anagrafica_deleted) {
    // Creazione altri documenti
    $where = '';
    if (!setting('Permetti fatturazione delle attività collegate a contratti')) {
        $where = ' AND in_interventi.id_contratto IS NULL';
    }
    if (!setting('Permetti fatturazione delle attività collegate a ordini')) {
        $where .= ' AND in_interventi.id_ordine IS NULL';
    }
    if (!setting('Permetti fatturazione delle attività collegate a preventivi')) {
        $where .= ' AND in_interventi.id_preventivo IS NULL';
    }

    $is_fatturabile = $dbo->fetchOne('SELECT
        `in_interventi`.`id` FROM `in_interventi` INNER JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento`=`in_statiintervento`.`id`
    WHERE
        `in_interventi`.`id`='.prepare($id_record).' AND `in_statiintervento`.`is_fatturabile`=1 AND `in_interventi`.`id` NOT IN (SELECT `idintervento` FROM `co_righe_documenti` WHERE `idintervento` IS NOT NULL) 
        '.$where
    )['id'];

    $stati_fatturabili = Stato::where('is_fatturabile', '=', '1')->get();
    $stati = [];

    foreach ($stati_fatturabili as $stato) {
        $stati[] = $stato->getTranslation('title');
    }

    echo '
    <div class="tip btn-group" data-widget="tooltip" title="'.tr('Per creare un documento _CONTROLLO_DOCUMENTI_ lo stato dell\'attività deve essere tra: _STATE_LIST_', [
        '_CONTROLLO_DOCUMENTI_' => (!setting('Permetti fatturazione delle attività collegate a contratti') || !setting('Permetti fatturazione delle attività collegate a ordini') || !setting('Permetti fatturazione delle attività collegate a preventivi') ? tr('l\'attività non deve essere collegata ai seguenti documenti').': '.(!setting('Permetti fatturazione delle attività collegate a contratti') ? '<b>Contratti</b>' : '').(!setting('Permetti fatturazione delle attività collegate a ordini') ? ' <b>Ordini</b>' : '').(!setting('Permetti fatturazione delle attività collegate a preventivi') ? ' <b>Preventivi</b>' : '').'<br> e' : ''),
        '_STATE_LIST_' => implode(', ', (array) $stati),
    ]).'">
        <button class="btn btn-info dropdown-toggle '.($is_fatturabile ? '' : 'disabled').'" type="button" data-toggle="dropdown" aria-expanded="false">
            <i class="fa fa-magic"></i> '.tr('Crea').'
            <span class="caret"></span>
        </button>
        <div class="dropdown-menu dropdown-menu-right">
            <a class="dropdown-item" data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=fattura" data-widget="modal" data-title="'.tr('Crea fattura').'">
                <i class="fa fa-file"></i> '.tr('Fattura di vendita').'
            </a>
        </div>
    </div>';
}
