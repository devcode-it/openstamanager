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

switch (post('op')) {
    case 'delete-bulk':
        $count_iva = $dbo->fetchNum('SELECT id FROM `co_iva` WHERE deleted_at IS NOT NULL');
        foreach ($id_records as $id) {
            $res = $dbo->fetchNum('SELECT `co_righe_documenti`.`id` FROM `co_righe_documenti` WHERE `co_righe_documenti`.`idiva`='.prepare($id).
            ' UNION SELECT `co_righe_preventivi`.`id` FROM `co_righe_preventivi` WHERE `co_righe_preventivi`.`idiva` = '.prepare($id).
            ' UNION SELECT `co_righe_contratti`.`id` FROM `co_righe_contratti` WHERE `co_righe_contratti`.`idiva` = '.prepare($id).
            ' UNION SELECT `dt_righe_ddt`.`id` FROM `dt_righe_ddt` WHERE `dt_righe_ddt`.`idiva` = '.prepare($id).
            ' UNION SELECT `or_righe_ordini`.`id` FROM `or_righe_ordini` WHERE `or_righe_ordini`.`idiva` = '.prepare($id).
            ' UNION SELECT `mg_articoli`.`id` FROM `mg_articoli` WHERE `mg_articoli`.`idiva_vendita` = '.prepare($id).
            ' UNION SELECT `an_anagrafiche`.`idanagrafica` AS `id` FROM `an_anagrafiche` WHERE `an_anagrafiche`.`idiva_vendite` = '.prepare($id).' OR `an_anagrafiche`.`idiva_acquisti` = '.prepare($id));

            if (empty($res)) {
                $dbo->query('UPDATE `co_iva` SET deleted_at = NOW() WHERE id = '.prepare($id).Modules::getAdditionalsQuery($id_module));
            }
        }
        $count_iva = $dbo->fetchNum('SELECT id FROM `co_iva` WHERE deleted_at IS NOT NULL') - $count_iva;

        if ($count_iva > 0) {
            $msg = tr('_NUM_ tipologi_A_ iva eliminat_A_.', [
            '_NUM_' => $count_iva,
            '_A_' => ($count_iva == 1) ? 'a' : 'e', ]);

            flash()->info($msg);
        } else {
            flash()->warning(tr('Nessuna tipologia iva eliminata!'));
        }
        break;
}

$bulk = [
    'delete-bulk' => tr('Elimina selezionati'),
];

return $bulk;
