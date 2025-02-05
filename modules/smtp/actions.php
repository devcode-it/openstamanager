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

use Models\Module;
use Models\OAuth2;
use Modules\Emails\Account;

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'add':
        $dbo->insert('em_accounts', [
            'name' => post('name'),
            'from_name' => post('from_name'),
            'from_address' => post('from_address'),
        ]);

        $id_record = $dbo->lastInsertedID();

        flash()->info(tr('Nuovo account email aggiunto!'));

        break;

    case 'update':
        $account = Account::find($id_record);

        $predefined = post('predefined');
        if (!empty($predefined)) {
            $dbo->query('UPDATE em_accounts SET predefined = 0');
        }

        $abilita_oauth2 = post('abilita_oauth2');

        $dbo->update('em_accounts', [
            'name' => post('name'),
            'note' => post('note'),
            'server' => post('server'),
            'port' => post('port'),
            'username' => post('username'),
            'password' => $_POST['password'],
            'from_name' => post('from_name'),
            'from_address' => post('from_address'),
            'encryption' => post('encryption'),
            'pec' => post('pec'),
            'timeout' => post('timeout'),
            'ssl_no_verify' => post('ssl_no_verify'),
            'predefined' => $predefined,
        ], ['id' => $id_record]);

        flash()->info(tr('Informazioni salvate correttamente!'));

        // Rimozione informazioni OAuth2 in caso di disabilitazione
        if (!$abilita_oauth2) {
            $oauth2 = $account->oauth2;
            if (!empty($oauth2)) {
                $account->oauth2()->dissociate();
                $account->save();

                $oauth2->delete();
            }
        }
        // Aggiornamento delle informazioni per OAuth2
        else {
            $oauth2 = $account->oauth2 ?: OAuth2::build();

            $oauth2->class = post('provider');
            $oauth2->client_id = post('client_id');
            $oauth2->client_secret = post('client_secret');
            $oauth2->config = post('config') ?: null;

            // Link di redirect dopo la configurazione
            $id_modulo_account_email = Module::where('name', 'Account email')->first()->id;
            $oauth2->after_configuration = base_path().'/editor.php?id_module='.$id_modulo_account_email.'&id_record='.$id_record;

            $oauth2->save();

            // Associazione Account-OAuth2
            $account->oauth2()->associate($oauth2);
            $account->save();
        }

        // Validazione indirizzo email mittente
        $check_email = Validate::isValidEmail(post('from_address'));

        // Controllo sulla validazione
        if (!empty($check_email['valid-format'])) {
            flash()->info(tr('Sintassi email verificata'));
        } else {
            flash()->error(tr("Attenzione: l'indirizzo email _EMAIL_ sembra non essere valido", [
                '_EMAIL_' => post('from_address'),
            ]));
        }

        // Controllo sulla verifica
        if (!empty($check_email['valid-format'])) {
            flash()->info(tr('SMTP email verificato'));
        } else {
            flash()->warning(tr('SMTP email non verificato'));
        }

        if (isAjaxRequest()) {
            echo json_encode(['id' => $id_record]);
        }

        break;

    case 'test':
        $result = $account->testConnection();

        echo json_encode([
            'test' => $result,
        ]);

        break;

    case 'delete':
        $account->delete();

        flash()->info(tr('Account email eliminato!'));

        break;

    case 'oauth2':
        $oauth2 = $account->oauth2;
        redirect(base_path().'/oauth2.php?id='.$oauth2->id);

        break;
}
