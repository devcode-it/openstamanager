<?php

include_once __DIR__.'/../../core.php';

$id_utente = filter('idutente');

switch (filter('op')) {
    // Abilita utente
    case 'enable':
        if ($dbo->query('UPDATE zz_users SET enabled=1 WHERE idutente='.prepare($id_utente))) {
            $_SESSION['infos'][] = _('Utente abilitato!');
        }
        break;

    // Disabilita utente
    case 'disable':
        if ($dbo->query('UPDATE zz_users SET enabled=0 WHERE idutente='.prepare($id_utente))) {
            $_SESSION['infos'][] = _('Utente disabilitato!');
        }
        break;

    // Cambio di password e usernome dell'utente
    case 'change_pwd':
        $id_utente = filter('idutente');
        $min_length = filter('min_length');
        $min_length_username = filter('min_length_username');

        $password = filter('password1');
        $password_rep = filter('password2');

        // Verifico che la password sia di almeno x caratteri
        if (strlen($password) < $min_length) {
            $_SESSION['errors'][] = str_replace('_MIN_', $min_length, _('La password deve essere lunga almeno _MIN_ caratteri!'));
        } elseif ($password != $password_rep) {
            $_SESSION['errors'][] = _('Le password non coincidono');
        } else {
            $dbo->query('UPDATE zz_users SET password='.prepare(Auth::hashPassword($password)).' WHERE idutente='.prepare($id_utente));

            $_SESSION['infos'][] = _('Password aggiornata!');
        }

        $username = filter('username');

        // Se ho modificato l'username, verifico che questo non sia già stato usato
        $rs = $dbo->fetchArray('SELECT username FROM zz_users WHERE idutente='.prepare($id_utente));

        if ($rs[0]['username'] != $username) {
            $n = $dbo->fetchNum('SELECT idutente FROM zz_users WHERE username='.prepare($username));

            if ($n == 0) {
                $dbo->query('UPDATE zz_users SET username='.prepare($username).' WHERE idutente='.prepare($id_utente));

                $_SESSION['infos'][] = _('Username aggiornato!');
            } else {
                $_SESSION['errors'][] = _('Utente già esistente!');
            }
        }
        break;

    // Aggiunta di un nuovo utente
    case 'adduser':
        $username = filter('username');

        $min_length = filter('min_length');
        $min_length_username = filter('min_length_username');

        $password = filter('password1');
        $password_rep = filter('password2');

        $idtipoanagrafica = '';
        $idanag = explode('-', filter('idanag'));
        if (count($idanag) == 2) {
            $idtipoanagrafica = $idanag[0];
            $idanagrafica = $idanag[1];
        }

        // Verifico che questo username non sia già stato usato
        $n = $dbo->fetchNum('SELECT * FROM zz_users WHERE username='.prepare($username));

        if ($n == 0) {
            // Verifico che la password sia di almeno x caratteri
            if (strlen($password) < $min_length) {
                $_SESSION['errors'][] = str_replace('_MIN_', $min_length, _('La password deve essere lunga almeno _MIN_ caratteri!'));
            } elseif ($password != $password_rep) {
                $_SESSION['errors'][] = _('Le password non coincidono');
            } else {
                if ($dbo->query('INSERT INTO zz_users(idgruppo, username, password, idanagrafica, idtipoanagrafica, enabled, email) VALUES('.prepare($id_record).', '.prepare($username).', '.prepare(Auth::hashPassword($password)).', '.prepare($idanagrafica).', '.prepare($idtipoanagrafica).", 1, '')")) {
                    $dbo->query('INSERT INTO `zz_tokens` (`id_utente`, `token`) VALUES ('.prepare($dbo->lastInsertedID()).', '.prepare(secure_random_string()).')');

                    $_SESSION['infos'][] = _('Utente aggiunto!');
                }
            }
        } else {
            $_SESSION['errors'][] = _('Utente già esistente!');
        }
        break;

    // Aggiunta nuovo gruppo
    case 'add':
        $nome = filter('nome');

        // Verifico che questo username non sia già stato usato
        if ($dbo->fetchNum('SELECT nome FROM zz_groups WHERE nome='.prepare($nome)) == 0) {
            $dbo->query('INSERT INTO zz_groups( nome, editable ) VALUES('.prepare($nome).', 1)');
            $_SESSION['infos'][] = _('Gruppo aggiunto!');
            $id_record = $dbo->lastInsertedID();
        } else {
            $_SESSION['errors'][] = _('Gruppo già esistente!');
        }
        break;

    // Elimina utente
   case 'delete':
        if ($dbo->query('DELETE FROM zz_users WHERE idutente='.prepare($id_utente))) {
            $_SESSION['infos'][] = _('Utente eliminato!');
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
                $_SESSION['infos'][] = _('Gruppo eliminato!');
            }
        } else {
            $_SESSION['errors'][] = _('Questo gruppo non si può eliminare!');
        }

        break;

    // Aggiornamento dei permessi di accesso
    case 'update_permission':
        $permessi = filter('permesso');
        $idmodulo = filter('idmodulo');

        // Verifico che ci sia il permesso per questo gruppo
        $rs = $dbo->fetchArray('SELECT * FROM zz_permissions WHERE idgruppo='.prepare($id_record).' AND idmodule='.prepare($idmodulo));
        if (count($rs) == 0) {
            $query = 'INSERT INTO zz_permissions(idgruppo, idmodule, permessi) VALUES('.prepare($id_record).', '.prepare($idmodulo).', '.prepare($permessi).')';
        } else {
            $query = 'UPDATE zz_permissions SET permessi='.prepare($permessi).' WHERE id='.prepare($rs[0]['id']);
        }

        // Aggiunta dei permessi relativi alle viste
        $count = $dbo->fetchArray('SELECT COUNT(*) AS count FROM `zz_group_view` WHERE `id_gruppo` = '.prepare($id_record).' AND `id_vista` IN (SELECT `id` FROM `zz_views` WHERE `id_module`='.prepare($idmodulo).')');
        if (empty($count[0]['count'])) {

            $results = $dbo->fetchArray('SELECT `id_vista` FROM `zz_group_view` WHERE `id_vista` IN (SELECT `id` FROM `zz_views` WHERE `id_module`='.prepare($idmodulo).')');
            foreach ($results as $result) {
                $dbo->attach('zz_group_view', ['id_vista' => $result['id_vista']], ['id_gruppo' => $id_record]);
            }
        }

        $dbo->query($query);

        ob_end_clean();
        echo 'ok';

        break;
}
