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
<h4>'.($modulo->title == 'Anagrafiche' ? 'Note' : $modulo->title).'</h4>
<table class="table table-hover">
    <tr>
        <th width="15%" >'.(($modulo->title == 'Anagrafiche') ? '' : tr('Riferimento')).'</th>
        <th width="20%" >'.($modulo->title == 'Anagrafiche' ? 'Tecnico' : (($modulo->title == 'Fatture di acquisto' || $modulo->title == 'Ordini fornitore' || $modulo->title == 'Ddt in entrata') ? tr('Fornitore') : tr('Cliente'))).'</th>
        <th>'.tr('Contenuto').'</th>
        <th width="20%" class="text-center">'.tr('Data di notifica').'</th>
        <th class="text-center">#</th>
    </tr>';

    foreach ($note as $nota) {
        $class = (strtotime($nota->notification_date) < strtotime(date('Y-m-d')) && !empty($nota->notification_date)) ? 'danger' : '';

        $documento = '';
        if ($modulo->title == 'Attività') {
            $documento = $dbo->fetchOne("SELECT `in_interventi`.`codice` AS numero, `ragione_sociale` FROM `zz_notes` INNER JOIN `in_interventi` ON (`in_interventi`.`id` = `zz_notes`.`id_record` AND `zz_notes`.`id_module`=(SELECT `id_record` FROM `zz_modules_lang` WHERE `title` = 'Attività' AND `id_lang` = ".prepare(setting('Lingua')).")) INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `in_interventi`.`idanagrafica` WHERE `zz_notes`.`id` = ".$nota->id);
        } elseif ($modulo->title == 'Fatture di vendita') {
            $documento = $dbo->fetchOne("SELECT `numero_esterno` AS numero, `ragione_sociale` FROM `zz_notes` INNER JOIN `co_documenti` ON (`co_documenti`.`id` = `zz_notes`.`id_record` AND `zz_notes`.`id_module`=(SELECT `id_record` FROM `zz_modules_lang` WHERE `title` = 'Fatture di vendita' AND `id_lang` = ".prepare(setting('Lingua')).")) INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `co_documenti`.`idanagrafica` WHERE `zz_notes`.`id` = ".$nota->id);
        } elseif ($modulo->title == 'Fatture di acquisto') {
            $documento = $dbo->fetchOne("SELECT `numero`, `ragione_sociale` FROM `zz_notes` INNER JOIN `co_documenti` ON (`co_documenti`.`id` = `zz_notes`.`id_record` AND `zz_notes`.`id_module`=(SELECT `id_record` FROM `zz_modules_lang` WHERE `title` = 'Fatture di acquisto' AND `id_lang` = ".prepare(setting('Lingua')).")) INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `co_documenti`.`idanagrafica` WHERE `zz_notes`.`id` = ".$nota->id);
        } elseif ($modulo->title == 'Preventivi') {
            $documento = $dbo->fetchOne("SELECT `numero`, `ragione_sociale` FROM `zz_notes` INNER JOIN `co_preventivi` ON (`co_preventivi`.`id` = `zz_notes`.`id_record` AND `zz_notes`.`id_module`=(SELECT `id_record` FROM `zz_modules_lang` WHERE `title` = 'Preventivi' AND `id_lang` = ".prepare(setting('Lingua')).")) INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `co_preventivi`.`idanagrafica` WHERE `zz_notes`.`id` = ".$nota->id);
        } elseif ($modulo->title == 'Contratti') {
            $documento = $dbo->fetchOne("SELECT `numero`, `ragione_sociale` FROM `zz_notes` INNER JOIN `co_contratti` ON (`co_contratti`.`id` = `zz_notes`.`id_record` AND `zz_notes`.`id_module`=(SELECT `id_record` FROM `zz_modules_lang` WHERE `title` = 'Contratti' AND `id_lang` = ".prepare(setting('Lingua')).")) INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `co_contratti`.`idanagrafica` WHERE `zz_notes`.`id` = ".$nota->id);
        } elseif ($modulo->title == 'Ordini cliente') {
            $documento = $dbo->fetchOne("SELECT `numero_esterno` as numero, `ragione_sociale` FROM `zz_notes` INNER JOIN `or_ordini` ON (`or_ordini`.`id` = `zz_notes`.`id_record` AND `zz_notes`.`id_module`=(SELECT `id_record` FROM `zz_modules_lang` WHERE `title` = 'Ordini cliente' AND `id_lang` = ".prepare(setting('Lingua')).")) INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `or_ordini`.`idanagrafica` WHERE `zz_notes`.`id` = ".$nota->id);
        } elseif ($modulo->title == 'Ordini fornitore') {
            $documento = $dbo->fetchOne("SELECT `numero`, `ragione_sociale` FROM `zz_notes` INNER JOIN `or_ordini` ON (`or_ordini`.`id` = `zz_notes`.`id_record` AND `zz_notes`.`id_module`=(SELECT `id_record` FROM `zz_modules_lang` WHERE `title` = 'Ordini fornitore' AND `id_lang` = ".prepare(setting('Lingua')).")) INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `or_ordini`.`idanagrafica` WHERE `zz_notes`.`id` = ".$nota->id);
        } elseif ($modulo->title == 'Ddt in uscita') {
            $documento = $dbo->fetchOne("SELECT `numero_esterno` as numero, `ragione_sociale` FROM `zz_notes` INNER JOIN `dt_ddt` ON (`dt_ddt`.`id` = `zz_notes`.`id_record` AND `zz_notes`.`id_module`=(SELECT `id_lang` FROM `zz_modules_lang` WHERE `title` = 'Ddt in uscita' AND `id_lang` = ".prepare(setting('Lingua')).")) INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `dt_ddt`.`idanagrafica` WHERE `zz_notes`.`id` = ".$nota->id);
        } elseif ($modulo->title == 'Ddt in entrata') {
            $documento = $dbo->fetchOne("SELECT `numero`, `ragione_sociale` FROM `zz_notes` INNER JOIN `dt_ddt` ON (`dt_ddt`.`id` = `zz_notes`.`id_record` AND `zz_notes`.`id_module`=(SELECT `id_record` FROM `zz_modules_lang` WHERE `title` = 'Ddt in uscita' AND `id_lang` = ".prepare(setting('Lingua')).")) INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `dt_ddt`.`idanagrafica` WHERE `zz_notes`.`id` = ".$nota->id);
        } elseif ($modulo->title == 'Articoli') {
            $documento = $dbo->fetchOne("SELECT `codice` AS numero FROM `zz_notes` INNER JOIN `mg_articoli` ON (`mg_articoli`.`id` = `zz_notes`.`id_record` AND `zz_notes`.`id_module`=(SELECT `id_record` FROM `zz_modules_lang` WHERE `title` = 'Articoli' AND `id_lang` = ".prepare(setting('Lingua')).")) WHERE `zz_notes`.`id` = ".$nota->id);
        } elseif ($modulo->title == 'Impianti') {
            $documento = $dbo->fetchOne("SELECT `matricola` AS numero, `ragione_sociale` FROM `zz_notes` INNER JOIN `my_impianti` ON (`my_impianti`.`id` = `zz_notes`.`id_record` AND `zz_notes`.`id_module`=(SELECT `id_record` FROM `zz_modules_lang` WHERE `title` = 'Impianti' AND `id_lang` = ".prepare(setting('Lingua')).")) INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `my_impianti`.`idanagrafica` WHERE `zz_notes`.`id` = ".$nota->id);
        } elseif ($modulo->title == 'Anagrafiche') {
            $documento = $dbo->fetchOne("SELECT ' ' AS numero, `ragione_sociale` FROM `zz_notes` INNER JOIN `an_anagrafiche` ON (`an_anagrafiche`.`idanagrafica` = `zz_notes`.`id_record` AND `zz_notes`.`id_module`=(SELECT `id_record` FROM `zz_modules_lang` WHERE `title` = 'Anagrafiche' AND `id_lang` = ".prepare(setting('Lingua')).")) WHERE `zz_notes`.`id` = ".$nota->id);
        } else {
            $documento['numero'] = ' ';
        }

        echo '
    <tr class="'.$class.'">
        <td>'.($documento['numero'] == null ? ' - ' : $documento['numero']).'</td>
        <td>'.$documento['ragione_sociale'].'</td>
        <td>
            <span class="pull-right"></span>

            '.$nota->content.'

            <small>'.$nota->user->nome_completo.'</small>
        </td>

        <td class="text-center">
            '.dateFormat($nota->notification_date).' ('.Carbon::parse($nota->notification_date)->diffForHumans().')
        </td>

        <td class="text-center">
            '.Modules::link($module_id, $nota->id_record, '', null, 'class="btn btn-primary btn-xs"', true, 'tab_note').'
        </td>
    </tr>';
    }

    echo '
</table>';
}
