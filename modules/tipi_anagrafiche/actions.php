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

switch (post('op')) {
    case 'update':
        $id_tipo = post('id_record');
        $descrizione = post('descrizione');

        $block = ['Cliente', 'Tecnico', 'Azienda', 'Fornitore'];
        // Nome accettato

        if (!in_array($descrizione, $block)) {
            $dbo->query('UPDATE `an_tipianagrafiche_lang` SET `name`='.prepare($descrizione).' WHERE `id_record`='.prepare($id_tipo));
            flash()->info(tr('Informazioni salvate correttamente!'));
        } else {
            // Nome non consentito
            flash()->error(tr('Nome non consentito!'));
        }

        break;

    case 'add':
        $descrizione = post('descrizione');

        if (!empty($descrizione)) {
            // Verifico che il nome non sia duplicato
            $rs = $dbo->fetchArray('SELECT `name` FROM `an_tipianagrafiche_lang` WHERE `name`='.prepare($descrizione));

            if (count($rs) > 0) {
                flash()->error(tr('Nome già esistente!'));
            } else {
                $dbo->query('INSERT INTO `an_tipianagrafiche` (`id`) VALUES (NULL)');
                $id_record = $dbo->lastInsertedID();
                $dbo->query('INSERT INTO `an_tipianagrafiche_lang` (`name`, `id_record`, `id_lang`) VALUES ('.prepare($descrizione).', '.prepare($id_record).', '.prepare(setting('Lingua')).')');

                flash()->info(tr('Nuovo tipo di anagrafica aggiunto!'));
            }
        }
        break;

    case 'delete':
        $query = 'DELETE FROM `an_tipianagrafiche` WHERE `id`='.prepare($id_record);
        $dbo->query($query);

        flash()->info(tr('Tipo di anagrafica eliminato!'));
        break;
}
