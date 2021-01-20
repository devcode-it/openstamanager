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

use Models\User;

$id_utente = filter('id_utente');

switch (filter('op')) {
    // Aggiunta nuovo gruppo
    case 'add':
        $nome = filter('nome');

        // Verifico che questo nome gruppo non sia già stato usato
        if ($dbo->fetchNum('SELECT nome FROM zz_groups WHERE nome='.prepare($nome)) == 0) {
            $dbo->query('INSERT INTO zz_groups(nome, editable) VALUES('.prepare($nome).', 1)');
            $id_record = $dbo->lastInsertedID();

            flash()->info(tr('Gruppo aggiunto!'));
        } else {
            flash()->error(tr('Gruppo già esistente!'));
        }
        break;

    // Abilita utente
    case 'enable_user':
        if ($dbo->query('UPDATE zz_users SET enabled=1 WHERE id='.prepare($id_utente))) {
            flash()->info(tr('Utente abilitato!'));
        }
        break;

    // Disabilita utente
    case 'disable_user':
        if ($dbo->query('UPDATE zz_users SET enabled=0 WHERE id='.prepare($id_utente))) {
            flash()->info(tr('Utente disabilitato!'));
        }
        break;

    // Cambio di password e username dell'utente
    case 'update_user':
        $username = filter('username');
        $email = filter('email');
        $password = filter('password');

        $id_utente = filter('id_utente');
        if ($dbo->fetchNum('SELECT username FROM zz_users WHERE id != '.prepare($id_utente).' AND username='.prepare($username)) == 0) {
            //Aggiunta/modifica utente
            if (!empty($id_utente)) {
                $utente = User::find($id_utente);

                $utente->username = $username;
                $utente->email = $email;

                $cambia_password = filter('change_password');
                if (!empty($cambia_password)) {
                    $utente->password = $password;
                }
            } else {
                $gruppo = \Models\Group::find($id_record);
                $utente = User::build($gruppo, $username, $email, $password);
            }

            // Foto
            if (!empty($_FILES['photo']['tmp_name'])) {
                $utente->photo = $_FILES['photo'];
            }

            // Anagrafica
            $id_anagrafica = filter('idanag');
            $utente->id_anagrafica = $id_anagrafica;

            $utente->save();

            $dbo->query('DELETE FROM zz_user_sedi WHERE id_user = '.prepare($id_utente));
            $sedi = post('idsede');
            if (empty($sedi)) {
                $sedi = [0];
            }
            foreach ($sedi as $id_sede) {
                $dbo->query('INSERT INTO `zz_user_sedi` (`id_user`,`idsede`) VALUES ('.prepare($id_utente).', '.prepare($id_sede).')');
            }
        } else {
            flash()->error(tr('Utente già esistente!'));
        }

        break;

    // Aggiunta di un nuovo utente
    case 'self_update':
        $password = filter('password');

        $utente = Auth::user();

        if (!empty($password)) {
            $utente->password = $password;
        } elseif (!empty($_FILES['photo']['tmp_name'])) {
            $utente->photo = $_FILES['photo'];
        }

        $utente->save();

        redirect(base_path().'/modules/utenti/info.php');

        break;

    // Elimina utente + disattivazione token
    case 'delete_user':
        if ($dbo->query('DELETE FROM zz_users WHERE id='.prepare($id_utente))) {
            flash()->info(tr('Utente eliminato!'));

            if ($dbo->query('DELETE FROM zz_tokens WHERE id_utente='.prepare($id_utente))) {
                flash()->info(tr('Token eliminato!'));
            }
        }
        break;

    // Abilita API utente
    case 'token_enable':
        $utente = User::find($id_utente);

        $already_token = $dbo->fetchOne('SELECT `id` FROM `zz_tokens` WHERE `id_utente` = '.prepare($id_utente))['id'];

        if (empty($already_token)) {
            //Quando richiamo getApiTokens,  non trovando nessun token abilitato ne crea uno nuovo
            $tokens = $utente->getApiTokens();

            foreach ($tokens as $token) {
                $dbo->query('UPDATE zz_tokens SET enabled = 1 WHERE id = '.prepare($token['id']));
                flash()->info(tr('Token creato!'));
            }
        } elseif ($dbo->query('UPDATE zz_tokens SET enabled = 1 WHERE id_utente = '.prepare($id_utente))) {
            flash()->info(tr('Token abilitato!'));
        }

        break;

    // Disabilita API utente
    case 'token_disable':
        $utente = User::find($id_utente);
        $tokens = $utente->getApiTokens();

        foreach ($tokens as $token) {
            $dbo->query('UPDATE zz_tokens SET enabled = 0 WHERE id = '.prepare($token['id']));
        }

        flash()->info(tr('Token disabilitato!'));
        break;

    // Elimina gruppo
    case 'deletegroup':
        // Verifico se questo gruppo si può eliminare
        $query = 'SELECT editable FROM zz_groups WHERE id='.prepare($id_record);
        $rs = $dbo->fetchArray($query);

        if ($rs[0]['editable'] == 1) {
            if ($dbo->query('DELETE FROM zz_groups WHERE id='.prepare($id_record))) {
                $dbo->query('DELETE FROM zz_users WHERE idgruppo='.prepare($id_record));
                $dbo->query('DELETE FROM zz_tokens WHERE id_utente IN (SELECT id FROM zz_users WHERE idgruppo='.prepare($id_record).')');
                $dbo->query('DELETE FROM zz_permissions WHERE idgruppo='.prepare($id_record));
                flash()->info(tr('Gruppo e relativi utenti eliminati!'));
            }
        } else {
            flash()->error(tr('Questo gruppo non si può eliminare!'));
        }

        break;

    // Impostazione/reimpostazione dei permessi di accesso di default
    case 'restore_permission':
        //Gruppo Tecnici
        if ($dbo->fetchArray('SELECT `nome` FROM `zz_groups` WHERE `id` = '.prepare($id_record))[0]['nome'] == 'Tecnici') {
            $permessi = [];
            $permessi['Dashboard'] = 'rw';
            $permessi['Anagrafiche'] = 'rw';
            $permessi['Interventi'] = 'rw';
            $permessi['Magazzino'] = 'rw';
            $permessi['Articoli'] = 'rw';

            $dbo->query('DELETE FROM zz_permissions WHERE idgruppo='.prepare($id_record));

            foreach ($permessi as $module_name => $permesso) {
                $module_id = $dbo->fetchArray('SELECT `id` FROM `zz_modules` WHERE `name` = "'.$module_name.'"')[0]['id'];

                $dbo->insert('zz_permissions', [
                    'idgruppo' => $id_record,
                    'idmodule' => $module_id,
                    'permessi' => $permesso,
                ]);
            }

            flash()->info(tr('Permessi reimpostati'));
        }

    break;

    // Aggiornamento dei permessi di accesso
    case 'update_permission':
        $permessi = filter('permesso');
        $idmodulo = filter('idmodulo');

        // Verifico che ci sia il permesso per questo gruppo
        if ($permessi != '-') {
            $rs = $dbo->fetchArray('SELECT * FROM zz_permissions WHERE idgruppo='.prepare($id_record).' AND idmodule='.prepare($idmodulo));
            if (empty($rs)) {
                $query = 'INSERT INTO zz_permissions(idgruppo, idmodule, permessi) VALUES('.prepare($id_record).', '.prepare($idmodulo).', '.prepare($permessi).')';
            } else {
                $query = 'UPDATE zz_permissions SET permessi='.prepare($permessi).' WHERE id='.prepare($rs[0]['id']);
            }

            // Aggiunta dei permessi relativi alle viste
            $count = $dbo->fetchNum('SELECT * FROM `zz_group_view` WHERE `id_gruppo` = '.prepare($id_record).' AND `id_vista` IN (SELECT `id` FROM `zz_views` WHERE `id_module`='.prepare($idmodulo).')');

            if (empty($count)) {
                $results = $dbo->fetchArray('SELECT `id_vista` FROM `zz_group_view` WHERE `id_vista` IN (SELECT `id` FROM `zz_views` WHERE `id_module`='.prepare($idmodulo).')');

                foreach ($results as $result) {
                    $dbo->attach('zz_group_view', ['id_vista' => $result['id_vista']], ['id_gruppo' => $id_record]);
                }
            }
        } else {
            $query = 'DELETE FROM zz_permissions WHERE idgruppo='.prepare($id_record).' AND idmodule='.prepare($idmodulo);
        }

        $dbo->query($query);

        ob_end_clean();
        echo 'ok';

        break;
}
