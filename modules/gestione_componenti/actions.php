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

$path = base_dir().'/files/impianti/';

switch (post('op')) {
    case 'update':
        $nomefile = post('nomefile');
        $contenuto = post('contenuto');

        if (!file_put_contents($path.$nomefile, $contenuto)) {
            flash()->error(tr('Impossibile modificare il file!'));
        } else {
            flash()->info(tr('Informazioni salvate correttamente!'));
        }

    break;

    case 'add':
        $nomefile = str_replace('.ini', '', post('nomefile')).'.ini';
        $contenuto = post('contenuto');

        $cmp = \Util\Ini::getList($path);

        $duplicato = false;
        for ($c = 0; $c < count($cmp); ++$c) {
            if ($nomefile == $cmp[$c][0]) {
                $duplicato = true;
            }
        }

        if ($duplicato) {
            flash()->error(tr('Il file componente _FILE_ esiste già, nessun nuovo componente è stato creato!', [
                '_FILE_' => "'".$nomefile."'",
            ]));
        } elseif (!file_put_contents($path.$nomefile, $contenuto)) {
            flash()->error(tr('Impossibile creare il file!'));
        } else {
            flash()->info(tr('Componente _FILE_ aggiunto correttamente!', [
                '_FILE_' => "'".$nomefile."'",
            ]));
        }

    break;

    case 'delete':
        $nomefile = post('nomefile');

        if (!empty($nomefile)) {
            delete($path.$nomefile);

            flash()->info(tr('File _FILE_ rimosso correttamente!', [
                '_FILE_' => "'".$nomefile."'",
            ]));
        }

    break;
}
