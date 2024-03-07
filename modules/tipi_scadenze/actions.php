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
use Models\Module;

switch (filter('op')) {
    case 'update':
        $descrizione = filter('descrizione');
        $nome = filter('nome');

        if (isset($nome)) {
            // Se non esiste già una tipo di scadenza con lo stesso nome
            $nome_new = $dbo->fetchOne('SELECT * FROM `co_tipi_scadenze` LEFT JOIN `co_tipi_scadenze_lang` ON (`co_tipi_scadenze_lang`.`id_record` = `co_tipi_scadenze`.`id` AND `co_tipi_scadenze_lang`.`id_lang` = '.prepare(setting('Lingua')).') WHERE `name` =  '.prepare($nome).' AND `co_tipi_scadenze_lang`.`id_record` != '.prepare($id_record));
            if (empty($nome_new)) {
                // nome_prev
                $nome_prev = $dbo->fetchOne('SELECT `name` AS nome_prev FROM `co_tipi_scadenze` LEFT JOIN `co_tipi_scadenze_lang` ON (`co_tipi_scadenze_lang`.`id_record` = `co_tipi_scadenze`.`id` AND `co_tipi_scadenze_lang`.`id_lang` = "'.prepare(setting('Lingua')).'") WHERE `co_tipi_scadenze`.`id`='.prepare($id_record))['nome_prev'];

                $dbo->update('co_tipi_scadenze_lang', [
                    'name' => $nome,
                    'description' => $descrizione,
                ], ['id_record' => $id_record, 'id_lang' => setting('Lingua')]);

                // aggiorno anche il segmento
                $dbo->update('zz_segments', [
                    'clause' => 'co_scadenziario.tipo="'.$nome.'"',
                    'name' => 'Scadenzario '.$nome,
                ], [
                    'clause' => 'co_scadenziario.tipo="'.$nome_prev.'"',
                    'name' => 'Scadenzario '.$nome_prev,
                    'id_module' => (new Module())->getByName('Scadenzario')->id_record,
                ]);

                flash()->info(tr('Salvataggio completato!'));
            } else {
                flash()->error(tr("E' già presente una tipologia di _TYPE_ con nome: _NOME_", [
                    '_TYPE_' => 'scadenza',
                    '_NOME_' => $nome,
                ]));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio'));
        }

        break;

    case 'add':
        $descrizione = filter('descrizione');
        $nome = filter('nome');

        if (isset($nome)) {
            // Se non esiste già un tipo di scadenza con lo stesso nome
            if ($dbo->fetchNum('SELECT * FROM `co_tipi_scadenze` LEFT JOIN `co_tipi_scadenze_lang` ON (`co_tipi_scadenze_lang`.`id_record` = `co_tipi_scadenze`.`id` AND `co_tipi_scadenze_lang`.`id_lang` = "'.prepare(setting('Lingua')).'") WHERE `name`='.prepare($nome)) == 0) {
                $dbo->insert('co_tipi_scadenze', [
                    'created_at' => 'NOW()',
                ]);
                $id_record = $dbo->lastInsertedID();
                $dbo->insert('co_tipi_scadenze_lang', [
                    'name' => $nome,
                    'description' => $descrizione,
                    'id_record' => $id_record,
                    'id_lang' => setting('Lingua')
                ]);

                // Aggiungo anche il segmento
                $dbo->insert('zz_segments', [
                    'id_module' => (new Module())->getByName('Scadenzario')->id_record,
                    'name' => 'Scadenzario '.$nome,
                    'clause' => 'co_scadenziario.tipo="'.$nome.'"',
                    'position' => 'WHR',
                ]);

                if (isAjaxRequest()) {
                    echo json_encode(['id' => $id_record, 'text' => $descrizione]);
                }

                flash()->info(tr('Aggiunta nuova tipologia di _TYPE_', [
                    '_TYPE_' => 'scadenza',
                ]));
            } else {
                flash()->error(tr("E' già presente una tipologia di _TYPE_ con nome: _NOME_", [
                    '_TYPE_' => 'scadenza',
                    '_NOME_' => $nome,
                ]));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio'));
        }

        break;

    case 'delete':
        $documenti = $dbo->fetchNum('SELECT `id` FROM `co_scadenziario` WHERE `tipo` = (SELECT `name` FROM `co_tipi_scadenze` LEFT JOIN `co_tipi_scadenze_lang` ON (`co_tipi_scadenze_lang`.`id_record` = `co_tipi_scadenze`.`id` AND `co_tipi_scadenze_lang`.`id_lang` = "'.prepare(setting('Lingua')).'") WHERE `co_tipi_scadenze`.`id` = '.prepare($id_record).')');

        if (isset($id_record) && empty($documenti)) {
            $dbo->query('DELETE FROM `co_tipi_scadenze` WHERE `can_delete` = 1 AND `id`='.prepare($id_record));
            flash()->info(tr('Tipologia di _TYPE_ eliminata con successo.', [
                '_TYPE_' => 'scadenza',
            ]));
        } else {
            flash()->error(tr('Sono presenti delle scadenze collegate a questo tipo di scadenza'));
        }

        break;
}
