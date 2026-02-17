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

use Models\Group;
use Models\Module;
use Models\User;
use Models\UserTokens;

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
            $group->save();

            if ($id_module_start) {
                // Usa INSERT IGNORE per evitare errori di duplicazione in caso di race condition
                $dbo->query('INSERT IGNORE INTO zz_permissions(idgruppo, idmodule, permessi) VALUES('.prepare($id_record).', '.prepare($id_module_start).', \'r\')');

                // Sincronizza i permessi delle viste e dei segmenti per il modulo di partenza
                $group->syncModulePermissions($id_module_start, 'r');
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

            $dbo->delete('zz_user_sedi', ['id_user' => $id_utente]);
            $sedi = post('idsede');

            if (empty($sedi)) {
                $sedi = $dbo->fetchArray('SELECT id FROM an_sedi WHERE idanagrafica = '.prepare($id_azienda));
                $sedi = array_column($sedi, 'id');
                $sedi = array_merge([0], $sedi);
            }
            foreach ($sedi as $id_sede) {
                // Usa INSERT IGNORE per evitare errori di duplicazione in caso di race condition
                $dbo->query('INSERT IGNORE INTO `zz_user_sedi` (`id_user`,`idsede`) VALUES ('.prepare($utente['id']).', '.prepare($id_sede).')');
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

        $utente = auth_osm()->getUser();

        if (!empty($password)) {
            $utente->password = $password;
        } elseif (!empty($_FILES['photo']['tmp_name'])) {
            $utente->photo = $_FILES['photo'];
        }

        $utente->save();

        redirect_url(base_path_osm().'/modules/utenti/info.php');

        break;

        // Elimina utente + disattivazione token
    case 'delete_user':
        $utente = User::find($id_utente);

        /* Controlla che non posso auto eliminarmi */
        if (auth_osm()->getUser()->id != $utente->id) {
            /* Controlla che l'utente che voglio eliminare non presenti logs associati */
            if (count($utente->logs) == 0) {
                $utente->delete();
                flash()->info(tr('Utente eliminato!'));

                UserTokens::where('id_utente', $id_utente)->delete();
                flash()->info(tr('Token eliminato!'));
            } else {
                flash()->error(tr('L\'utente _USER_ presenta dei log attivi. Impossibile eliminare utente.', ['_USER_' => $utente->username]));

                $dbo->update('zz_users', [
                    'enabled' => 0,
                    'deleted_at' => Carbon\Carbon::now(),
                ], ['id' => $id_utente]);

                flash()->info(tr('Utente disabilitato!'));

                UserTokens::where('id_utente', $id_utente)->delete();
                flash()->info(tr('Token eliminato!'));
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
            $group = Group::find($id_record);
            $group->delete();
            User::where('idgruppo', $id_record)->delete();
            UserTokens::whereIn('id_utente', User::where('idgruppo', $id_record)->pluck('id'))->delete();
            $dbo->delete('zz_permissions', ['idgruppo' => $id_record]);
            flash()->info(tr('Gruppo e relativi utenti eliminati!'));
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
            $dbo->delete('zz_permissions', ['idgruppo' => $id_record]);

            // Ottieni il gruppo per la sincronizzazione
            $group = Group::find($id_record);

            foreach ($permessi as $module_name => $permesso) {
                $module_id = Module::where('name', $module_name)->first()->id;

                // Usa INSERT IGNORE per evitare errori di duplicazione in caso di race condition
                $dbo->query('INSERT IGNORE INTO zz_permissions(idgruppo, idmodule, permessi) VALUES('.prepare($id_record).', '.prepare($module_id).', '.prepare($permesso).')');

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
                // Usa INSERT IGNORE per evitare errori di duplicazione in caso di race condition
                $query = 'INSERT IGNORE INTO zz_permissions(idgruppo, idmodule, permessi) VALUES('.prepare($id_record).', '.prepare($idmodulo).', '.prepare($permessi).')';
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

        // Abilita OTP per l'utente
    case 'enable_otp':
        $utente = User::find($id_utente);

        if ($utente) {
            $id_token = filter('id_token');

            // Aggiorna il token esistente per abilitare OTP
            $dbo->query('UPDATE zz_otp_tokens SET enabled = 1, email = '.prepare($utente->email).' WHERE id = '.prepare($id_token));

            ob_end_clean();
            echo 'ok';
        } else {
            ob_end_clean();
            echo 'error';
        }

        break;

        // Disabilita OTP per l'utente
    case 'disable_otp':
        $utente = User::find($id_utente);

        if ($utente) {
            $id_token = filter('id_token');

            // Disabilita OTP nel token dell'utente e resetta last_otp
            $dbo->query('UPDATE zz_otp_tokens SET enabled = 0, last_otp = "", valido_dal = NULL, valido_al = NULL, email = '.prepare($utente->email).' WHERE id = '.prepare($id_token));

            ob_end_clean();
            echo 'ok';
        } else {
            ob_end_clean();
            echo 'error';
        }

        break;

        // Aggiorna configurazione OTP completa
    case 'update_otp':
        $utente = User::find($id_utente);
        $id_token = filter('id_token');
        $valido_dal = filter('valido_dal');
        $valido_al = filter('valido_al');

        if ($utente && !empty($id_token)) {
            if (!empty($valido_dal)) {
                // Valida che la data sia futura
                if (strtotime($valido_dal) <= time()) {
                    flash()->error(tr('La data di inizio validità deve essere futura'));
                    break;
                }
                $valido_dal_mysql = date('Y-m-d H:i:s', strtotime($valido_dal));
            } else {
                $valido_dal_mysql = null;
            }

            if (!empty($valido_al)) {
                // Valida che la data sia futura
                if (strtotime($valido_al) <= time()) {
                    flash()->error(tr('La data di fine validità deve essere futura'));
                    break;
                }
                $valido_al_mysql = date('Y-m-d H:i:s', strtotime($valido_al));
            } else {
                $valido_al_mysql = null;
            }

            $dbo->query('UPDATE zz_otp_tokens SET valido_dal = '.prepare($valido_dal_mysql).', valido_al = '.prepare($valido_al_mysql).', email = '.prepare($utente->email).' WHERE id = '.prepare($id_token));

            flash()->info(tr('Configurazione OTP aggiornata con successo!'));
        } else {
            flash()->error(tr('Errore durante l\'aggiornamento della configurazione OTP'));
        }

        break;
}
