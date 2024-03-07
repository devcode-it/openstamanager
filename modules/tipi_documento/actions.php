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
use Modules\Fatture\Tipo;

switch (filter('op')) {
    case 'update':
        $descrizione = filter('descrizione');
        $dir = filter('dir');
        $codice_tipo_documento_fe = filter('codice_tipo_documento_fe');
        $predefined = post('predefined');
        $tipo_new = Tipo::where('id', '=', (new Tipo())->getByName($descrizione)->id_record)->where('dir', '=', $dir)->where('codice_tipo_documento_fe', '=', $codice_tipo_documento_fe)->first();

        if (isset($descrizione) && isset($dir) && isset($codice_tipo_documento_fe)) {
            if (!empty($tipo_new) && $tipo_new->id != $id_record){
                flash()->error(tr('Questa combinazione di nome, codice e direzione è già stata utilizzata per un altro tipo di documento.'));
            } else {
                if (!empty($predefined)) {
                    $dbo->query('UPDATE `co_tipidocumento` SET `predefined` = 0 WHERE `dir` = '.prepare($dir));
                }
                $tipo->dir = $dir;
                $tipo->codice_tipo_documento_fe = $codice_tipo_documento_fe;
                $tipo->help = filter('help');
                $tipo->predefined = $predefined;
                $tipo->enabled = post('enabled');
                $tipo->id_segment = post('id_segment');
                $tipo->name = $descrizione;
                $tipo->save();

                flash()->info(tr('Salvataggio completato!'));
            }
        }
 
        break;

    case 'add':
        $descrizione = filter('descrizione');
        $dir = filter('dir');
        $codice_tipo_documento_fe = filter('codice_tipo_documento_fe');
        $tipo_new = Tipo::where('id', '=', (new Tipo())->getByName($descrizione)->id_record)->where('dir', '=', $dir)->where('codice_tipo_documento_fe', '=', $codice_tipo_documento_fe)->first();

        if (isset($descrizione) && isset($dir) && isset($codice_tipo_documento_fe)) {
            if (!empty($tipo_new) && $tipo_new->id != $id_record){
                flash()->error(tr('Questa combinazione di nome, codice e direzione è già stata utilizzata per un altro tipo di documento.'));
            } else {
                $tipo = Tipo::build($dir, $codice_tipo_documento_fe);
                $id_record = $dbo->lastInsertedID();
                $tipo->name = $descrizione;
                $tipo->save();

                if (isAjaxRequest()) {
                    echo json_encode(['id' => $id_record, 'text' => $descrizione]);
                }

                flash()->info(tr('Aggiunta nuova tipologia di _TYPE_', [
                    '_TYPE_' => 'tipo documento',
                ]));
            }
        }

        break;

    case 'delete':
        $documenti = $dbo->fetchNum('SELECT `id` FROM `co_documenti` WHERE `idtipodocumento` ='.prepare($id_record));

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
        }

        break;
}
