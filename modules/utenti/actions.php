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

$skip_permissions = true;
include_once __DIR__.'/../../core.php';

use Models\Group;
use Models\Module;
use Models\User;

$id_utente = filter('id_utente');

switch (filter('op')) {
    // Aggiunta nuovo gruppo
    case 'add':
        $nome = filter('nome');
        $id_module_start = filter('id_module_start') ?: null;
        $theme = filter('theme') ?: null;

        // Verifico che questo nome gruppo non sia già stato usato
        if (Group::where('nome', $nome)->first()->id == null) {
            $group = Group::build($nome, $theme, $id_module_start);
            $id_record = $dbo->lastInsertedID();
            $group->editable = 1;
            $group->setTranslation('title', $nome);
            $group->save();

            if ($id_module_start) {
                $dbo->insert('zz_permissions', [
                    'idgruppo' => $id_record,
                    'idmodule' => $id_module_start,
                    'permessi' => 'r',
                ]);
            }

            flash()->info(tr('Gruppo aggiunto!'));
        } else {
            flash()->error(tr('Gruppo già esistente!'));
        }
        break;

        // Abilita utente
    case 'enable_user':
        if ($dbo->query('UPDATE `zz_users` SET `enabled`=1 WHERE `id`='.prepare($id_utente))) {
            flash()->info(tr('Utente abilitato!'));
        }
        break;

        // Disabilita utente
    case 'disable_user':
        if ($dbo->query('UPDATE `zz_users` SET `enabled`=0 WHERE `id`='.prepare($id_utente))) {
            flash()->info(tr('Utente disabilitato!'));
        }
        break;

        // Cambio di password e username dell'utente
    case 'update_user':
        $username = filter('username');
        $email = filter('email');
        $password = $_POST['password'];

        $id_utente = filter('id_utente');
        if ($dbo->fetchNum('SELECT `username` FROM `zz_users` WHERE `id` != '.prepare($id_utente).' AND `username`='.prepare($username)) == 0) {
            // Aggiunta/modifica utente
            if (!empty($id_utente)) {
                $utente = User::find($id_utente);

                $utente->username = $username;
                $utente->email = $email;

                $cambia_password = filter('change_password');
                if (!empty($cambia_password)) {
                    $utente->password = $password;
                }
            } else {
                $gruppo = Group::find($id_record);
                $utente = User::build($gruppo, $username, $email, $password);
            }

            // Foto
            if (!empty($_FILES['photo']['tmp_name'])) {
                $utente->photo = $_FILES['photo'];
            }
            // Anagrafica
            $id_anagrafica = filter('idanag');
            $utente->id_anagrafica = $id_anagrafica;

            // Gruppo
            $id_azienda = setting('Azienda predefinita');
            $id_gruppo = filter('idgruppo');
            $utente->idgruppo = $id_gruppo;

            $utente->save();

            $dbo->query('DELETE FROM zz_user_sedi WHERE id_user = '.prepare($id_utente));
            $sedi = post('idsede');

            if (empty($sedi)) {
                $sedi = $dbo->fetchArray('SELECT id FROM an_sedi WHERE idanagrafica = '.prepare($id_azienda));
                $sedi = array_column($sedi, 'id');
                $sedi = array_merge([0], $sedi);
            }
            foreach ($sedi as $id_sede) {
                $dbo->query('INSERT INTO `zz_user_sedi` (`id_user`,`idsede`) VALUES ('.prepare($utente['id']).', '.prepare($id_sede).')');
            }

            flash()->info(tr("Informazioni per l'utente _USERNAME_ salvate correttamente!", [
                '_USERNAME_' => $utente->username,
            ]));
        } else {
            flash()->error(tr('Utente _USERNAME_ già esistente!', [
                '_USERNAME_' => $username,
            ]));
        }

        break;

        // Aggiunta di un nuovo utente
    case 'self_update':
        $password = filter('password', null, true);

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
        $utente = User::find($id_utente);

        /* Controlla che non posso auto eliminarmi */
        if (Auth::user()->id != $utente->id) {
            /* Controlla che l'utente che voglio eliminare non presenti logs associati */
            if (count($utente->logs) == 0) {
                if ($dbo->query('DELETE FROM zz_users WHERE id='.prepare($id_utente))) {
                    flash()->info(tr('Utente eliminato!'));

                    if ($dbo->query('DELETE FROM zz_tokens WHERE id_utente='.prepare($id_utente))) {
                        flash()->info(tr('Token eliminato!'));
                    }
                }
            } else {
                flash()->error(tr('L\'utente _USER_ presenta dei log attivi. Impossibile eliminare utente.', ['_USER_' => $utente->username]));

                $dbo->update('zz_users', [
                    'enabled' => 0,
                ], ['id' => $id_utente]);

                flash()->info(tr('Utente disabilitato!'));

                if ($dbo->query('DELETE FROM zz_tokens WHERE id_utente='.prepare($id_utente))) {
                    flash()->info(tr('Token eliminato!'));
                } flash()->info(tr('Token eliminato!'));
            }
        } else {
            flash()->error(tr('L\'utente _USER_ è l\'utente attuale. Impossibile eliminare utente.', ['_USER_' => $utente->username]));
        }

        break;

        // Abilita API utente
    case 'token_enable':
        $utente = User::find($id_utente);

        $already_token = $dbo->fetchOne('SELECT `id` FROM `zz_tokens` WHERE `id_utente` = '.prepare($id_utente))['id'];

        if (empty($already_token)) {
            // Quando richiamo getApiTokens,  non trovando nessun token abilitato ne crea uno nuovo
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
        $query = 'SELECT `editable` FROM `zz_groups` WHERE `id`='.prepare($id_record);
        $rs = $dbo->fetchArray($query);

        if ($rs[0]['editable'] == 1) {
            if ($dbo->query('DELETE FROM `zz_groups` WHERE `id`='.prepare($id_record))) {
                $dbo->query('DELETE FROM `zz_users` WHERE `idgruppo`='.prepare($id_record));
                $dbo->query('DELETE FROM `zz_tokens` WHERE `id_utente` IN (SELECT `id` FROM `zz_users` WHERE `idgruppo`='.prepare($id_record).')');
                $dbo->query('DELETE FROM `zz_permissions` WHERE `idgruppo`='.prepare($id_record));
                flash()->info(tr('Gruppo e relativi utenti eliminati!'));
            }
        } else {
            flash()->error(tr('Questo gruppo non si può eliminare!'));
        }

        break;

        // Impostazione/reimpostazione dei permessi di accesso di default
    case 'restore_permission':
        // Gruppo Tecnici
        if ($dbo->fetchArray('SELECT `nome` FROM `zz_groups` WHERE `id` = '.prepare($id_record))[0]['nome'] == 'Tecnici') {
            $permessi = [];
            $permessi['Dashboard'] = 'rw';
            $permessi['Anagrafiche'] = 'rw';
            $permessi['Interventi'] = 'rw';
            $permessi['Magazzino'] = 'rw';
            $permessi['Articoli'] = 'rw';

            // Rimuovi tutti i permessi esistenti
            $dbo->query('DELETE FROM zz_permissions WHERE idgruppo='.prepare($id_record));

            // Ottieni il gruppo per la sincronizzazione
            $group = Group::find($id_record);

            foreach ($permessi as $module_name => $permesso) {
                $module_id = Module::where('name', $module_name)->first()->id;

                $dbo->insert('zz_permissions', [
                    'idgruppo' => $id_record,
                    'idmodule' => $module_id,
                    'permessi' => $permesso,
                ]);

                // Sincronizza i permessi delle viste e dei segmenti per ogni modulo
                $group->syncModulePermissions($module_id, $permesso);
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
        } else {
            $query = 'DELETE FROM zz_permissions WHERE idgruppo='.prepare($id_record).' AND idmodule='.prepare($idmodulo);
        }

        $dbo->query($query);

        // Sincronizza i permessi delle viste e dei segmenti
        $group = Group::find($id_record);
        $group->syncModulePermissions($idmodulo, $permessi);

        ob_end_clean();
        echo 'ok';

        break;

    case 'update_id_module_start':
        try {
            $group->id_module_start = filter('id_module_start');
            $group->save();
            echo 'ok';
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        break;

    case 'update_theme':
        try {
            $group->theme = filter('theme');
            $group->save();
            echo 'ok';
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        break;

    case 'update_setting':
        $id = filter('id');
        $valore = filter('valore', null, 1);

        $user_options = json_decode((string) $user->options, true) ?: [];
        $user_options['settings'][$id] = $valore;

        $user->options = json_encode($user_options);
        $user->save();

        echo json_encode([
            'result' => true,
        ]);

        flash()->info('Impostazione modificata con successo!');

        break;
}
