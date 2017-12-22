<?php


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
 * @param unknown $filename
 *
 * @return mixed
 */
function sanitizeFilename($filename)
{
    $filename = str_replace(' ', '-', $filename);
    $filename = preg_replace("/[^A-Za-z0-9_\-\.?!]/", '', $filename);

    return $filename;
}

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
 * @author Aidan Lister <aidan@php.net>
 *
 * @version 1.0.1
 *
 * @see http://aidanlister.com/repos/v/function.copyr.php
 *
 * @param string       $source
 *                              Source path
 * @param string       $dest
 *                              Destination path
 * @param array|string $ignores
 *                              Paths to ingore
 *
 * @return bool Returns TRUE on success, FALSE on failure
 */
function copyr($source, $destination, $ignores = [])
{
    $finder = Symfony\Component\Finder\Finder::create()
        ->files()
        ->exclude((array) $ignores['dirs'])
        ->ignoreDotFiles(true)
        ->ignoreVCS(true)
        ->in($source);

    foreach ((array) $ignores['files'] as $value) {
        $finder->notName($value);
    }

    foreach ($finder as $file) {
        $filename = rtrim($destination, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$file->getRelativePathname();

        // Filesystem Symfony
        $fs = new Symfony\Component\Filesystem\Filesystem();

        // Copia
        try {
            $fs->copy($file, $filename);
        } catch (Symfony\Component\Filesystem\Exception\IOException $e) {
        }
    }

    return true;
}

/**
 * Crea un file zip comprimendo ricorsivamente tutte le sottocartelle a partire da una cartella specificata.
 * *.
 *
 * @param string $source
 * @param string $destination
 * @param array  $ignores
 */
function create_zip($source, $destination, $ignores = [])
{
    if (!extension_loaded('zip')) {
        $_SESSION['errors'][] = tr('Estensione zip non supportata!');

        return false;
    }

    $zip = new ZipArchive();
    $result = $zip->open($destination, ZIPARCHIVE::CREATE);
    if ($result === true && is_writable(dirname($destination))) {
        $finder = Symfony\Component\Finder\Finder::create()
            ->files()
            ->exclude((array) $ignores['dirs'])
            ->ignoreDotFiles(true)
            ->ignoreVCS(true)
            ->in($source);

        foreach ((array) $ignores['files'] as $value) {
            $finder->notName($value);
        }

        foreach ($finder as $file) {
            $zip->addFile($file, $file->getRelativePathname());
        }
        $zip->close();
    } else {
        $_SESSION['errors'][] = tr("Errore durante la creazione dell'archivio!");
    }

    return $result === true;
}

/**
 * Controllo dei file zip e gestione errori.
 *
 * @param unknown $zip_file
 *
 * @return string|bool
 */
function checkZip($zip_file)
{
    $errno = zip_open($zip_file);
    zip_close($errno);

    if (!is_resource($errno)) {
        // using constant name as a string to make this function PHP4 compatible
        $errors = [
            ZIPARCHIVE::ER_MULTIDISK => tr('archivi multi-disco non supportati'),
            ZIPARCHIVE::ER_RENAME => tr('ridenominazione del file temporaneo fallita'),
            ZIPARCHIVE::ER_CLOSE => tr('impossibile chiudere il file zip'),
            ZIPARCHIVE::ER_SEEK => tr('errore durante la ricerca dei file'),
            ZIPARCHIVE::ER_READ => tr('errore di lettura'),
            ZIPARCHIVE::ER_WRITE => tr('errore di scrittura'),
            ZIPARCHIVE::ER_CRC => tr('errore CRC'),
            ZIPARCHIVE::ER_ZIPCLOSED => tr("l'archivio zip è stato chiuso"),
            ZIPARCHIVE::ER_NOENT => tr('file non trovato'),
            ZIPARCHIVE::ER_EXISTS => tr('il file esiste già'),
            ZIPARCHIVE::ER_OPEN => tr('impossibile aprire il file'),
            ZIPARCHIVE::ER_TMPOPEN => tr('impossibile creare il file temporaneo'),
            ZIPARCHIVE::ER_ZLIB => tr('errore nella libreria Zlib'),
            ZIPARCHIVE::ER_MEMORY => tr("fallimento nell'allocare memoria"),
            ZIPARCHIVE::ER_CHANGED => tr('voce modificata'),
            ZIPARCHIVE::ER_COMPNOTSUPP => tr('metodo di compressione non supportato'),
            ZIPARCHIVE::ER_EOF => tr('fine del file non prevista'),
            ZIPARCHIVE::ER_INVAL => tr('argomento non valido'),
            ZIPARCHIVE::ER_NOZIP => tr('file zip non valido'),
            ZIPARCHIVE::ER_INTERNAL => tr('errore interno'),
            ZIPARCHIVE::ER_INCONS => tr('archivio zip inconsistente'),
            ZIPARCHIVE::ER_REMOVE => tr('impossibile rimuovere la voce'),
            ZIPARCHIVE::ER_DELETED => tr('voce eliminata'),
        ];

        if (isset($errors[$errno])) {
            return tr('Errore').': '.$errors[$errno];
        }

        return false;
    } else {
        return true;
    }
}

/**
 * Esegue il backup dell'intero progetto.
 *
 * @return bool
 */
function do_backup($path = null)
{
    global $backup_dir;

    set_time_limit(0);

    $path = !empty($path) ? $path : DOCROOT;

    $backup_name = 'OSM backup '.date('Y-m-d').' '.date('H_i_s');
    if (
        (extension_loaded('zip') && directory($backup_dir.'tmp')) ||
        (!extension_loaded('zip') && directory($backup_dir.$backup_name))
    ) {
        // Backup del database
        $database_file = $backup_dir.(extension_loaded('zip') ? 'tmp' : $backup_name).'/database.sql';
        backup_tables($database_file);

        // Percorsi da ignorare di default
        $ignores = [
            'files' => [
                'config.inc.php',
            ],
            'dirs' => [
                basename($backup_dir),
                '.couscous',
                'node_modules',
                'tests',
            ],
        ];

        // Creazione dello zip
        if (extension_loaded('zip')) {
            $result = create_zip([$path, dirname($database_file)], $backup_dir.$backup_name.'.zip', $ignores);

            // Rimozione cartella temporanea
            delete($database_file);
        }
        // Copia dei file di OSM
        else {
            $result = copyr($path, $backup_dir.$backup_name, $ignores);
        }

        // Eliminazione vecchi backup se ce ne sono
        $max_backups = intval(get_var('Numero di backup da mantenere'));
        // Lettura file di backup
        if ($handle = opendir($backup_dir)) {
            $backups = [];
            while (false !== ($file = readdir($handle))) {
                // I nomi dei file di backup hanno questa forma:
                // OSM backup yyyy-mm-dd HH_ii_ss.zip (oppure solo cartella senza zip)
                if (preg_match('/^OSM backup ([0-9\-]{10}) ([0-9_]{8})/', $file, $m)) {
                    $backups[] = $file;
                }
            }
            closedir($handle);

            if (count($backups) > $max_backups) {
                // Fondo e ordino i backup dal più recente al più vecchio
                arsort($backups);
                $cont = 1;
                foreach ($backups as $backup) {
                    if ($cont > $max_backups) {
                        delete($backup_dir.'/'.$backup);
                    }
                    ++$cont;
                }
            }
        }

        return $result;
    }

    return false;
}

/**
 * Funzione per fare il dump del database.
 *
 * @see http://davidwalsh.name/backup-mysql-database-php
 *
 * @param string $tables
 *
 * @return string
 */
function backup_tables($file)
{
    global $backup_dir;
    global $db_host;
    global $db_name;
    global $db_username;
    global $db_password;

    $dump = new Ifsnop\Mysqldump\Mysqldump('mysql:host='.$db_host.';dbname='.$db_name, $db_username, $db_password, ['add-drop-table' => true]);
    $dump->start($file);
}

/**
 * Individua la differenza tra le date indicate.
 * $interval può essere:
 * yyyy - Number of full years
 * q - Number of full quarters
 * m - Number of full months
 * y - Difference between day numbers
 * (eg 1st Jan 2004 is "1", the first day. 2nd Feb 2003 is "33". The datediff is "-32".)
 * d - Number of full days
 * w - Number of full weekdays
 * ww - Number of full weeks
 * h - Number of full hours
 * n - Number of full minutes
 * s - Number of full seconds (default).
 *
 * @param unknown $interval
 * @param unknown $datefrom
 * @param unknown $dateto
 * @param string  $using_timestamps
 */
function datediff($interval, $datefrom, $dateto, $using_timestamps = false)
{
    if (!$using_timestamps) {
        $datefrom = strtotime($datefrom, 0);
        $dateto = strtotime($dateto, 0);
    }
    $difference = $dateto - $datefrom; // Difference in seconds
    switch ($interval) {
        case 'yyyy': // Number of full years
            $years_difference = floor($difference / 31536000);
            if (mktime(date('H', $datefrom), date('i', $datefrom), date('s', $datefrom), date('n', $datefrom), date('j', $datefrom), date('Y', $datefrom) + $years_difference) > $dateto) {
                --$years_difference;
            }
            if (mktime(date('H', $dateto), date('i', $dateto), date('s', $dateto), date('n', $dateto), date('j', $dateto), date('Y', $dateto) - ($years_difference + 1)) > $datefrom) {
                ++$years_difference;
            }
            $datediff = $years_difference;
            break;
        case 'q': // Number of full quarters
            $quarters_difference = floor($difference / 8035200);
            while (mktime(date('H', $datefrom), date('i', $datefrom), date('s', $datefrom), date('n', $datefrom) + ($quarters_difference * 3), date('j', $dateto), date('Y', $datefrom)) < $dateto) {
                ++$months_difference;
            }
            --$quarters_difference;
            $datediff = $quarters_difference;
            break;
        case 'm': // Number of full months
            $months_difference = floor($difference / 2678400);
            while (mktime(date('H', $datefrom), date('i', $datefrom), date('s', $datefrom), date('n', $datefrom) + ($months_difference), date('j', $dateto), date('Y', $datefrom)) < $dateto) {
                ++$months_difference;
            }
            --$months_difference;
            $datediff = $months_difference;
            break;
        case 'y': // Difference between day numbers
            $datediff = date('z', $dateto) - date('z', $datefrom);
            break;
        case 'd': // Number of full days
            $datediff = floor($difference / 86400);
            break;
        case 'w': // Number of full weekdays
            $days_difference = floor($difference / 86400);
            $weeks_difference = floor($days_difference / 7); // Complete weeks
            $first_day = date('w', $datefrom);
            $days_remainder = floor($days_difference % 7);
            $odd_days = $first_day + $days_remainder; // Do we have a Saturday or Sunday in the remainder?
            if ($odd_days > 7) { // Sunday
                --$days_remainder;
            }
            if ($odd_days > 6) { // Saturday
                --$days_remainder;
            }
            $datediff = ($weeks_difference * 5) + $days_remainder;
            break;
        case 'ww': // Number of full weeks
            $datediff = floor($difference / 604800);
            break;
        case 'h': // Number of full hours
            $datediff = floor($difference / 3600);
            break;
        case 'n': // Number of full minutes
            $datediff = floor($difference / 60);
            break;
        default: // Number of full seconds (default)
            $datediff = $difference;
            break;
    }

    return $datediff;
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
 * Legge una stringa presumibilmente codificata (tipo "8D001") e, se possibile, restituisce il codice successivo ("8D002").
 *
 * @param $str string
 *        	Codice di partenza da incrementare
 * @param $qty int
 *        	Unità da aggiungere alla parte numerica del codice (di default incrementa di 1)
 * @param $mask string
 *        	Specifica i caratteri da sostituire con numeri nel caso di generazione di codici complessi (esempio: se un codice attuale fosse 56/D e volessi calcolare il successivo (57/D), dovrei usare una maschera. La maschera in questo caso potrebbe essere ##/D. In questo modo so che i caratteri ## vanno sostituiti da numeri e il totale di caratteri sarà 2. Quindi il primo codice non sarebbe 1/D, ma 01/D)
 */
function get_next_code($str, $qty = 1, $mask = '')
{
    // Se è il primo codice che sto inserendo sostituisco gli zeri al carattere jolly #
    if ($str == '') {
        $str = str_replace('#', '0', $mask);
    }
    // Se non uso una maschera, estraggo l'ultima parte numerica a destra della stringa e la incremento
    if ($mask == '') {
        preg_match("/(.*?)([\d]*$)/", $str, $m);
        $first_part = $m[1];
        $numeric_part = $m[2];
        // Se non c'è una parte numerica ritorno stringa vuota
        if ($numeric_part == '') {
            return '';
        } else {
            $pad_length = strlen($numeric_part);
            $second_part = str_pad(intval($numeric_part) + $qty, $pad_length, '0', STR_PAD_LEFT);

            return $first_part.$second_part;
        }
    }
    // Utilizzo della maschera
    else {
        // Calcolo prima parte (se c'è)
        $pos1 = strpos($mask, '#');
        $first_part = substr($str, 0, $pos1);
        // Calcolo terza parte (se c'è)
        $pos2 = strlen($str) - strpos(strrev($mask), '#');
        $third_part = substr($str, $pos2, strlen($mask));
        // Calcolo parte numerica
        $numeric_part = substr($str, $pos1, $pos2);
        $pad_length = intval(strlen($numeric_part));
        $first_part_length = intval(strlen($first_part));
        $third_part_length = intval(strlen($third_part));
        $numeric_part = str_pad(intval($numeric_part) + intval($qty), $pad_length, '0', STR_PAD_LEFT);
        // $numeric_part = str_pad( intval($numeric_part)+intval($qty), ( $pad_length - $third_part_length ), "0", STR_PAD_LEFT );
        return $first_part.$numeric_part.$third_part;
    }
}

/**
 * Verifica che il nome del file non sia già usato nella cartella inserita, nel qual caso aggiungo un suffisso.
 *
 * @param unknown $filename
 * @param unknown $dir
 *
 * @return string
 */
function unique_filename($filename, $dir)
{
    $f = pathinfo($filename);
    $suffix = 1;
    while (file_exists($dir.'/'.$filename)) {
        $filename = $f['filename'].'_'.$suffix.'.'.$f['extension'];
        ++$suffix;
    }

    return $filename;
}

/**
 * Crea le thumbnails di $filename da dentro $dir e le salva in $dir.
 *
 * @param unknown $tmp
 * @param unknown $filename
 * @param unknown $dir
 *
 * @return bool
 */
function create_thumbnails($tmp, $filename, $dir)
{
    $infos = pathinfo($filename);
    $name = $infos['filename'];
    $extension = strtolower($infos['extension']);

    if (!directory($dir)) {
        return false;
    }

    $driver = extension_loaded('gd') ? 'gd' : 'imagick';
    Intervention\Image\ImageManagerStatic::configure(['driver' => $driver]);

    $img = Intervention\Image\ImageManagerStatic::make($tmp);

    $img->resize(600, null, function ($constraint) {
        $constraint->aspectRatio();
    });
    $img->save(slashes($dir.'/'.$name.'.'.$extension));

    $img->resize(250, null, function ($constraint) {
        $constraint->aspectRatio();
    });
    $img->save(slashes($dir.'/'.$name.'_thumb250.'.$extension));

    $img->resize(100, null, function ($constraint) {
        $constraint->aspectRatio();
    });
    $img->save(slashes($dir.'/'.$name.'_thumb100.'.$extension));

    return true;
}

/**
 * Ottiene l'indirizzo IP del client.
 *
 * @return string|unknown
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
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ipaddress = $_SERVER['REMOTE_ADDR'];
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
    global $id_module;
    global $id_record;
    global $id_plugin;
    global $id_parent;
    global $operations_log;

    $template = ob_get_clean();

    $template = \HTMLBuilder\HTMLBuilder::replace($template);

    $template = str_replace('$id_module$', $id_module, $template);
    $template = str_replace('$id_plugin$', $id_plugin, $template);
    $template = str_replace('$id_record$', $id_record, $template);
    $template = str_replace('$id_parent$', $id_parent, $template);

    // Completamento delle informazioni estese sulle azioni dell'utente
    if (Auth::check() && !empty($operations_log) && !empty($_SESSION['infos'])) {
        $user = Auth::user();
        $logger = Monolog\Registry::getInstance('logs');

        foreach ($_SESSION['infos'] as $value) {
            $logger->info($value.PHP_EOL.json_encode([
                'user' => $user['username'],
            ]));
        }
    }

    // Annullo le notifiche (AJAX)
    if (isAjaxRequest()) {
        unset($_SESSION['infos']);
    }

    echo $template;
}

/**
 * Sostituisce la prima occorenza di una determinata stringa.
 *
 * @param string $str_pattern
 * @param string $str_replacement
 * @param string $string
 *
 * @since 2.3
 *
 * @return string
 */
function str_replace_once($str_pattern, $str_replacement, $string)
{
    if (strpos($string, $str_pattern) !== false) {
        $occurrence = strpos($string, $str_pattern);

        return substr_replace($string, $str_replacement, strpos($string, $str_pattern), strlen($str_pattern));
    }

    return $string;
}

/**
 * Restituisce il percorso del filesystem in modo indipendete dal sistema operativo.
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
 * Prepara il parametro inserito per l'inserimento in una query SQL.
 * Attenzione: protezione di base contro SQL Injection.
 *
 * @param string $parameter
 *
 * @since 2.3
 *
 * @return string
 */
function prepare($parameter)
{
    return p($parameter);
}

/**
 * Prepara il parametro inserito per l'inserimento in una query SQL.
 * Attenzione: protezione di base contro SQL Injection.
 *
 * @param string $parameter
 *
 * @since 2.3
 *
 * @return string
 */
function p($parameter)
{
    return Database::getConnection()->prepare($parameter);
}

/**
 * Restituisce la traduzione del messaggio inserito.
 *
 * @param string $string
 * @param array  $parameters
 * @param string $domain
 * @param string $locale
 *
 * @since 2.3
 *
 * @return string
 */
function tr($string, $parameters = [], $operations = [])
{
    return Translator::translate($string, $parameters, $operations);
}

// Retrocompatibilità (con la funzione gettext)
if (!function_exists('_')) {
    function _($string, $parameters = [], $operations = [])
    {
        return tr($string, $parameters, $operations);
    }
}

/**
 * Legge il valore di un'impostazione dalla tabella zz_settings.
 * Se descrizione = 1 e il tipo è 'query=' mi restituisce il valore del campo descrizione della query.
 *
 * @param string $name
 * @param string $sezione
 * @param string $descrizione
 *
 * @return mixed
 */
function get_var($nome, $sezione = null, $descrizione = false, $again = false)
{
    return Settings::get($nome, $sezione, $descrizione, $again);
}

/**
 * Restitusice il contentuo sanitarizzato dell'input dell'utente.
 *
 * @param string $param  Nome del paramentro
 * @param string $rule   Regola di filtraggio
 * @param string $method Posizione del paramentro (post o get)
 *
 * @since 2.3
 *
 * @return string
 */
function filter($param, $method = null)
{
    return Filter::getValue($param, $method = null);
}

/**
 * Restitusice il contentuo sanitarizzato dell'input dell'utente.
 *
 * @param string $param Nome del paramentro
 * @param string $rule  Regola di filtraggio
 *
 * @since 2.3
 *
 * @return string
 */
function post($param, $rule = 'text')
{
    return Filter::getValue($param, 'post');
}

/**
 * Restitusice il contentuo sanitarizzato dell'input dell'utente.
 *
 * @param string $param Nome del paramentro
 * @param string $rule  Regola di filtraggio
 *
 * @since 2.3
 *
 * @return string
 */
function get($param, $rule = 'text')
{
    return Filter::getValue($param, 'get');
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
 * Esegue una somma precisa tra due interi/array.
 *
 * @param array|float $first
 * @param array|float $second
 * @param int         $decimals
 *
 * @since 2.3
 *
 * @return float
 */
function sum($first, $second = null, $decimals = null)
{
    $first = (array) $first;
    $second = (array) $second;

    $array = array_merge($first, $second);

    $result = 0;

    if (!is_numeric($decimals)) {
        $decimals = is_numeric($decimals) ? $decimals : Translator::getFormatter()->getPrecision();
    }

    $bcadd = function_exists('bcadd');

    foreach ($array as $value) {
        if ($bcadd) {
            $result = bcadd($result, $value, $decimals);
        } else {
            $result = round($result, $decimals) + round($value, $decimals);
        }
    }

    return floatval($result);
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
 * @return string
 */
function prepareToField($string)
{
    return str_replace('"', '&quot;', $string);
}

/**
 * Restituisce la configurazione dell'installazione.
 *
 * @return array
 */
function getConfig()
{
    include DOCROOT.'/config.inc.php';

    return get_defined_vars();
}

/**
 * Restituisce se l'user-agent (browser web) è una versione mobile.
 *
 * @return bool
 */
function isMobile()
{
    return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER['HTTP_USER_AGENT']);
}
