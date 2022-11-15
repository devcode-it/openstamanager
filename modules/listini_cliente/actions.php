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

use Modules\Articoli\Articolo AS ArticoloOriginale;
use Modules\ListiniCliente\Articolo;
use Modules\ListiniCliente\Listino;

switch (filter('op')) {
    case 'update':
        $listino->nome = post('nome');
        $listino->data_attivazione = post('data_attivazione') ?: null;
        $listino->data_scadenza_predefinita = post('data_scadenza_predefinita') ?: null;
        $listino->is_sempre_visibile = post('is_sempre_visibile');
        $listino->note = post('note');
        $listino->save();

        flash()->info(tr('Listino modificato correttamente!'));

        break;

    case 'add':
        $listino = Listino::build(post('nome'));
        $listino->data_attivazione = post('data_attivazione') ?: null;
        $listino->data_scadenza_predefinita = post('data_scadenza_predefinita') ?: null;
        $listino->is_sempre_visibile = post('is_sempre_visibile');
        $listino->note = post('note');
        $listino->save();

        $id_record = $listino->id;

        flash()->info(tr('Nuovo listino aggiunto!'));
        
        break;

    case 'manage_articolo':
        if (empty(post('id'))) {
            $articolo_originale = ArticoloOriginale::find(post('id_articolo'));

            $articolo_listino = Articolo::build($articolo_originale, $id_record);
            $articolo_listino->data_scadenza = post('data_scadenza') ?: null;
            $articolo_listino->setPrezzoUnitario(post('prezzo_unitario'));
            $articolo_listino->sconto_percentuale = post('sconto_percentuale');
            $articolo_listino->save();
        } else {
            $articolo_listino = Articolo::find(post('id'));
            $articolo_listino->data_scadenza = post('data_scadenza') ?: null;
            $articolo_listino->setPrezzoUnitario(post('prezzo_unitario'));
            $articolo_listino->sconto_percentuale = post('sconto_percentuale');
            $articolo_listino->save();
        }

        flash()->info(tr('Nuovo articolo al listino aggiunto!'));
        
        break;

    case 'delete_articolo':
        $articolo_listino = Articolo::find(post('id'));
        $articolo_listino->delete();

        flash()->info(tr('Articolo del listino eliminato correttamente!'));

        break;

    case 'delete':
        if (isset($id_record)) {
            $listino->delete();
            $dbo->query('UPDATE `an_anagrafiche` SET id_listino=0 WHERE id_listino='.prepare($id_record));
            $dbo->query('DELETE FROM `mg_listini_articoli` WHERE id_listino='.prepare($id_record));
            flash()->info(tr('Listino eliminato correttamente!'));
        }

        break;
}
