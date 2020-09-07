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

switch (filter('op')) {
    case 'update':
        $nome = filter('nome');
        $nota = filter('nota');
        $colore = filter('colore');

        if (isset($nome) && isset($nota) && isset($colore)) {
            $dbo->query('UPDATE `mg_categorie` SET `nome`='.prepare($nome).', `nota`='.prepare($nota).', `colore`='.prepare($colore).' WHERE `id`='.prepare($id_record));
            flash()->info(tr('Salvataggio completato!'));
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio!'));
        }

        break;

    case 'add':
        $nome = filter('nome');
        $nota = filter('nota');
        $colore = filter('colore');

        $n = $dbo->fetchNum('SELECT * FROM `mg_categorie` WHERE `nome` LIKE '.prepare($nome));

        if (isset($nome)) {
            if ($n == 0) {
                $dbo->query('INSERT INTO `mg_categorie` (`nome`, `colore`, `nota`) VALUES ('.prepare($nome).', '.prepare($colore).', '.prepare($nota).')');

                $id_record = $dbo->lastInsertedID();

                if (isAjaxRequest()) {
                    echo json_encode(['id' => $id_record, 'text' => $nome]);
                }

                flash()->info(tr('Aggiunta nuova tipologia di _TYPE_', [
                    '_TYPE_' => 'categoria',
                ]));
            } else {
                flash()->error(tr('Esiste già una categoria con lo stesso nome!'));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio!'));
        }

        break;

    case 'delete':
        $id = filter('id');
        if (empty($id)) {
            $id = $id_record;
        }

        if ($dbo->fetchNum('SELECT * FROM `mg_articoli` WHERE `id_categoria`='.prepare($id).' OR `id_sottocategoria`='.prepare($id).'  OR `id_sottocategoria` IN (SELECT id FROM `mg_categorie` WHERE `parent`='.prepare($id).')') == 0) {
            $dbo->query('DELETE FROM `mg_categorie` WHERE `id`='.prepare($id));

            flash()->info(tr('Tipologia di _TYPE_ eliminata con successo!', [
                '_TYPE_' => 'categoria',
            ]));
        } else {
            flash()->error(tr('Esistono alcuni articoli collegati a questa categoria. Impossibile eliminarla.'));
        }

        break;

    case 'row':
        $nome = filter('nome');
        $nota = filter('nota');
        $colore = filter('colore');
        $original = filter('id_original');

        if (isset($nome) && isset($nota) && isset($colore)) {
            if (!empty($id_record)) {
                $dbo->query('UPDATE `mg_categorie` SET `nome`='.prepare($nome).', `nota`='.prepare($nota).', `colore`='.prepare($colore).' WHERE `id`='.prepare($id_record));
            } else {
                $dbo->query('INSERT INTO `mg_categorie` (`nome`,`nota`,`colore`, `parent`) VALUES ('.prepare($nome).', '.prepare($nota).', '.prepare($colore).', '.prepare($original).')');

                $id_record = $dbo->lastInsertedID();

                if (isAjaxRequest()) {
                    echo json_encode(['id' => $id_record, 'text' => $nome]);
                }
            }
            flash()->info(tr('Salvataggio completato!'));
            $id_record = $original;
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio!'));
        }

        break;
}
