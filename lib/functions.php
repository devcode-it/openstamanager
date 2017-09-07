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

/**
 * Rimuove ricorsivamente una directory.
 *
 * @param unknown $path
 *
 * @return bool
 */
function deltree($path)
{
    $path = realpath($path);

    if (is_dir($path)) {
        $files = scandir($path);
        if (empty($files)) {
            $files = [];
        }

        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                deltree($path.DIRECTORY_SEPARATOR.$file);
            }
        }

        return rmdir($path);
    } elseif (file_exists($path)) {
        return unlink($path);
    }
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
function copyr($source, $dest, $ignores = [])
{
    $ignores = (array) $ignores;
    foreach ($ignores as $key => $value) {
        $ignores[$key] = slashes($value);
    }

    $path = realpath($source);
    $exclude = !empty(array_intersect($ignores, [slashes($path), slashes($path.'/'), $entry]));

    if ($exclude) {
        return;
    }

    // Simple copy for a file
    if (is_file($source)) {
        return copy($source, $dest);
    }

    // Make destination directory
    if (!is_dir($dest)) {
        create_dir($dest);
    }

    // If the source is a symlink
    if (is_link($source)) {
        $link_dest = readlink($source);

        return symlink($link_dest, $dest);
    }

    // Loop through the folder
    $dir = dir($source);
    while (false !== $entry = $dir->read()) {
        // Skip pointers
        if ($entry == '.' || $entry == '..') {
            continue;
        }

        $path = realpath($source.'/'.$entry.'/');
        $exclude = !empty(array_intersect($ignores, [slashes($path), slashes($path.'/'), $entry]));

        // Deep copy directories
        if (slashes($dest) !== slashes($source.'/'.$entry) && !$exclude) {
            copyr($source.'/'.$entry, $dest.'/'.$entry, $ignores);
        }
    }

    // Clean up
    $dir->close();

    return true;
}

/**
 * Crea un file zip comprimendo ricorsivamente tutte le sottocartelle a partire da una cartella specificata.
 *
 * @see http://stackoverflow.com/questions/1334613/how-to-recursively-zip-a-directory-in-php
 *
 * @param unknown $source
 * @param unknown $destination
 */
function create_zip($source, $destination)
{
    if (!extension_loaded('zip') || !file_exists($source)) {
        $_SESSION['errors'][] = tr('Estensione zip non supportata!');

        return false;
    }

    $destination = slashes($destination);

    $zip = new ZipArchive();
    $result = $zip->open($destination, ZIPARCHIVE::CREATE);
    if ($result === true && is_writable(dirname($destination))) {
        $source = slashes(realpath($source));

        if (is_dir($source) === true) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);

            foreach ($files as $file) {
                $file = slashes(realpath($file));
                $filename = str_replace($source.DIRECTORY_SEPARATOR, '', $file);

                if (is_dir($file) === true) {
                    $zip->addEmptyDir($filename);
                } elseif (is_file($file) === true && $destination != $file) {
                    $zip->addFromString($filename, file_get_contents($file));
                }
            }
        } elseif (is_file($source) === true && $destination != $source) {
            $zip->addFromString(basename($source), file_get_contents($source));
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
function do_backup()
{
    global $backup_dir;

    set_time_limit(0);

    if (extension_loaded('zip')) {
        $tmp_backup_dir = '/tmp/';
    } else {
        $tmp_backup_dir = '/OSM backup '.date('Y-m-d').' '.date('H_i_s').'/';
    }

    // Creazione cartella temporanea
    if (file_exists($backup_dir.$tmp_backup_dir) || create_dir($backup_dir.$tmp_backup_dir)) {
        $do_backup = true;
    } else {
        $do_backup = false;
    }

    if ($do_backup) {
        $database_file = 'database.sql';
        $backup_file = 'OSM backup '.date('Y-m-d').' '.date('H_i_s').'.zip';

        // Dump database
        $dump = "SET foreign_key_checks = 0;\n";
        $dump .= backup_tables();
        $dump .= "SET foreign_key_checks = 1;\n";
        file_put_contents($backup_dir.$tmp_backup_dir.$database_file, $dump);

        // Copia file di OSM (escludendo la cartella di backup)
        copyr(DOCROOT, $backup_dir.$tmp_backup_dir, [slashes($backup_dir), '.svn', '.git', 'config.inc.php', 'node_modules']);

        // Creazione zip
        if (extension_loaded('zip')) {
            if (create_zip($backup_dir.$tmp_backup_dir, $backup_dir.$backup_file)) {
                $_SESSION['infos'][] = tr('Nuovo backup creato!');
            } else {
                $_SESSION['errors'][] = tr('Errore durante la creazione del backup!');
            }

            // Rimozione cartella temporanea
            deltree($backup_dir.$tmp_backup_dir);
        } else {
            $_SESSION['infos'][] = tr('Nuovo backup creato!');
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
                        if (preg_match('/^OSM backup ([0-9\-]{10}) ([0-9_]{8})$/', $backup, $m)) {
                            deltree($backup_dir.'/'.$backup);
                        } elseif (preg_match('/^OSM backup ([0-9\-]{10}) ([0-9_]{8})\.zip$/', $backup, $m)) {
                            unlink($backup_dir.'/'.$backup);
                        }
                    }
                    ++$cont;
                }
            }
        }
    }

    return $do_backup;
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
function backup_tables($tables = '*')
{
    $dbo = Database::getConnection();

    if ($tables == '*') {
        $tables = [];
        $result = $dbo->fetchArray('SHOW TABLES', true);
        if ($result != null) {
            foreach ($result as $res) {
                $tables[] = $res[0];
            }
        }
    } else {
        $tables = is_array($tables) ? $tables : explode(',', $tables);
    }

    // Eliminazione di tutte le tabelle
    foreach ($tables as $table) {
        $return .= "DROP TABLE IF EXISTS `$table`;\n";
    }

    // Ricreazione della struttura di ogni tabella e ri-popolazione database
    foreach ($tables as $table) {
        $result = $dbo->fetchArray('SELECT * FROM '.$table, true);
        $num_fields = count($result[0]);

        $row2 = $dbo->fetchArray('SHOW CREATE TABLE '.$table);
        $return .= "\n".$row2[1].";\n";

        for ($i = 0; $i < $num_fields; ++$i) {
            foreach ($result as $row) {
                $return .= 'INSERT INTO '.$table.' VALUES(';

                for ($j = 0; $j < $num_fields; ++$j) {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = str_replace("\r\n", '\\n', $row[$j]);
                    $row[$j] = str_replace("\n", '\\n', $row[$j]);

                    if (isset($row[$j])) {
                        $return .= '"'.$row[$j].'"';
                    } else {
                        $return .= '""';
                    }

                    if ($j < ($num_fields - 1)) {
                        $return .= ',';
                    }
                }

                $return .= ");\n";
            }
        }

        $return .= "\n";
    }

    return $return;
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

    if ((is_dir($dir) && !is_writable($dir)) || (!is_dir($dir) && !create_dir($dir))) {
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

    $template = ob_get_clean();

    $template = \HTMLBuilder\HTMLBuilder::replace($template);

    $template = str_replace('$id_module$', $id_module, $template);
    $template = str_replace('$id_plugin$', $id_plugin, $template);
    $template = str_replace('$id_record$', $id_record, $template);

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
function tr($string, $parameters = [], $domain = null, $locale = null)
{
    return Translator::translate($string, $parameters, $domain, $locale);
}

// Retrocompatibilità
if (!function_exists('_')) {
    function _($string, $parameters = [], $domain = null, $locale = null)
    {
        return tr($string, $parameters, $domain, $locale);
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
 * @return float
 */
function sum($first, $second = null, $decimals = null)
{
    $first = (array) $first;
    $second = (array) $second;

    $array = array_merge($first, $second);

    $result = 0;

    if (!is_numeric($decimals)) {
        $decimals = is_numeric($decimals) ? $decimals : Settings::get('Cifre decimali per importi');
    }

    $bcadd = function_exists('bcadd');

    foreach ($array as $value) {
        if ($bcadd) {
            $result = bcadd($result, $value, $decimals);
        } else {
            $result = round($result, $decimals) + round($value, $decimals);
        }
    }

    return $result;
}

function redirectOperation()
{
    $id_module = filter('id_module');
    $id_record = filter('id_record');

    $backto = filter('backto');
    // Scelta del redirect dopo un submit
    if (!empty($backto)) {
        $hash = filter('hash');
        $hash = !starts_with($hash, '#') ? '#'.$hash : $hash;
        if ($backto == 'record-edit') {
            redirect(ROOTDIR.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.$hash);
            exit();
        } elseif ($backto == 'record-list') {
            redirect(ROOTDIR.'/controller.php?id_module='.$id_module.$hash);
            exit();
        }
    }
}

function create_dir($path)
{
    return mkdir($path, 0777, true);
}
