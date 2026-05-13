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

use Modules\Anagrafiche\Referente;

$operazione = filter('op');

switch ($operazione) {
    case 'addreferente':
        if (!empty(post('nome'))) {
            $nome = post('nome');
            $id_mansione = post('id_mansione');
            $id_sede = post('id_sede');

            $referente = Referente::build($id_parent, $nome, $id_mansione, $id_sede);
            $id_record = $referente->id;

            $referente->telefono = post('telefono');
            $referente->email = post('email');
            $referente->enable_newsletter = post('enable_newsletter_add');
            $referente->save();

            if (isAjaxRequest() && !empty($id_record)) {
                echo json_encode(['id' => $id_record, 'text' => $referente->nome]);
            }

            flash()->info(tr('Aggiunto nuovo referente!'));
        } else {
            flash()->warning(tr('Errore durante aggiunta del referente'));
        }

        break;

    case 'updatereferente':
        $dbo->update('an_referenti', [
            'id_anagrafica' => $id_parent,
            'nome' => post('nome'),
            'id_mansione' => post('id_mansione'),
            'telefono' => post('telefono'),
            'email' => post('email'),
            'id_sede' => post('id_sede'),
            'enable_newsletter' => post('enable_newsletter'),
        ], ['id' => $id_record]);

        flash()->info(tr('Salvataggio completato!'));

        break;

    case 'deletereferente':
        $dbo->delete('an_referenti', ['id' => $id_record]);
        $dbo->query('UPDATE co_preventivi SET id_referente = 0 WHERE `id_referente` = '.prepare($id_record));

        flash()->info(tr('Referente eliminato!'));

        break;
}
