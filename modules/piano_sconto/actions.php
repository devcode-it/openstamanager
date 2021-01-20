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

use Modules\PianiSconto\PianoSconto;

switch (post('op')) {
    case 'update':
        $listino->nome = post('nome');
        $listino->note = post('note');

        $listino->percentuale = post('prc_guadagno');
        $listino->percentuale_combinato = post('prc_combinato');

        $listino->save();

        flash()->info(tr('Informazioni salvate correttamente!'));
        break;

    case 'add':
        $listino = PianoSconto::build(post('nome'), post('prc_guadagno'));

        $listino->percentuale_combinato = post('prc_combinato');

        $listino->save();
        $id_record = $listino->id;

        flash()->info(tr('Nuovo listino aggiunto!'));
        break;

    case 'delete':
        $listino->delete();

        flash()->info(tr('Listino eliminato!'));
        break;
}
