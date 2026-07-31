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

use Modules\GestioneDocumentale\Documento;

switch (post('op')) {
    case 'add':
        $documento = Documento::create([
            'id_categoria' => post('id_categoria'),
            'nome' => post('nome'),
            'data' => post('data') ?: null,
        ]);
        $id_record = $documento->id;

        flash()->info(tr('Nuova documento aggiunto!'));

        break;

    case 'update':
        Documento::find($id_record)->update([
            'id_categoria' => post('id_categoria'),
            'nome' => post('nome'),
            'descrizione' => post('descrizione', true) ?: null,
            'data' => post('data') ?: null,
        ]);

        flash()->info(tr('Informazioni salvate correttamente!'));
        break;

    case 'delete':
        Documento::find($id_record)->delete();

        Uploads::deleteLinked([
            'id_module' => $id_module,
            'id_record' => $id_record,
        ]);

        flash()->info(tr('Scheda e relativi files eliminati!'));

        break;
}
