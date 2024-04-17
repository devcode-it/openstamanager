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
        $id_marca = post('id_record');
        $name = post('name');

        // Verifico che il nome non esista già
        $n = $dbo->fetchNum('SELECT `id` FROM `my_impianti_marche` WHERE (`name`='.prepare($name).' AND `id` !='.prepare($id_marca));

        // Marca già esistente
        if ($n > 0) {
            flash()->error(tr('Marca già esistente!'));
        }
        // Marca non esistente
        else {
            $dbo->query('UPDATE `my_impianti_marche` SET `name`='.prepare($name).' WHERE `id`='.prepare($id_marca));
            flash()->info(tr('Informazioni salvate correttamente!'));
        }

        break;

    case 'add':
        $name = post('name');

        // Verifico che il nome non sia duplicato
        $n = $dbo->fetchNum('SELECT `id` FROM `my_impianti_marche` WHERE `name`='.prepare($name));

        if ($n > 0) {
            flash()->error(tr('Nome già esistente!'));
        } else {
            $query = 'INSERT INTO my_impianti_marche (`name`) VALUES ('.prepare($name).')';
            $dbo->query($query);

            $id_record = $dbo->lastInsertedID();

            if (isAjaxRequest()) {
                echo json_encode(['id' => $id_record, 'text' => $name]);
            }

            flash()->info(tr('Aggiunta una nuova marca!'));
        }

        break;

    case 'delete':
        $dbo->query('DELETE FROM `my_impianti_marche` WHERE `id`='.prepare($id_record));

        flash()->info(tr('Marca eliminata!'));

        break;
}
