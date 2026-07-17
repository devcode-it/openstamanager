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

use Modules\Mansioni\Mansione;
use Modules\Anagrafiche\Referente;

switch (post('op')) {
    case 'update':
        $nome = post('nome');

        $exists = Mansione::where('nome', $nome)->where('id', '!=', $id_record)->exists();
        if (!$exists) {
            Mansione::find($id_record)->update(['nome' => $nome]);
            flash()->info(tr('Salvataggio completato.'));
        } else {
            flash()->error(tr("E' già presente una _TYPE_ con lo stesso nome", [
                '_TYPE_' => 'mansione',
            ]));
        }

        break;

    case 'add':
        $nome = post('nome');

        $exists = Mansione::where('nome', $nome)->exists();
        if (!$exists) {
            $mansione = Mansione::create(['nome' => $nome]);
            $id_record = $mansione->id;

            if (isAjaxRequest()) {
                echo json_encode(['id' => $id_record, 'text' => $nome]);
            }

            flash()->info(tr('Aggiunta nuova _TYPE_', [
                '_TYPE_' => 'mansione',
            ]));
        } else {
            flash()->error(tr("E' già presente una _TYPE_ con lo stesso nome", [
                '_TYPE_' => 'mansione',
            ]));
        }

        break;

    case 'delete':
        $has_referenti = Referente::where('id_mansione', $id_record)->exists();

        if ((!empty($id_record)) && !$has_referenti) {
            Mansione::find($id_record)->delete();
            flash()->info(tr('_TYPE_ eliminata con successo.', [
                '_TYPE_' => 'Mansione',
            ]));
        } else {
            flash()->error(tr('Sono presenti dei referenti collegati a questa mansione.'));
        }

        break;
}
