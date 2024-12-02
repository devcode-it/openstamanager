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

use Modules\Anagrafiche\Anagrafica;
use Plugins\AssicurazioneCrediti\AssicurazioneCrediti;

include_once __DIR__.'/../../core.php';

$operazione = filter('op');

switch ($operazione) {
    case 'add':
        $anagrafica = Anagrafica::find($id_parent);
        $assicurazione_crediti = AssicurazioneCrediti::build($anagrafica, post('fido_assicurato'), post('data_inizio'), post('data_fine'));
        $assicurazione_crediti->save();

        flash()->info(tr('Assicurazione crediti aggiunta!'));

        break;

    case 'update':
        $assicurazione_crediti->data_inizio = post('data_inizio');
        $assicurazione_crediti->data_fine = post('data_fine');
        $assicurazione_crediti->fido_assicurato = post('fido_assicurato');
        $assicurazione_crediti->save();

        flash()->info(tr('Assicurazione crediti aggiornata!'));

        break;

    case 'delete':
        $assicurazione_crediti->delete();

        flash()->info(tr('Assicurazione crediti eliminata!'));

        break;
}
