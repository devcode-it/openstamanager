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

if (get('op') == 'get_costo_orario') {
    $idtipointervento = get('idtipointervento');

    $rs = $dbo->fetchArray('SELECT `costo_orario` FROM `in_tipiintervento` WHERE `id`='.prepare($idtipointervento));
    echo $rs[0]['costo_orario'];
}

if (get('op') == 'get_costo_ore') {
    $idtipointervento = get('idtipointervento');
    $id_record = get('id_record');

    // Recupera il costo ore dal contratto
    $rs = $dbo->fetchArray('SELECT `costo_ore` FROM `co_contratti_tipiintervento` WHERE `idcontratto`='.prepare($id_record).' AND `idtipointervento`='.prepare($idtipointervento));
    
    if (!empty($rs)) {
        echo json_encode([
            'costo_ore' => $rs[0]['costo_ore'],
        ]);
    } else {
        // Se non esiste nel contratto, recupera il costo orario standard
        $rs_standard = $dbo->fetchArray('SELECT `costo_orario` FROM `in_tipiintervento` WHERE `id`='.prepare($idtipointervento));
        echo json_encode([
            'costo_ore' => $rs_standard[0]['costo_orario'],
        ]);
    }
}

if (get('op') == 'tipiintervento_abilitati') {
    $id_record = filter('id_record');

    // Recupera i tipi di attività abilitati per il contratto
    $rs = $dbo->fetchArray('SELECT `in_tipiintervento`.`id`, `in_tipiintervento_lang`.`title` FROM `co_contratti_tipiintervento` INNER JOIN `in_tipiintervento` ON `in_tipiintervento`.`id` = `co_contratti_tipiintervento`.`idtipointervento` LEFT JOIN `in_tipiintervento_lang` ON `in_tipiintervento_lang`.`id_record` = `in_tipiintervento`.`id` AND `in_tipiintervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).' WHERE `co_contratti_tipiintervento`.`idcontratto` = '.prepare($id_record).' AND `co_contratti_tipiintervento`.`is_abilitato` = 1 ORDER BY `in_tipiintervento_lang`.`title`');

    echo json_encode($rs);
}
