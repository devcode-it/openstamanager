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

use Ifsnop\Mysqldump\Mysqldump;
use Util\FileSystem;
use Util\Generator;
use Util\Zip;

/**
 * Classe per la gestione dei backup.
 *
 * @since 2.4
 */
class Backup
{
    /** @var string Pattern per i nomi dei backup */
    public const PATTERN = 'OSM backup YYYY-m-d H_i_s AAAAAAA';

    /** @var array Elenco delle variabili che identificano i backup giornalieri */
    protected static $daily_replaces = [
        'YYYY', 'm', 'd',
    ];

    /**
     * Restituisce il percorso su previsto per il salvataggio dei backup.
     *
     * @return string
     */
    public static function getDirectory()
    {
        // Ottieni l'adattatore di archiviazione selezionato
        $adapter = self::getStorageAdapter();
        $backup_dir = base_dir().'/backups';

        // Estrai la directory dalle opzioni dell'adattatore
        if (!empty($adapter)) {
            $options = $adapter->options;
            // Se options è una stringa JSON, decodificala
            if (is_string($options)) {
                // Prova a decodificare il JSON normalmente
                $decoded = json_decode($options, true);

                // Se la decodifica fallisce, prova a gestire il caso specifico con $ nella password
                if ($decoded === null && strpos($options, 'password') !== false) {
                    // Estrai manualmente il valore di root usando espressioni regolari
                    if (preg_match('/"root":"([^"]+)"/', $options, $matches)) {
                        $backup_dir = $matches[1];
                    }
                } else {
                    $options = $decoded ?: [];

                    // Verifica se esiste la chiave 'directory' o 'root'
                    if (!empty($options)) {
                        if (isset($options['directory'])) {
                            $backup_dir = base_dir().$options['directory'];
                        } elseif (isset($options['root'])) {
                            $backup_dir = $options['root'];
                        }
                    }
                }
            }
        }

        // Fallback al percorso di configurazione se disponibile
        if (empty($backup_dir)) {
            $config = App::getConfig();
            $backup_dir = isset($config['backup_dir']) ? $config['backup_dir'] : base_dir().'/backups';
        }

        $result = rtrim((string) $backup_dir, '/');
        if (!directory($result) || !is_writable($result)) {
            // throw new UnexpectedValueException();
        }

        return slashes($result);
    }

    /**
     * Restituisce l'elenco dei backup disponibili.
     *
     * @param string $pattern Eventuale pattern alternativo
     *
     * @return array
     */
    public static function getList($pattern = null)
    {
        $directory = self::getDirectory();
        if (!is_writable($directory) || !is_readable($directory)) {
            return [];
        }

        // Costruzione del pattern
        if (empty($pattern)) {
            $replaces = self::getReplaces();
            $regexs = array_column($replaces, 'regex');

            $pattern = str_replace(array_keys($replaces), array_values($regexs), self::PATTERN);
        }

        // Individuazione dei backup
        $backups = Symfony\Component\Finder\Finder::create()
            ->name('/^'.$pattern.'/')
            ->sortByName()
            ->in($directory)
            ->depth('== 0');

        $results = [];
        foreach ($backups as $backup) {
            $results[] = $backup->getRealPath();
        }

        return $results;
    }

    /**
     * Restituisce i valori utilizzati sulle variabili sostituite.
     *
     * @return array
     */
    public static function readName($string)
    {
        return Generator::read(self::PATTERN, basename((string) $string));
    }

    /**
     * Controlla se il backup giornaliero è stato effettuato.
     *
     * @return bool|null
     */
    public static function isDailyComplete()
    {
        // Costruzione del pattern
        $replaces = self::getReplaces();

        foreach ($replaces as $key => $replace) {
            if (in_array($key, self::$daily_replaces)) {
                $replaces[$key] = $replace['value'];
            } else {
                $replaces[$key] = $replace['regex'];
            }
        }

        $pattern = str_replace(array_keys($replaces), array_values($replaces), self::PATTERN);

        // Individuazione dei backup
        $backups = self::getList($pattern);

        return !empty($backups);
    }

    /**
     * Effettua il backup giornaliero.
     *
     * @return bool|null
     */
    public static function daily()
    {
        // Creazione del backup eventuale
        if (!self::isDailyComplete()) {
            return self::create([]);
        }

        return false;
    }

    /**
     * Esegue il backup del progetto.
     *
     * @param array $ignores eventuali dirs o files da ignorare
     *
     * @return bool
     */
    public static function create($ignores)
    {
        self::checkSpace();

        $backup_dir = self::getDirectory();
        $backup_name = tr(self::getNextName(), ['AAAAAAA' => ($ignores['dirs'] || $ignores['files']) ? 'PARTIAL' : 'FULL']);

        set_time_limit(0);

        // Backup del database
        $database_file = self::getDatabaseDirectory().'/database.sql';
        self::database($database_file);

        // Files e dirs da ignorare di default
        $default_ignores = [
            'files' => [
                'config.inc.php',
                '*.lock',
                '*.phar',
                '*.log',
            ],
            'dirs' => [
                'node_modules',
                'tests',
                'tmp',
                '.git',
                '.github',
                '.config',  // Aggiungi la directory .config per evitare errori di permesso
            ],
        ];

        $ignores = array_merge_recursive($ignores, $default_ignores);

        // Escludo la directory dei backup
        if (string_starts_with($backup_dir, slashes(base_dir()))) {
            $ignores['dirs'][] = basename($backup_dir);
        }

        // Nome del file di backup
        $backup_filename = $backup_name.'.zip';
        $backup_path = $backup_dir.'/'.$backup_filename;

        // Creazione backup in formato ZIP
        if (extension_loaded('zip')) {
            // Verifica se è impostata una password per il backup
            $password = setting('Password di protezione backup');

            // Se è impostata una password e ZipArchive è disponibile, crea un backup protetto da password
            if (!empty($password) && class_exists('ZipArchive')) {
                // Crea un percorso temporaneo per il backup
                $temp_path = $backup_path . '.tmp';

                // Crea prima un backup normale
                $result = Zip::create([
                    base_dir(),
                    self::getDatabaseDirectory(),
                ], $temp_path, $ignores);

                if ($result) {
                    // Crea un nuovo ZIP protetto da password
                    $zip = new \ZipArchive();
                    if ($zip->open($backup_path, \ZipArchive::CREATE) === true) {
                        // Apri il file ZIP originale in lettura
                        $original_zip = new \ZipArchive();
                        if ($original_zip->open($temp_path) === true) {
                            // Imposta la password per il nuovo ZIP
                            $zip->setPassword($password);

                            // Copia tutti i file dal backup originale al nuovo backup protetto da password
                            for ($i = 0; $i < $original_zip->numFiles; $i++) {
                                $stat = $original_zip->statIndex($i);
                                $file_content = $original_zip->getFromIndex($i);

                                // Aggiungi il file al nuovo ZIP
                                $zip->addFromString($stat['name'], $file_content);

                                // Cripta il file con AES-256
                                $zip->setEncryptionIndex($i, \ZipArchive::EM_AES_256, $password);
                            }

                            // Chiudi i file ZIP
                            $original_zip->close();
                            $zip->close();

                            // Rimuovi il file temporaneo
                            unlink($temp_path);
                        } else {
                            // Fallback al metodo normale
                            $zip->close();
                            unlink($backup_path);
                            rename($temp_path, $backup_path);
                        }
                    } else {
                        // Fallback al metodo normale
                        rename($temp_path, $backup_path);
                    }
                }
            } else {
                // Crea un backup normale senza password
                $result = Zip::create([
                    base_dir(),
                    self::getDatabaseDirectory(),
                ], $backup_path, $ignores);
            }
        }
        // Creazione backup attraverso la copia dei file
        else {
            $result = copyr([
                base_dir(),
                self::getDatabaseDirectory(),
            ], $backup_path, $ignores);
        }

        // Rimozione cartella temporanea
        delete($database_file);

        self::cleanup();

        return $result;
    }

    /**
     * Effettua il dump del database.
     *
     * @param string $file
     */
    public static function database($file)
    {
        $config = App::getConfig();

        $dump = new Mysqldump('mysql:host='.$config['db_host'].';dbname='.$config['db_name'], $config['db_username'], $config['db_password'], [
            'add-drop-table' => true,
            'add-locks' => false,
        ]);

        $dump->start($file);
    }

    /**
     * Rimuove i backup più vecchi.
     */
    public static function cleanup()
    {
        $max_backups = intval(setting('Numero di backup da mantenere'));

        $backups = self::getList();
        $count = count($backups);

        // Rimozione dei backup più vecchi
        for ($i = 0; $i < $count - $max_backups; ++$i) {
            delete($backups[$i]);
        }
    }

    /**
     * Ripristina un backup esistente.
     *
     * @param string $path
     * @param bool $cleanup
     * @param string|null $password Password per decriptare il backup (se criptato)
     */
    public static function restore($path, $cleanup = true, $password = null)
    {
        $database = database();

        // Se il backup non è una directory e è stata fornita una password, prova a estrarlo con la password
        if (!is_dir($path) && !empty($password) && class_exists('ZipArchive')) {
            $zip = new \ZipArchive();
            if ($zip->open($path) === true) {
                // Imposta la password per l'estrazione
                $zip->setPassword($password);

                // Estrai il backup in una directory temporanea
                $extraction_dir = sys_get_temp_dir().'/'.basename($path, '.zip');
                if (!directory($extraction_dir)) {
                    mkdir($extraction_dir, 0777, true);
                }

                // Estrai tutti i file
                $zip->extractTo($extraction_dir);
                $zip->close();
            } else {
                // Se non è possibile aprire il file ZIP con la password, prova a estrarlo normalmente
                $extraction_dir = Zip::extract($path);
            }
        } else {
            // Estrai il backup normalmente
            $extraction_dir = is_dir($path) ? $path : Zip::extract($path);
        }

        // TODO: Forzo il log out di tutti gli utenti e ne impedisco il login
        // fino a ripristino ultimato

        // Rimozione del database
        $tables = include base_dir().'/update/tables.php';

        // Ripristino del database
        $database_file = $extraction_dir.'/database.sql';
        if (file_exists($database_file)) {
            $database->query('SET foreign_key_checks = 0');
            foreach ($tables as $table) {
                $database->query('DROP TABLE IF EXISTS `'.$table.'`');
            }
            $database->query('DROP TABLE IF EXISTS `updates`');

            // Ripristino del database
            $database->multiQuery($database_file);
            $database->query('SET foreign_key_checks = 1');
        }

        // Salva il file di configurazione
        $config = file_get_contents(base_dir().'/config.inc.php');

        // Copia i file dalla cartella temporanea alla root
        copyr($extraction_dir, base_dir());

        // Ripristina il file di configurazione dell'installazione
        file_put_contents(base_dir().'/config.inc.php', $config);

        // Pulizia
        if (!empty($cleanup)) {
            delete($extraction_dir);
        }
        delete(base_dir().'/database.sql');
    }

    /**
     * Effettua i controlli relativi allo spazio disponibile per l'esecuzione del backup;.
     */
    public static function checkSpace()
    {
        $scarto = 1.1;

        // Informazioni di base sui limiti di spazio
        $spazio_libero = disk_free_space('.');

        // Calcolo dello spazio necessario per gli allegati (dimensione totale * 2 per garantire margine di sicurezza)
        $database = database();
        $allegati_size = $database->fetchOne('SELECT COALESCE(SUM(`size`), 0) AS total_size FROM `zz_files`');
        $spazio_allegati = $allegati_size['total_size'] * 2; // Raddoppio per garantire spazio sufficiente durante il processo di backup

        // Calcolo dello spazio necessario per il backup
        $spazio_necessario = FileSystem::folderSize(base_dir(), ['htaccess']);
        $cartelle_ignorate = [
            self::getDirectory(),
            'node_modules',
            'tests',
            'tmp',
        ];
        foreach ($cartelle_ignorate as $path) {
            $spazio_necessario -= FileSystem::folderSize($path);
        }

        // Aggiungi lo spazio necessario per gli allegati allo spazio totale necessario
        $spazio_necessario += $spazio_allegati;

        // Controllo semplificato: confronto tra spazio disponibile e spazio necessario
        if ($spazio_libero < $spazio_necessario * $scarto) {
            $spazio_richiesto = FileSystem::formatBytes($spazio_necessario * $scarto);
            $spazio_disponibile = FileSystem::formatBytes($spazio_libero);
            throw new InvalidArgumentException('Spazio disco insufficiente per eseguire il backup. Richiesti: '.$spazio_richiesto.', Disponibili: '.$spazio_disponibile);
        }
    }

    /**
     * Restituisce il percorso su cui salvare temporaneamente il dump del database.
     *
     * @return string
     */
    protected static function getDatabaseDirectory()
    {
        $result = self::getDirectory().'/database';

        if (!directory($result)) {
            throw new UnexpectedValueException();
        }

        return slashes($result);
    }



    /**
     * Restituisce l'elenco delle variabili da sostituire normalizzato per l'utilizzo.
     */
    protected static function getReplaces()
    {
        return Generator::getReplaces();
    }

    /**
     * Restituisce l'adattatore di archiviazione da utilizzare per i backup.
     *
     * @return \Modules\FileAdapters\FileAdapter
     */
    public static function getStorageAdapter()
    {
        $adapter_id = setting('Adattatore archiviazione backup');

        // Se non è stato selezionato un adattatore, utilizzo quello predefinito
        if (empty($adapter_id)) {
            return \Modules\FileAdapters\FileAdapter::getDefaultConnector();
        }

        return \Modules\FileAdapters\FileAdapter::find($adapter_id);
    }

    /**
     * Restituisce il nome previsto per il backup successivo.
     *
     * @return string
     */
    protected static function getNextName()
    {
        return Generator::generate(self::PATTERN);
    }
}
