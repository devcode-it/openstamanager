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
include_once __DIR__.'/core.php';

use Carbon\Carbon;
use Illuminate\Database\QueryException;

$op = filter('op');
$token = filter('token');

// Lingua pre-login: solo sessione/richiesta corrente, senza modificare l'impostazione globale.
$available_login_locales = [];
$selected_login_locale = trans_osm()->getCurrentLocale();
if ($dbo->isConnected() && $dbo->isInstalled() && !AuthOSM::check()) {
    try {
        $catalog_locales = trans_osm()->getAvailableLocales();
        $enabled_locales = $dbo->fetchArray('SELECT `id`, `name`, `iso_code`, `language_code`, `date`, `time`, `timestamp`, `decimals`, `thousands`, `predefined` FROM `zz_langs` WHERE `enabled` = 1 ORDER BY `predefined` DESC, `name` ASC');

        foreach ($enabled_locales as $enabled_locale) {
            if (!empty($enabled_locale['predefined']) || trans_osm()->isLocaleAvailable($enabled_locale['language_code']) || in_array($enabled_locale['language_code'], $catalog_locales)) {
                $available_login_locales[] = $enabled_locale;
            }
        }

        $requested_login_locale = null;
        $has_requested_login_locale = array_key_exists('login_locale', $_POST) || array_key_exists('login_locale', $_GET);
        if ($has_requested_login_locale) {
            $requested_login_locale = post('login_locale') ?: get('login_locale');
        } elseif (!empty($_SESSION['login_locale'])) {
            $requested_login_locale = $_SESSION['login_locale'];
        }

        $login_locale = null;
        foreach ($available_login_locales as $available_login_locale) {
            if ($available_login_locale['language_code'] === $requested_login_locale) {
                $login_locale = $available_login_locale;
                break;
            }
        }

        if (!empty($login_locale)) {
            $_SESSION['login_locale'] = $login_locale['language_code'];
            $selected_login_locale = $login_locale['language_code'];
            $lang = $selected_login_locale;

            trans_osm()->setLocale($selected_login_locale, [
                'timestamp' => $login_locale['timestamp'],
                'date' => $login_locale['date'],
                'time' => $login_locale['time'],
                'number' => [
                    'decimals' => $login_locale['decimals'],
                    'thousands' => $login_locale['thousands'],
                ],
            ]);
        } elseif ($has_requested_login_locale) {
            unset($_SESSION['login_locale']);
        }
    } catch (QueryException $e) {
    }
}

// LOGIN
switch ($op) {
    case 'login':
        $username = post('username');
        $password = post('password');

        if ($dbo->isConnected() && $dbo->isInstalled() && auth_osm()->attempt($username, $password)) {
            $_SESSION['keep_alive'] = true;

            if (intval(setting('Inizio periodo calendario'))) {
                $_SESSION['period_start'] = Carbon::createFromFormat('d/m/Y', setting('Inizio periodo calendario'))->format('Y-m-d');
            } else {
                $_SESSION['period_start'] = date('Y').'-01-01';
            }

            if (intval(setting('Fine periodo calendario'))) {
                $_SESSION['period_end'] = Carbon::createFromFormat('d/m/Y', setting('Fine periodo calendario'))->format('Y-m-d');
            } else {
                $_SESSION['period_end'] = date('Y').'-12-31';
            }

        // Rimozione log vecchi
        // $dbo->query('DELETE FROM `zz_operations` WHERE DATE_ADD(`created_at`, INTERVAL 30*24*60*60 SECOND) <= NOW()');
        } else {
            $status = auth_osm()->getCurrentStatus();

            // Salva il messaggio di errore in una variabile di sessione separata
            $_SESSION['login_error'] = AuthOSM::getStatus()[$status]['message'];

            redirect_url(base_path_osm().'/index.php');
            exit;
        }

        break;

    case 'logout':
        AuthOSM::logout();
        // Pulisce anche l'intended URL al logout
        AuthOSM::clearIntended();

        redirect_url(base_path_osm().'/index.php');
        exit;
}

if (AuthOSM::check() && isset($dbo) && $dbo->isConnected() && $dbo->isInstalled()) {
    // Priorità 1: Token access (sistema esistente)
    if (Permissions::isTokenAccess()) {
        if (!empty($_SESSION['token_access']['id_module_target']) && !empty($_SESSION['token_access']['id_record_target'])) {
            redirect_url(base_path_osm().'/shared_editor.php?id_module='.$_SESSION['token_access']['id_module_target'].'&id_record='.$_SESSION['token_access']['id_record_target']);
            exit;
        }
    }

    // Priorità 2: Intended URL (nuovo sistema di redirect post-login)
    if (AuthOSM::hasIntended()) {
        $intended_url = AuthOSM::getIntended();

        // Verifica i permessi per l'URL intended
        if (AuthOSM::canAccessIntended()) {
            AuthOSM::clearIntended();
            redirect_url($intended_url);
            exit;
        }
        // L'utente non ha i permessi per accedere alla pagina richiesta
        AuthOSM::clearIntended();
        flash()->warning(tr('Non hai i permessi necessari per accedere alla pagina richiesta.'));
    }

    // Priorità 3: Primo modulo (sistema esistente come fallback)
    $module = AuthOSM::firstModule();

    if (!empty($module)) {
        redirect_url(base_path_osm().'/controller.php?id_module='.$module);
    } else {
        redirect_url(base_path_osm().'/index.php?op=logout');
    }
    exit;
}

// Gestione accesso tramite token OTP
if (!empty($token) && $dbo->isConnected() && $dbo->isInstalled()) {
    redirect_url(base_path_osm().'/token_login.php?token='.urlencode($token));
    exit;
}

// Modalità manutenzione
if (!empty($config['maintenance_ip'])) {
    include_once base_dir().'/include/init/maintenance.php';
}

// Procedura di installazione
include_once base_dir().'/include/init/configuration.php';

// Procedura di aggiornamento
include_once base_dir().'/include/init/update.php';

// Procedura di inizializzazione
include_once base_dir().'/include/init/init.php';

$pageTitle = (!$dbo->isInstalled() || !$dbo->isConnected()) ? tr('Installazione') : tr('Login');

include_once App::filepath('include|custom|', 'top.php');

// Recupera il messaggio di errore dalla variabile di sessione
$error_message = $_SESSION['login_error'] ?? null;
if (!empty($error_message)) {
    // Rimuovi il messaggio dalla sessione dopo averlo recuperato
    unset($_SESSION['login_error']);
}

$login_logo = App::getPaths()['img'].'/logo_completo.png';
$login_logo_setting = setting('Login logo');
if (!empty($login_logo_setting)) {
    try {
        $login_logo_file = Models\Upload::find($login_logo_setting);
    } catch (Exception $e) {
        $login_logo_file = null;
    }
    if (!empty($login_logo_file)) {
        $login_logo = base_path_osm().'/files/impostazioni/'.$login_logo_file->filename;
    }
}

$oauth_providers = [];
if ($dbo->isInstalled() && $dbo->isConnected() && !Update::isUpdateAvailable()) {
    try {
        $oauth_providers = $dbo->fetchArray('SELECT `id`, `name` FROM `zz_oauth2` WHERE `enabled` = 1 AND `is_login` = 1 ORDER BY `name` ASC');
    } catch (QueryException $e) {
        $oauth_providers = [];
    }
}

if ($dbo->isInstalled() && $dbo->isConnected() && !Update::isUpdateAvailable()) {
    include_once App::filepath('include|custom|', 'login.php');
}

$custom_css = $dbo->isInstalled() ? html_entity_decode(setting('CSS Personalizzato')) : '';
if (!empty($custom_css)) {
    echo '
    <style>'.$custom_css.'</style>';
}

include_once App::filepath('include|custom|', 'bottom.php');
