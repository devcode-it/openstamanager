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

include_once __DIR__.'/../../core.php';

if (empty($record['firma_file'])) {
    $frase = tr('Anteprima e firma');
    $info_firma = '';
} else {
    $frase = tr('Nuova anteprima e firma');
    $info_firma = '<span class="label label-success"><i class="fa fa-edit"></i> '.tr('Firmato il _DATE_ alle _TIME_ da _PERSON_', [
        '_DATE_' => Translator::dateToLocale($record['firma_data']),
        '_TIME_' => Translator::timeToLocale($record['firma_data']),
        '_PERSON_' => '<b>'.$record['firma_nome'].'</b>',
    ]).'</span>';
}

// Duplica intervento
echo'
<button type="button" class="btn btn-primary " onclick="duplicaIntervento()">
    <i class="fa fa-copy"></i> '.tr('Duplica attività').'
</button>

<!-- EVENTUALE FIRMA GIA\' EFFETTUATA -->
'.$info_firma.'

<button type="button" class="btn btn-primary" onclick="anteprimaFirma()" '.($record['flag_completato'] ? 'disabled' : '').'>
    <i class="fa fa-desktop"></i> '.$frase.'...
</button>

<script>
function duplicaIntervento() {
    openModal("'.tr('Duplica attività').'", "'.$module->fileurl('modals/duplicazione.php').'?id_module='.$id_module.'&id_record='.$id_record.'");
}

function anteprimaFirma() {
    openModal("'.tr('Anteprima e firma').'", "'.$module->fileurl('modals/anteprima_firma.php').'?id_module='.$id_module.'&id_record='.$id_record.'&anteprima=1");
}
</script>';

// Creazione altri documenti
// TODO: trasformazione delle sessioni in righe relative
/*
echo '
<div class="btn-group">
    <button class="btn btn-info dropdown-toggle '.(!$record['flag_completato'] ? 'disabled' : '').'" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
        <i class="fa fa-magic"></i> '.tr('Crea').'
        <span class="caret"></span>
    </button>
    <ul class="dropdown-menu dropdown-menu-right">
        <li>
            <a data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=fattura" data-toggle="modal" data-title="'.tr('Crea fattura').'">
                <i class="fa fa-file"></i> '.tr('Fattura').'
            </a>
        </li>
    </ul>
</div>';
*/
