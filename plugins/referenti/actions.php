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
            $idmansione = post('idmansione');
            $idsede = post('idsede');
            $opt_out_newsletter = post('disable_newsletter');
            
            $referente = Referente::build($id_parent, $nome, $idmansione, $idsede);
            $id_record = $referente->id;

            $referente->telefono = post('telefono');
            $referente->email = post('email');
            $referente->enable_newsletter = empty($opt_out_newsletter);
    
            $referente->save();

            if (isAjaxRequest() && !empty($id_record)) {
                echo json_encode(['id' => $id_record, 'text' =>  $referente->nome]);
            }

            flash()->info(tr('Aggiunto nuovo referente!'));
        } else {
            flash()->warning(tr('Errore durante aggiunta del referente'));
        }

        break;

    case 'updatereferente':
        $opt_out_newsletter = post('disable_newsletter');

        $dbo->update('an_referenti', [
            'idanagrafica' => $id_parent,
            'nome' => post('nome'),
            'idmansione' => post('idmansione'),
            'telefono' => post('telefono'),
            'email' => post('email'),
            'idsede' => post('idsede'),
            'enable_newsletter' => empty($opt_out_newsletter),
        ], ['id' => $id_record]);

        flash()->info(tr('Salvataggio completato!'));

        break;

    case 'deletereferente':
        $dbo->query('DELETE FROM `an_referenti` WHERE `id`='.prepare($id_record));
        $dbo->query('UPDATE co_preventivi SET idreferente = 0 WHERE `idreferente` = '.prepare($id_record));

        flash()->info(tr('Referente eliminato!'));

        break;
}
