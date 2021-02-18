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
        $descrizione = filter('descrizione');
        $dir = filter('dir');
        $codice_tipo_documento_fe = filter('codice_tipo_documento_fe');

        if (isset($descrizione) && isset($dir) && isset($codice_tipo_documento_fe)) {
            if ($dbo->fetchNum('SELECT * FROM `co_tipidocumento` WHERE `dir`='.prepare($dir).' AND `codice_tipo_documento_fe`='.prepare($codice_tipo_documento_fe).' AND `id`!='.prepare($id_record)) == 0) {
                $predefined = post('predefined');
                if (!empty($predefined)) {
                    $dbo->query('UPDATE co_tipidocumento SET predefined = 0 WHERE dir = '.prepare($dir));
                }

                $dbo->update('co_tipidocumento', [
                    'descrizione' => $descrizione,
                    'dir' => $dir,
                    'codice_tipo_documento_fe' => $codice_tipo_documento_fe,
                    'help' => filter('help'),
                    'predefined' => $predefined,
                    'enabled' => post('enabled'),
                ], ['id' => $id_record]);

                flash()->info(tr('Salvataggio completato!'));
            } else {
                flash()->error(tr("E' già presente una tipologia di _TYPE_ con la stessa combinazione di direzione e tipo documento FE", [
                    '_TYPE_' => 'tipo documento',
                ]));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio'));
        }

        break;

    case 'add':
        $descrizione = filter('descrizione');
        $dir = filter('dir');
        $codice_tipo_documento_fe = filter('codice_tipo_documento_fe');

        if (isset($descrizione) && isset($dir) && isset($codice_tipo_documento_fe)) {
            if ($dbo->fetchNum('SELECT * FROM `co_tipidocumento` WHERE `dir`='.prepare($dir).' AND `codice_tipo_documento_fe`='.prepare($codice_tipo_documento_fe)) == 0) {
                $dbo->insert('co_tipidocumento', [
                    'descrizione' => $descrizione,
                    'dir' => $dir,
                    'codice_tipo_documento_fe' => $codice_tipo_documento_fe,
                ]);
                $id_record = $dbo->lastInsertedID();

                if (isAjaxRequest()) {
                    echo json_encode(['id' => $id_record, 'text' => $descrizione]);
                }

                flash()->info(tr('Aggiunta nuova tipologia di _TYPE_', [
                    '_TYPE_' => 'tipo documento',
                ]));
            } else {
                flash()->error(tr("E' già presente una tipologia di _TYPE_ con la stessa combinazione di direzione e tipo documento FE", [
                    '_TYPE_' => 'tipo documento',
                ]));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio'));
        }

        break;

    case 'delete':
        $documenti = $dbo->fetchNum('SELECT id FROM co_documenti WHERE idtipodocumento ='.prepare($id_record));

        if (isset($id_record) && empty($documenti)) {
            $dbo->query('DELETE FROM `co_tipidocumento` WHERE `id`='.prepare($id_record));
            flash()->info(tr('Tipologia di _TYPE_ eliminata con successo.', [
                '_TYPE_' => 'tipo documento',
            ]));
        } else {
            $dbo->update('co_tipidocumento', [
                'deleted_at' => date(),
                'predefined' => 0,
                'enabled' => 0,
            ], ['id' => $id_record]);

            flash()->info(tr('Tipologia di _TYPE_ eliminata con successo.', [
                '_TYPE_' => 'tipo documento',
            ]));

            //flash()->error(tr('Sono presenti dei documenti collegati a questo tipo documento'));
        }

        break;
}
