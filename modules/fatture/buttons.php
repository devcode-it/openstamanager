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

echo '
<button type="button" class="btn btn-primary ask btn-primary" '.(empty($record['is_reversed']) ? '' : 'disabled').' data-msg="'.tr('Duplicare questa fattura?').'"  data-op="copy" data-button="'.tr('Duplica').'" data-class="btn btn-lg btn-warning" data-backto="record-edit" >
    <i class="fa fa-copy"></i> '.tr('Duplica fattura').'
</button>';

if ($module->name == 'Fatture di vendita') {
    $attributi_visibili = $record['dati_aggiuntivi_fe'] != null || $record['stato'] == 'Bozza';

    echo '
<a class="btn btn-info '.($attributi_visibili ? '' : 'disabled').'" data-toggle="modal" data-title="'.tr('Dati Fattura Elettronica').'" data-href="'.$structure->fileurl('fe/document-fe.php').'?id_module='.$id_module.'&id_record='.$id_record.'" '.($attributi_visibili ? '' : 'disabled').'>
    <i class="fa fa-file-code-o"></i> '.tr('Attributi avanzati').'
</a>';
}

if ($dir == 'entrata') {
    echo '
<div class="btn-group">
    <button type="button" class="btn btn-primary unblockable dropdown-toggle '.(((!empty($record['ref_documento']) || $record['stato'] != 'Bozza') and empty($record['is_reversed'])) ? '' : 'disabled').'" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="fa fa-magic"></i> '.tr('Crea').'
        <span class="caret"></span>
    </button>

    <ul class="dropdown-menu dropdown-menu-right">
        <li><a href="'.ROOTDIR.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'&op=nota_addebito&backto=record-edit">
            '.tr('Nota di debito').'
        </a></li>

        <li><a data-href="'.ROOTDIR.'/modules/fatture/crea_documento.php?id_module='.$id_module.'&id_record='.$id_record.'&iddocumento='.$id_record.'" data-title="Aggiungi nota di credito">
            '.tr('Nota di credito').'
        </a></li>
    </ul>
</div>';
}

if (empty($record['is_fiscale'])) {
    $msg = '<br>{[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "values": "query=SELECT id, name AS descrizione FROM zz_segments WHERE id_module=\''.$id_module.'\' AND is_fiscale = 1 ORDER BY name" ]}
    {[ "type": "date", "label": "'.tr('Data').'", "name": "data", "required": 1, "value": "-now-" ]}';

    echo '
    <button type="button" class="btn btn-warning ask" data-msg="'.tr('Vuoi trasformare questa fattura pro-forma in una di tipo fiscale?').'<br>'.prepareToField(\HTMLBuilder\HTMLBuilder::replace($msg)).'" data-op="transform" data-button="'.tr('Trasforma').'" data-class="btn btn-lg btn-warning" data-backto="record-edit">
        <i class="fa fa-upload"></i> '.tr('Trasforma in fattura fiscale').'
    </button>';
}

$modulo_prima_nota = Modules::get('Prima nota');
if (!empty($record['is_fiscale'])) {
    // Aggiunta insoluto
    $registrazione_insoluto = 1;
    if (!empty($record['riba']) && $dir == 'entrata' && in_array($record['stato'], ['Emessa', 'Parzialmente pagato', 'Pagato'])) {
        $registrazione_insoluto = 0;
    }

    echo '
        <a class="btn btn-primary '.(!empty($modulo_prima_nota) && empty($registrazione_insoluto) ? '' : 'disabled').'" data-href="'.ROOTDIR.'/add.php?id_module='.$modulo_prima_nota['id'].'&id_documenti='.$id_record.'&single=1&is_insoluto=1" data-title="'.tr('Registra insoluto').'">
            <i class="fa fa-ban fa-inverse"></i> '.tr('Registra insoluto').'
        </a>';

    // Aggiunta prima nota solo se non c'è già, se non si è in bozza o se il pagamento non è completo
    $prima_nota_presente = $dbo->fetchNum('SELECT id FROM co_movimenti WHERE iddocumento = '.prepare($id_record).' AND primanota = 1');

    $totale_scadenze = $dbo->fetchOne('SELECT SUM(da_pagare - pagato) AS differenza, SUM(da_pagare) AS da_pagare FROM co_scadenziario WHERE iddocumento = '.prepare($id_record));
    $differenza = isset($totale_scadenze) ? $totale_scadenze['differenza'] : 0;
    $registrazione_contabile = 1;
    if ($differenza != 0 || (!$prima_nota_presente && $record['stato'] == 'Emessa')) {
        $registrazione_contabile = 0;
    }

    echo '
        <a class="btn btn-primary '.(!empty($modulo_prima_nota) && empty($registrazione_contabile) ? '' : 'disabled').'" data-href="'.ROOTDIR.'/add.php?id_module='.$modulo_prima_nota['id'].'&id_documenti='.$id_record.'&single=1" data-title="'.tr('Registra contabile').'">
            <i class="fa fa-euro"></i> '.tr('Registra contabile').'
        </a>';

    if ($record['stato'] == 'Pagato') {
        echo '
        <button type="button" class="btn btn-primary ask tip" data-msg="'.tr('Se riapri questa fattura verrà azzerato lo scadenzario e la prima nota. Continuare?').'" data-method="post" data-op="reopen" data-backto="record-edit" data-title="'.tr('Riaprire la fattura?').'" title="'.tr('Riporta la fattura in stato bozza e ne elimina i movimenti contabili').'">
            <i class="fa fa-folder-open"></i> '.tr('Riapri fattura').'...
        </button>';
    }
}
