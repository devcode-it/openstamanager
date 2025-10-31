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

use Carbon\Carbon;
use Models\Module;

if (!empty($is_title_request)) {
    echo tr('Note interne');

    return;
}

$notes = collect();

$moduli = Module::getAll()->where('permission', '<>', '-');
foreach ($moduli as $modulo) {
    $note = $modulo->notes()->whereNotNull('notification_date')->orderBy('notification_date', 'asc')->get();
    $notes = $notes->merge($note);
}

if (!empty($is_number_request)) {
    echo $notes->count();

    return;
}

if ($notes->count() < 1) {
    echo '
<p>'.tr('Non ci sono note da notificare').'.</p>';

    return;
}

$moduli = $notes->groupBy('id_module')->sortBy('notification_date');
foreach ($moduli as $module_id => $note) {
    $modulo = Module::find($module_id);

    echo '
<h4>'.($modulo->name == 'Anagrafiche' ? 'Note' : $modulo->getTranslation('title')).'</h4>
<div class="table-responsive">
<table class="table table-hover notification-table">
    <thead>
        <tr>
            <th width="15%" >'.(($modulo->name == 'Anagrafiche') ? '' : tr('Riferimento')).'</th>
            <th width="20%" >'.($modulo->name == 'Anagrafiche' ? 'Tecnico' : (($modulo->name == 'Fatture di acquisto' || $modulo->name == 'Ordini fornitore' || $modulo->name == 'Ddt in entrata') ? tr('Fornitore') : tr('Cliente'))).'</th>
            <th>'.tr('Contenuto').'</th>
            <th width="20%" class="text-center">'.tr('Data di notifica').'</th>
            <th class="text-center">#</th>
        </tr>
    </thead>
    <tbody>';

    foreach ($note as $nota) {
        $class = (strtotime((string) $nota->notification_date) < strtotime(date('Y-m-d')) && !empty($nota->notification_date)) ? 'danger' : '';

        $documento = [];
        if ($modulo->name == 'Interventi') {
            $documento = $dbo->fetchOne("SELECT `in_interventi`.`codice` AS numero, `ragione_sociale` FROM `zz_notes` INNER JOIN `in_interventi` ON (`in_interventi`.`id` = `zz_notes`.`id_record` AND `zz_notes`.`id_module`=(SELECT `id` FROM `zz_modules` WHERE `name` = \"Interventi\")) INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `in_interventi`.`idanagrafica` WHERE `zz_notes`.`id` = ".$nota->id);
        } elseif ($modulo->name == 'Fatture di vendita') {
            $documento = $dbo->fetchOne("SELECT `numero_esterno` AS numero, `ragione_sociale` FROM `zz_notes` INNER JOIN `co_documenti` ON (`co_documenti`.`id` = `zz_notes`.`id_record` AND `zz_notes`.`id_module`=(SELECT `id` FROM `zz_modules` WHERE `name` = \"Fatture di vendita\")) INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `co_documenti`.`idanagrafica` WHERE `zz_notes`.`id` = ".$nota->id);
        } elseif ($modulo->name == 'Fatture di acquisto') {
            $documento = $dbo->fetchOne("SELECT `numero`, `ragione_sociale` FROM `zz_notes` INNER JOIN `co_documenti` ON (`co_documenti`.`id` = `zz_notes`.`id_record` AND `zz_notes`.`id_module`=(SELECT `id` FROM `zz_modules` WHERE `name` = \"Fatture di acquisto\")) INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `co_documenti`.`idanagrafica` WHERE `zz_notes`.`id` = ".$nota->id);
        } elseif ($modulo->name == 'Preventivi') {
            $documento = $dbo->fetchOne("SELECT `numero`, `ragione_sociale` FROM `zz_notes` INNER JOIN `co_preventivi` ON (`co_preventivi`.`id` = `zz_notes`.`id_record` AND `zz_notes`.`id_module`=(SELECT `id` FROM `zz_modules` WHERE `name` = \"Preventivi\")) INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `co_preventivi`.`idanagrafica` WHERE `zz_notes`.`id` = ".$nota->id);
        } elseif ($modulo->name == 'Contratti') {
            $documento = $dbo->fetchOne("SELECT `numero`, `ragione_sociale` FROM `zz_notes` INNER JOIN `co_contratti` ON (`co_contratti`.`id` = `zz_notes`.`id_record` AND `zz_notes`.`id_module`=(SELECT `id` FROM `zz_modules` WHERE `name` = \"Contratti\")) INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `co_contratti`.`idanagrafica` WHERE `zz_notes`.`id` = ".$nota->id);
        } elseif ($modulo->name == 'Ordini cliente') {
            $documento = $dbo->fetchOne("SELECT `numero_esterno` as numero, `ragione_sociale` FROM `zz_notes` INNER JOIN `or_ordini` ON (`or_ordini`.`id` = `zz_notes`.`id_record` AND `zz_notes`.`id_module`=(SELECT `id` FROM `zz_modules` WHERE `name` = \"Ordini cliente\")) INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `or_ordini`.`idanagrafica` WHERE `zz_notes`.`id` = ".$nota->id);
        } elseif ($modulo->name == 'Ordini fornitore') {
            $documento = $dbo->fetchOne("SELECT `numero`, `ragione_sociale` FROM `zz_notes` INNER JOIN `or_ordini` ON (`or_ordini`.`id` = `zz_notes`.`id_record` AND `zz_notes`.`id_module`=(SELECT `id` FROM `zz_modules` WHERE `name` = \"Ordini fornitore\")) INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `or_ordini`.`idanagrafica` WHERE `zz_notes`.`id` = ".$nota->id);
        } elseif ($modulo->name == 'Ddt in uscita') {
            $documento = $dbo->fetchOne("SELECT `numero_esterno` as numero, `ragione_sociale` FROM `zz_notes` INNER JOIN `dt_ddt` ON (`dt_ddt`.`id` = `zz_notes`.`id_record` AND `zz_notes`.`id_module`=(SELECT `id` FROM `zz_modules` WHERE `name` = \"Ddt in uscita\")) INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `dt_ddt`.`idanagrafica` WHERE `zz_notes`.`id` = ".$nota->id);
        } elseif ($modulo->name  == 'Ddt in entrata') {
            $documento = $dbo->fetchOne("SELECT `numero`, `ragione_sociale` FROM `zz_notes` INNER JOIN `dt_ddt` ON (`dt_ddt`.`id` = `zz_notes`.`id_record` AND `zz_notes`.`id_module`=(SELECT `id` FROM `zz_modules` WHERE `name` = \"Ddt in entrata\")) INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `dt_ddt`.`idanagrafica` WHERE `zz_notes`.`id` = ".$nota->id);
        } elseif ($modulo->name == 'Articoli') {
            $documento = $dbo->fetchOne("SELECT `codice` AS numero FROM `zz_notes` INNER JOIN `mg_articoli` ON (`mg_articoli`.`id` = `zz_notes`.`id_record` AND `zz_notes`.`id_module`=(SELECT `id` FROM `zz_modules` WHERE `name` = \"Articoli\")) WHERE `zz_notes`.`id` = ".$nota->id);
        } elseif ($modulo->name == 'Impianti') {
            $documento = $dbo->fetchOne("SELECT `matricola` AS numero, `ragione_sociale` FROM `zz_notes` INNER JOIN `my_impianti` ON (`my_impianti`.`id` = `zz_notes`.`id_record` AND `zz_notes`.`id_module`=(SELECT `id` FROM `zz_modules` WHERE `name` = \"Impianti\")) INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `my_impianti`.`idanagrafica` WHERE `zz_notes`.`id` = ".$nota->id);
        } elseif ($modulo->name == 'Anagrafiche') {
            $documento = $dbo->fetchOne("SELECT ' ' AS numero, `ragione_sociale` FROM `zz_notes` INNER JOIN `an_anagrafiche` ON (`an_anagrafiche`.`idanagrafica` = `zz_notes`.`id_record` AND `zz_notes`.`id_module`=(SELECT `id` FROM `zz_modules` WHERE `name` = \"Anagrafiche\")) WHERE `zz_notes`.`id` = ".$nota->id);
        } elseif ($modulo->name == 'Scadenzario') {
            $documento = $dbo->fetchOne("SELECT `co_scadenziario`.`tipo` AS numero , `ragione_sociale` FROM `zz_notes` INNER JOIN `co_scadenziario` ON (`co_scadenziario`.`id` = `zz_notes`.`id_record` AND `zz_notes`.`id_module`=(SELECT `id` FROM `zz_modules` WHERE `name` = \"Scadenzario\")) INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `co_scadenziario`.`idanagrafica` WHERE `zz_notes`.`id` = ".$nota->id);
        } else {
            $documento['numero'] = ' ';
        }

        echo '
        <tr class="'.$class.'">
            <td class="notification-reference">'.($documento['numero'] == null ? ' - ' : $documento['numero']).'</td>
            <td class="notification-client">'.$documento['ragione_sociale'].'</td>
            <td class="notification-content">
                <div class="notification-text">
                    '.$nota->content.'
                </div>
                <div class="notification-author">
                    <small>'.$nota->user->nome_completo.'</small>
                </div>
            </td>
            <td class="text-center notification-date">
                '.dateFormat($nota->notification_date).' ('.Carbon::parse($nota->notification_date)->diffForHumans().')
            </td>
            <td class="text-center notification-action">
                '.Modules::link($module_id, $nota->id_record, '', null, 'class="btn btn-primary btn-xs"', true, 'tab_note').'
            </td>
        </tr>';
    }

    echo '
    </tbody>
</table>
</div>';
}
