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

/*
 * Funzioni fondamentali per il corretto funzionamento del nucleo del progetto.
 *
 * @since 2.3
 */

use HTMLBuilder\HTMLBuilder;
use Models\OperationLog;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use Symfony\Component\Finder\Finder;
use Util\FileSystem;

/**
 * Esegue il redirect.
 *
 * @param string $url
 * @param string $type
 *
 * @return bool
 */
function redirect_url($url, $type = 'php')
{
    switch ($type) {
        case 'php':
            header('Location: '.$url);
            break;
        case 'js':
            echo '<script type="text/javascript">location.href="'.$url.'";</script>';
            break;
    }
}

/**
 * Verifica e corregge il nome di un file.
 *
 * @param string $filename
 */
function sanitizeFilename($filename)
{
    $filename = str_replace(' ', '-', $filename);
    $filename = preg_replace("/[^A-Za-z0-9_\-\.?!]/", '', $filename);

    return $filename;
}

/**
 * Elimina i file indicati.
 *
 * @param array|string $files
 *
 * @return bool
 */
function delete($files)
{
    // Filesystem Symfony
    $fs = new SymfonyFilesystem();

    // Eliminazione
    try {
        $fs->remove($files);
    } catch (IOException) {
        return false;
    }

    return true;
}

/**
 * Controlla l'esistenza e i permessi di scrittura sul percorso indicato.
 *
 * @param string $path
 *
 * @return bool
 */
function directory($path)
{
    return FileSystem::directory($path);
}

/**
 * Copy a file, or recursively copy a folder and its contents.
 *
 * @param array|string $source  Source path
 * @param array|string $ignores Paths to ingore
 *
 * @return bool Returns TRUE on success, FALSE on failure
 */
function copyr($source, $destination, $ignores = [])
{
    if (!directory($destination)) {
        return false;
    }

    $files = Finder::create()
        ->files()
        ->exclude((array) $ignores['dirs'])
        ->ignoreDotFiles(false)
        ->ignoreVCS(true)
        ->in($source);

    foreach ((array) $ignores['files'] as $value) {
        $files->notName($value);
    }

    $result = true;

    // Filesystem Symfony
    $fs = new SymfonyFilesystem();

    try {
        $fs->chmod($destination, 0777, 0000, true);
    } catch (IOException) {
    }

    foreach ($files as $file) {
        $filename = rtrim((string) $destination, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$file->getRelativePathname();

        // Copia effettiva del file
        try {
            $fs->copy($file, $filename, true);
        } catch (IOException) {
            $result = false;
        }
    }

    return $result;
}

/**
 * Recupera informazioni sistema operativo dell'utente.
 *
 * @return string
 */
function getOS()
{
    $os = [
        'Windows NT 6.1' => 'Windows 7',
        'Windows NT 6.0' => 'Windows Vista',
        'Windows NT 5.1' => 'Windows XP',
        'Windows NT 5.0' => 'Windows 2000',
        'Windows NT 4.90' => 'Windows ME',
        'Win95' => 'Windows 95',
        'Win98' => 'Windows 98',
        'Windows NT 5.2' => 'Windows NET',
        'WinNT4.0' => 'Windows NT',
        'Mac' => 'Mac',
        'PPC' => 'Mac',
        'Linux' => 'Linux',
        'FreeBSD' => 'FreeBSD',
        'SunOS' => 'SunOS',
        'Irix' => 'Irix',
        'BeOS' => 'BeOS',
        'OS/2' => 'OS/2',
        'AIX' => 'AIX',
    ];

    foreach ($os as $key => $value) {
        if (strpos((string) $_SERVER['HTTP_USER_AGENT'], $key)) {
            return $value;
        }
    }

    return tr('Altro');
}

/**
 * Ottiene l'indirizzo IP del client.
 *
 * @return string
 */
function get_client_ip()
{
    $ipaddress = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    } elseif (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    } elseif (!empty($_SERVER['REMOTE_ADDR']) and $_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    } elseif (!empty(gethostbyname(gethostname()))) {
        $ipaddress = gethostbyname(gethostname());
    } else {
        $ipaddress = 'UNKNOWN';
    }

    return strip_tags((string) $ipaddress);
}

/**
 * Traduce il template semplificato in componenti HTML.
 *
 * @since 2.3
 */
function translateTemplate()
{
    $id_record = filter('id_record') ?: get('id_record');
    $id_parent = filter('id_parent');

    $module = Modules::getCurrent();
    $plugin = Plugins::getCurrent();
    $id_module = $module ? $module['id'] : null;
    $id_plugin = $plugin ? $plugin['id'] : null;

    $template = ob_get_clean();

    $replaces = [
        '$id_module$' => $id_module,
        '$id_plugin$' => $id_plugin,
        '$id_record$' => $id_record,
    ];

    $template = replace($template, $replaces);
    $template = $template ? HTMLBuilder::replace($template) : $template;
    $template = replace($template, $replaces);

    // Informazioni estese sulle azioni dell'utente
    $op = post('op');
    if (!empty($op) && $op != 'summable-results' && $op != 'avg-results') {
        OperationLog::setInfo('id_module', $id_module);
        OperationLog::setInfo('id_plugin', $id_plugin);
        OperationLog::setInfo('id_record', $id_record);

        OperationLog::build($op);
    }

    // Retrocompatibilità
    if (!empty($_SESSION['infos'])) {
        foreach ($_SESSION['infos'] as $message) {
            flash()->info($message);
        }
    }
    if (!empty($_SESSION['warnings'])) {
        foreach ($_SESSION['warnings'] as $message) {
            flash()->warning($message);
        }
    }
    if (!empty($_SESSION['errors'])) {
        foreach ($_SESSION['errors'] as $message) {
            flash()->error($message);
        }
    }

    // Annullo le notifiche (AJAX)
    if (isAjaxRequest()) {
        // flash()->clearMessage('info');
    }

    echo $template;
}

/**
 * Restituisce il percorso del filesystem in modo indipendente dal sistema operativo.
 *
 * @param string $string Percorso da correggere
 *
 * @since 2.3
 *
 * @return string
 */
function slashes($string)
{
    return str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $string);
}

/**
 * Controlla se è in corso una richiesta AJAX generata dal progetto.
 *
 * @since 2.3
 *
 * @return bool
 */
function isAjaxRequest()
{
    return Whoops\Util\Misc::isAjaxRequest() && filter('ajax') !== null && filter('ajax') !== '';
}

/**
 * Effettua le operazioni automatiche di redirect tra le pagine.
 *
 * @param int $id_module
 * @param int $id_record
 *
 * @since 2.3
 */
function redirectOperation($id_module, $id_record)
{
    $backto = filter('backto');

    // Scelta del redirect dopo un submit
    if (!empty($backto)) {
        $hash = filter('hash');
        $hash = !string_starts_with($hash, '#') ? '#'.$hash : $hash;
        $hash = $hash == '#tab_0' ? '' : $hash;

        if ($backto == 'record-edit') {
            redirect_url(base_path().'/editor.php?id_module='.$id_module.'&id_record='.$id_record.$hash);
        } elseif ($backto == 'record-list') {
            redirect_url(base_path().'/controller.php?id_module='.$id_module.$hash);
        }

        exit;
    }
}

/**
 * Predispone un testo per l'inserimento all'interno di un attributo HTML.
 *
 * @param string $string
 *
 * @since 2.3
 *
 * @return string
 */
function prepareToField($string)
{
    return $string ? str_replace('"', '&quot;', $string) : $string;
}

/**
 * Restituisce se l'user-agent (browser web) è una versione mobile.
 *
 * @since 2.3
 *
 * @return bool
 */
function isMobile()
{
    return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", (string) $_SERVER['HTTP_USER_AGENT']);
}

/**
 * Restituisce il percorso derivante dal file in esecuzione.
 *
 * @since 2.4.1
 *
 * @return string
 */
function getURLPath()
{
    $path = $_SERVER['SCRIPT_FILENAME'];
    $prefix = rtrim((string) $_SERVER['DOCUMENT_ROOT'], '/\\');

    if (str_starts_with((string) $path, $prefix)) {
        $path = substr((string) $path, strlen($prefix));
    } else {
        $path = str_replace(base_dir(), base_path(), $path);
    }

    return slashes($path);
}

/**
 * Sostituisce i caratteri speciali per la ricerca attraverso le tabelle Datatables.
 *
 * @since 2.4.2
 *
 * @param string $field
 *
 * @return string
 */
function searchFieldName($field)
{
    return str_replace([' ', '.'], ['-', ''], $field);
}

/**
 * Rimuove spazi e caratteri speciali da una stringa.
 *
 * @param string $string
 * @param string $permitted
 *
 * @since 2.4.6
 *
 * @return string
 */
function clean($string, $permitted = '')
{
    return preg_replace('/[^A-Za-z0-9'.$permitted.']/', '', $string); // Removes special chars.
}

function check_query($query)
{
    $query = mb_strtoupper((string) $query);

    $blacklist = ['INSERT', 'UPDATE', 'TRUNCATE', 'DELETE', 'DROP', 'GRANT', 'CREATE', 'REVOKE'];
    foreach ($blacklist as $value) {
        if (preg_match("/\b".preg_quote($value)."\b/", $query)) {
            return false;
        }
    }

    return true;
}

/**
 * Restituisce il valore corrente di un parametro della sessione.
 *
 * @param string     $name    Nome del parametro in dot-notation
 * @param mixed|null $default
 *
 * @return array|mixed|null
 */
function session_get($name, $default = null)
{
    $session = &$_SESSION;
    if (empty($name)) {
        return $default;
    }

    $pieces = explode('.', $name);
    foreach ($pieces as $piece) {
        if (!isset($session[$piece])) {
            return $default;
        }

        $session = &$session[$piece];
    }

    return $session ?? $default;
}

/**
 * Imposta un parametro nella sessione secondo un nome indicato.
 *
 * @param string $name Nome del parametro in dot-notation
 *
 * @return void
 */
function session_set($name, mixed $value)
{
    $session = &$_SESSION;

    if (!empty($name)) {
        $pieces = explode('.', $name);
        foreach ($pieces as $piece) {
            if (!isset($session[$piece])) {
                $session[$piece] = [];
            }

            $session = &$session[$piece];
        }
    }

    $session = $value;
}

/**
 * Restituisce l'URL completo per il gestionale.
 *
 * @return string
 */
function base_url()
{
    return App::$baseurl;
}

/**
 * Restituisce l'URL parziale per il gestionale.
 *
 * @return string
 */
function base_path()
{
    return App::$rootdir;
}

/**
 * Restituisce il percorso per la cartella principale del gestionale.
 *
 * @return string
 */
function base_dir()
{
    return App::$docroot;
}
