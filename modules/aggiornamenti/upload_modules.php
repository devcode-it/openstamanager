<?php

include_once __DIR__.'/../../core.php';

if (!get_var('Attiva aggiornamenti')) {
    die(tr('Accesso negato'));
}

$tmp = $_FILES['blob']['tmp_name'];
$filename = $_FILES['blob']['name'];
$filetype = $_FILES['blob']['type'];
$size = $_FILES['blob']['size'];
$type = $_POST['type'];

if (!extension_loaded('zip')) {
    $_SESSION['errors'][] = tr('Estensione zip non supportata!').'<br>'.tr('Verifica e attivala sul tuo file _FILE_', [
        '_FILE_' => '<b>php.ini</b>'
    ]);
} elseif (!ends_with($filename, '.zip')) {
    $_SESSION['errors'][] = tr('Il file non è un archivio zip!');
} elseif (!empty($tmp) && is_file($tmp)) {
    $zip = new ZipArchive();

    if ($zip->open($tmp)) {
        $tmp_dir = $docroot.'/tmp';

        // Controllo sulla cartella
        directory($tmp_dir);

        $zip->extractTo($tmp_dir);

        // AGGIORNAMENTO
        if ($type == 'update') {
            // Salvo i file di configurazione e versione attuale
            $old_config = file_get_contents($docroot.'/config.inc.php');

            // Aggiornamento del CORE
            if (file_exists($tmp_dir.'/VERSION')) {
                //rename($docroot.'/VERSION', $docroot.'/VERSION.old');

                // Copia i file dalla cartella temporanea alla root
                copyr($tmp_dir, $docroot);

                // Scollego l'utente per eventuali aggiornamenti del db
                Auth::logout();
            }

            // Aggiornamento di un MODULO
            elseif (file_exists($tmp_dir.'/MODULE')) {
                $module_info = parse_ini_file($tmp_dir.'/MODULE', true);
                $module_name = $module_info['module_name'];
                $module_dir = $module_info['module_directory'];

                // Copio i file nella cartella "modules/<nomemodulo>/"
                copyr($tmp_dir, $docroot.'/modules/'.$module_dir.'/');

                // Rinomino il file di versione per forzare l'aggiornamento
                //rename($docroot.'/VERSION_'.$module, $docroot.'/VERSION_'.$module.'.old');

                // Scollego l'utente per eventuali aggiornamenti del db
                Auth::logout();
            } else {
                $_SESSION['errors'][] = tr('File di aggiornamento non riconosciuto!');
            }

            // Ripristino il file di configurazione dell'utente
            file_put_contents($docroot.'/config.inc.php', $old_config);
        }

        // NUOVO MODULO
        elseif ($type == 'new') {
            // Se non c'è il file MODULE non é un modulo
            if (is_file($tmp_dir.'/MODULE')) {
                // Leggo le info dal file di configurazione del modulo
                $module_info = parse_ini_file($tmp_dir.'/MODULE', true);
                $module_name = $module_info['module_name'];
                $module_version = $module_info['module_version'];
                $module_dir = $module_info['module_directory'];

                // Copio i file nella cartella "modules/<nomemodulo>/"
                copyr($tmp_dir, $docroot.'/modules/'.$module_dir.'/');

                // Scollego l'utente per eventuali aggiornamenti del db
                Auth::logout();

                // Sposto il file di versione nella root per forzare l'aggiornamento del db
                file_put_contents($docroot.'/VERSION_'.$module_dir, $module_version);

                // Sposto i file della cartella "lib/" nella root
                $lib_dir = $docroot.'/modules/'.$module_dir.'/lib/';
                if (is_dir($lib_dir)) {
                    copyr($lib_dir, $docroot.'/lib');
                    delete($lib_dir);
                }

                // Sposto i file della cartella "files/" nella root
                $files_dir = $docroot.'/modules/'.$module_dir.'/files/';
                if (is_dir($files_dir)) {
                    copyr($files_dir, $docroot.'/files');
                    delete($files_dir);
                }

                // Inserimento delle voci del modulo nel db per ogni sezione [sezione]
                // Verifico che il modulo non esista già
                $query = 'SELECT name FROM zz_modules WHERE name='.prepare($module_name);
                $n = $dbo->fetchNum($query);

                if ($n == 0) {
                    $query = 'INSERT INTO zz_modules(`name`, `title`, `directory`, `options`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES('.prepare($module_name).', '.prepare($module_name).', '.prepare($module_dir).', '.prepare($module_info['module_options']).', '.prepare($module_info['module_icon']).', '.prepare($module_version).', '.prepare($module_info['module_compatibility']).', "100", '.prepare($module_info['module_parent']).', 0, 1)';
                    $dbo->query($query);
                }
            }

            // File zip non contiene il file MODULE
            else {
                $_SESSION['errors'][] = tr('File di installazione non valido!');
            }
        }

        delete($tmp_dir);
        redirect($rootdir);
    } else {
        $_SESSION['errors'][] = checkZip($tmp);
    }

    $zip->close();
}
