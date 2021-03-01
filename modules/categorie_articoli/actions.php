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

switch (filter('op')) {
    case 'update':
        $nome = filter('nome');
        $nota = filter('nota');
        $colore = filter('colore');

        if (isset($nome) && isset($nota) && isset($colore)) {
            $database->table('mg_categorie')
                ->where('id', '=', $id_record)
                ->update([
                    'nome' => $nome,
                    'nota' => $nota,
                    'colore' => $colore,
                ]);

            flash()->info(tr('Salvataggio completato!'));
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio!'));
        }

        break;

    case 'add':
        $nome = filter('nome');
        $nota = filter('nota');
        $colore = filter('colore');

        $id_original = filter('id_original') ?: null;

        // Ricerca corrispondenze con stesso nome
        $corrispondenze = $database->table('mg_categorie')
            ->where('nome', '=', $nome);
        if (!empty($id_original)) {
            $corrispondenze = $corrispondenze->where('parent', '=', $id_original);
        }
        $corrispondenze = $corrispondenze->get();

        // Eventuale creazione del nuovo record
        if ($corrispondenze->count() == 0) {
            $id_record = $database->table('mg_categorie')
                ->insertGetId([
                    'nome' => $nome,
                    'nota' => $nota,
                    'colore' => $colore,
                    'parent' => $id_original,
                ]);

            flash()->info(tr('Aggiunta nuova tipologia di _TYPE_', [
                '_TYPE_' => 'categoria',
            ]));
        } else {
            $id_record = $corrispondenze->first()->id;
            flash()->error(tr('Esiste già una categoria con lo stesso nome!'));
        }

        if (isAjaxRequest()) {
            echo json_encode(['id' => $id_record, 'text' => $nome]);
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
}
