<?php

/**
 * Funzioni fondamentali per il corretto funzionamento del nucleo del progetto.
 *
 * @since 2.3
 */

/**
 * Esegue il redirect.
 *
 * @param string $url
 * @param string $type
 *
 * @return bool
 */
function redirect($url, $type = 'php')
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
 *
 * @return mixed
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
 * @param array $files
 *
 * @return bool
 */
function delete($files)
{
    // Filesystem Symfony
    $fs = new Symfony\Component\Filesystem\Filesystem();

    // Eliminazione
    try {
        $fs->remove($files);
    } catch (Symfony\Component\Filesystem\Exception\IOException $e) {
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
    if (is_dir($path) && is_writable($path)) {
        return true;
    } elseif (!is_dir($path)) {
        // Filesystem Symfony
        $fs = new Symfony\Component\Filesystem\Filesystem();

        // Tentativo di creazione
        try {
            $fs->mkdir($path);

            return true;
        } catch (Symfony\Component\Filesystem\Exception\IOException $e) {
        }
    }

    return false;
}

/**
 * Copy a file, or recursively copy a folder and its contents.
 *
 * @param string       $source  Source path
 * @param string       $dest    Destination path
 * @param array|string $ignores Paths to ingore
 *
 * @return bool Returns TRUE on success, FALSE on failure
 */
function copyr($source, $destination, $ignores = [])
{
    if (!directory($destination)) {
        return false;
    }

    $files = Symfony\Component\Finder\Finder::create()
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
    $fs = new Symfony\Component\Filesystem\Filesystem();
    foreach ($files as $file) {
        $filename = rtrim($destination, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$file->getRelativePathname();

        // Copia
        try {
            $fs->copy($file, $filename);
        } catch (Symfony\Component\Filesystem\Exception\IOException $e) {
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
        if (strpos($_SERVER['HTTP_USER_AGENT'], $key)) {
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

    return $ipaddress;
}

/**
 * Traduce il template semplificato in componenti HTML.
 *
 * @since 2.3
 */
function translateTemplate()
{
    $id_record = filter('id_record');
    $id_parent = filter('id_parent');
    $id_email = filter('id_email');

    $id_module = Modules::getCurrent()['id'];
    $id_plugin = Plugins::getCurrent()['id'];

    $template = ob_get_clean();

    $template = str_replace('$id_module$', $id_module, $template);
    $template = str_replace('$id_plugin$', $id_plugin, $template);
    $template = str_replace('$id_record$', $id_record, $template);

    $template = \HTMLBuilder\HTMLBuilder::replace($template);

    // Informazioni estese sulle azioni dell'utente
    if (!empty(post('op')) && post('op') != 'send-email') {
        operationLog(post('op'));
    }

    // Annullo le notifiche (AJAX)
    if (isAjaxRequest()) {
        flash()->clearMessage('info');
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
    return \Whoops\Util\Misc::isAjaxRequest() && filter('ajax') !== null;
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
        $hash = !starts_with($hash, '#') ? '#'.$hash : $hash;

        if ($backto == 'record-edit') {
            redirect(ROOTDIR.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.$hash);
        } elseif ($backto == 'record-list') {
            redirect(ROOTDIR.'/controller.php?id_module='.$id_module.$hash);
        }

        exit();
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
    return str_replace('"', '&quot;', $string);
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
    return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER['HTTP_USER_AGENT']);
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
    $prefix = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');

    if (substr($path, 0, strlen($prefix)) == $prefix) {
        $path = substr($path, strlen($prefix));
    } else {
        $path = str_replace(DOCROOT, ROOTDIR, $path);
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
 * Registra un'azione specifica nei log.
 *
 * @since 2.4.3
 *
 * @param string $operation
 * @param int    $id_record
 * @param int    $id_module
 * @param int    $id_plugin
 * @param int    $id_parent
 * @param int    $id_email
 * @param array  $options
 */
function operationLog($operation, array $ids = [], array $options = [])
{
    if (!Auth::check()) {
        return false;
    }

    $ids['id_module'] = $ids['id_module'] ?: Modules::getCurrent()['id'];
    $ids['id_plugin'] = $ids['id_plugin'] ?: Plugins::getCurrent()['id'];
    $ids['id_record'] = $ids['id_record'] ?: filter('id_record');
    //$ids['id_parent'] = $ids['id_parent'] ?: filter('id_parent');

    database()->insert('zz_operations', array_merge($ids, [
        'op' => $operation,
        'id_utente' => Auth::user()['id'],

        'options' => !empty($options) ? json_encode($options) : null,
    ]));

    return true;
}
