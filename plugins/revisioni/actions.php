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

$operazione = filter('op');

switch ($operazione) {
    case 'edit_revision':
        $master_revision = post('master_revision');
        $id_record = post('idrevisione');
        //Tolgo il flag default_revision da tutte le revisioni e dal record_principale
        $dbo->query('UPDATE co_preventivi SET default_revision=0 WHERE master_revision='.prepare($master_revision));
        $dbo->query('UPDATE co_preventivi SET default_revision=1 WHERE id='.prepare($id_record));

        flash()->info(tr('Revisione aggiornata!'));
        break;

    case 'delete_revision':
        $idrevisione = post('idrevisione');
        $dbo->query('DELETE FROM co_preventivi WHERE id='.prepare($idrevisione));

        flash()->info(tr('Revisione eliminata!'));
        break;
}
