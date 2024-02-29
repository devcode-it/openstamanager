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

use Modules\AttributiCombinazioni\Attributo;
use Modules\AttributiCombinazioni\ValoreAttributo;

switch (filter('op')) {
    case 'add':
        $nome = post('nome');
        $esistente = (new Attributo())->getByName(post('nome'));

        if (!$esistente) {
            $attributo = Attributo::build();
            $attributo->save();

            $id_record = $attributo->id;

            $database->query('INSERT INTO `mg_attributi_lang` (`id_record`, `id_lang`, `name`, `title`) VALUES ('.$id_record.', '.setting('Lingua').', \''.post('nome').'\', \''.post('titolo').'\')');

            flash()->info(tr('Nuovo attributo creato correttamente!'));
        } else {
            flash()->error(tr('Attributo esistente con lo stesso nome!'));
        }

        break;

    case 'update':

        $attributo->save();

        flash()->info(tr('Attributo aggiornato correttamente!'));

        break;

    case 'delete':
        $attributo->delete();

        flash()->info(tr('Attributo rimosso correttamente!'));

        break;

    case 'gestione-valore':
        $id_valore = filter('id_valore');
        $nome = post('nome');

        if (!empty($id_valore)) {
            $valore = ValoreAttributo::find($id_valore);
            $valore->nome = $nome;
            $valore->save();
        } else {
            $valore = ValoreAttributo::build($attributo, $nome);
        }

        flash()->info(tr('Valore aggiornato correttamente!'));

        break;

    case 'rimuovi-valore':
        $id_valore = filter('id_valore');

        if (!empty($id_valore)) {
            $valore = ValoreAttributo::find($id_valore);
            $valore->delete();
        }

        flash()->info(tr('Valore rimosso correttamente!'));

        break;
}
