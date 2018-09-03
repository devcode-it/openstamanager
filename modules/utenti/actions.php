<?php

include_once __DIR__.'/../../core.php';

$id_utente = filter('id_utente');

switch (filter('op')) {
    // Abilita utente
    case 'enable':
        if ($dbo->query('UPDATE zz_users SET enabled=1 WHERE id='.prepare($id_utente))) {
            flash()->info(tr('Utente abilitato!'));
        }
        break;

    // Disabilita utente
    case 'disable':
        if ($dbo->query('UPDATE zz_users SET enabled=0 WHERE id='.prepare($id_utente))) {
            flash()->info(tr('Utente disabilitato!'));
        }
        break;

    // Cambio di password e usernome dell'utente
    case 'change_pwd':
        $id_utente = filter('id_utente');
        $min_length = filter('min_length');
        $min_length_username = filter('min_length_username');

        $password = filter('password1');
        $password_rep = filter('password2');

        // Verifico che la password sia di almeno x caratteri
        if (strlen($password) < $min_length) {
            flash()->error(tr('La password deve essere lunga almeno _MIN_ caratteri!', [
                '_MIN_' => $min_length,
            ]));
        } elseif ($password != $password_rep) {
            flash()->error(tr('Le password non coincidono'));
        } else {
            $idanagrafica = filter('idanag');

            $dbo->query('UPDATE zz_users SET password='.prepare(Auth::hashPassword($password)).', idanagrafica='.prepare($idanagrafica).' WHERE id='.prepare($id_utente));

            flash()->info(tr('Password aggiornata!'));
        }

        $username = filter('username');

        // Se ho modificato l'username, verifico che questo non sia già stato usato
        $rs = $dbo->fetchArray('SELECT username FROM zz_users WHERE id='.prepare($id_utente));

        if ($rs[0]['username'] != $username) {
            $n = $dbo->fetchNum('SELECT id FROM zz_users WHERE username='.prepare($username));

            if ($n == 0) {
                $dbo->query('UPDATE zz_users SET username='.prepare($username).' WHERE id='.prepare($id_utente));

                flash()->info(tr('Username aggiornato!'));
            } else {
                flash()->error(tr('Utente già esistente!'));
            }
        }

        if (empty($id_record)) {
            redirect(ROOTDIR.'/modules/utenti/info.php');
        }

        break;

    // Aggiunta di un nuovo utente
    case 'adduser':
        $username = filter('username');

        $min_length = filter('min_length');
        $min_length_username = filter('min_length_username');

        $password = filter('password1');
        $password_rep = filter('password2');

        $idanagrafica = filter('idanag');

        // Verifico che questo username non sia già stato usato
        $n = $dbo->fetchNum('SELECT * FROM zz_users WHERE username='.prepare($username));

        if ($n == 0) {
            // Verifico che la password sia di almeno x caratteri
            if (strlen($password) < $min_length) {
                flash()->error(tr('La password deve essere lunga almeno _MIN_ caratteri!', [
                    '_MIN_' => $min_length,
                ]));
            } elseif ($password != $password_rep) {
                flash()->error(tr('Le password non coincidono'));
            } else {
                if ($dbo->query('INSERT INTO zz_users(idgruppo, username, password, idanagrafica, enabled, email) VALUES('.prepare($id_record).', '.prepare($username).', '.prepare(Auth::hashPassword($password)).', '.prepare($idanagrafica).", 1, '')")) {
                    $dbo->query('INSERT INTO `zz_tokens` (`id_utente`, `token`) VALUES ('.prepare($dbo->lastInsertedID()).', '.prepare(secure_random_string()).')');

                    flash()->info(tr('Utente aggiunto!'));
                }
            }
        } else {
            flash()->error(tr('Utente già esistente!'));
        }
        break;

    // Aggiunta nuovo gruppo
    case 'add':
        $nome = filter('nome');

        // Verifico che questo username non sia già stato usato
        if ($dbo->fetchNum('SELECT nome FROM zz_groups WHERE nome='.prepare($nome)) == 0) {
            $dbo->query('INSERT INTO zz_groups( nome, editable ) VALUES('.prepare($nome).', 1)');
            flash()->info(tr('Gruppo aggiunto!'));
            $id_record = $dbo->lastInsertedID();
        } else {
            flash()->error(tr('Gruppo già esistente!'));
        }
        break;

    // Elimina utente
    case 'delete':
        if ($dbo->query('DELETE FROM zz_users WHERE id='.prepare($id_utente))) {
            flash()->info(tr('Utente eliminato!'));
        }
        break;

    // Abilita API utente
    case 'token_enable':
         if ($dbo->query('UPDATE zz_tokens SET enabled = 1 WHERE id_utente = '.prepare($id_utente))) {
             flash()->info(tr('Token abilitato!'));
         }
        break;

    // Disabilita API utente
    case 'token_disable':
        if ($dbo->query('UPDATE zz_tokens SET enabled = 0 WHERE id_utente = '.prepare($id_utente))) {
            flash()->info(tr('Token disabilitato!'));
        }
        break;

    // Elimina gruppo
    case 'deletegroup':
        // Verifico se questo gruppo si può eliminare
        $query = 'SELECT editable FROM zz_groups WHERE id='.prepare($id_record);
        $rs = $dbo->fetchArray($query);

        if ($rs[0]['editable'] == 1) {
            if ($dbo->query('DELETE FROM zz_groups WHERE id='.prepare($id_record))) {
                $dbo->query('DELETE FROM zz_users WHERE idgruppo='.prepare($id_record));
                $dbo->query('DELETE FROM zz_permissions WHERE idgruppo='.prepare($id_record));
                flash()->info(tr('Gruppo eliminato!'));
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

            $_SESSION['infos'][] = tr('Permessi reimpostati.');
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
