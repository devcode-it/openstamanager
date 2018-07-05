<?php

/**
 * Classe per la gestione dei backup.
 *
 * @since 2.4
 */
class Backup
{
    /** @var string Pattern per i nomi dei backup */
    const PATTERN = 'OSM backup YYYY-m-d H_i_s';

    /** @var array Elenco delle varabili che identificano i backup giornalieri */
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
        $result = App::getConfig()['backup_dir'];

        $result = rtrim($result, '/');

        if (!is_writable($result) || !directory($result)) {
            throw new UnexpectedValueException();
        }

        return slashes($result);
    }

    /**
     * Restituisce il percorso su cui salvare temporeneamente il dump del database.
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
     * Restituisce l'elenco dei backup disponibili.
     *
     * @param string $pattern Eventuale pattern alternativo
     *
     * @return array
     */
    public static function getList($pattern = null)
    {
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
            ->in(self::getDirectory());

        $results = [];
        foreach ($backups as $backup) {
            $results[] = $backup->getRealPath();
        }

        return $results;
    }

    /**
     * Restituisce l'elenco delle variabili da sostituire normalizzato per l'utilizzo.
     */
    protected static function getReplaces()
    {
        return Util\Generator::getReplaces();
    }

    /**
     * Restituisce il nome previsto per il backup successivo.
     *
     * @return string
     */
    protected static function getNextName()
    {
        return Util\Generator::generate(self::PATTERN);
    }

    /**
     * Restituisce i valori utilizzati sulle variabili sostituite.
     *
     * @return array
     */
    public static function readName($string)
    {
        return Util\Generator::read(self::PATTERN, basename($string));
    }

    /**
     * Effettua il backup giornaliero.
     *
     * @return null|bool
     */
    public static function daily()
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

        // Creazione del backup eventuale
        if (empty($backups)) {
            return self::create();
        }
    }

    /**
     * Esegue il backup del progetto.
     *
     * @return bool
     */
    public static function create()
    {
        $backup_dir = self::getDirectory();
        $backup_name = self::getNextName();

        set_time_limit(0);

        // Backup del database
        $database_file = self::getDatabaseDirectory().'/database.sql';
        self::database($database_file);

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

        // Lista dei file da inserire nel backup
        $files = Symfony\Component\Finder\Finder::create()
            ->files()
            ->exclude($ignores['dirs'])
            ->ignoreDotFiles(true)
            ->ignoreVCS(true)
            ->in(DOCROOT)
            ->in(self::getDatabaseDirectory());

        foreach ($ignores['files'] as $value) {
            $files->notName($value);
        }

        // Creazione backup in formato ZIP
        if (extension_loaded('zip')) {
            $result = self::zipBackup($files, $backup_dir.'/'.$backup_name.'.zip');
        }

        // Creazione backup attraverso la copia dei file
        else {
            $result = self::folderBackup($files, $backup_dir.'/'.$backup_name);
        }

        // Rimozione cartella temporanea
        delete($database_file);

        self::cleanup();

        return $result;
    }

    /**
     * Effettua il backup in formato ZIP.
     *
     * @param Iterator|array $files       File da includere
     * @param string         $destination Nome del file ZIP
     *
     * @return bool
     */
    protected static function zipBackup($files, $destination)
    {
        if (!directory(dirname($destination))) {
            return false;
        }

        $zip = new ZipArchive();

        $result = $zip->open($destination, ZipArchive::CREATE);
        if ($result === true) {
            foreach ($files as $file) {
                $zip->addFile($file, $file->getRelativePathname());
            }

            $zip->close();

            return true;
        }

        return false;
    }

    /**
     * Effettua il backup attraverso la copia dei file.
     *
     * @param array  $files       Elenco dei file da includere
     * @param string $destination Nome della cartella
     *
     * @return bool
     */
    protected static function folderBackup($files, $destination)
    {
        if (!directory($destination)) {
            return false;
        }

        $result = true;

        // Filesystem Symfony
        $fs = new Symfony\Component\Filesystem\Filesystem();
        foreach ($files as $file) {
            $filename = $destination.DIRECTORY_SEPARATOR.$file->getRelativePathname();

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
     * Effettua il dump del database.
     *
     * @param string $file
     */
    public static function database($file)
    {
        $config = App::getConfig();

        $dump = new Ifsnop\Mysqldump\Mysqldump('mysql:host='.$config['db_host'].';dbname='.$config['db_name'], $config['db_username'], $config['db_password'], [
            'add-drop-table' => true,
        ]);

        $dump->start($file);
    }

    /**
     * Rimuove i backup più vecchi.
     */
    public static function cleanup()
    {
        $max_backups = intval(Settings::get('Numero di backup da mantenere'));

        $backups = self::getList();
        $count = count($backups);

        // Rimozione dei backup più vecchi
        for ($i = 0; $i < $count - $max_backups; ++$i) {
            delete($backups[$i]);
        }
    }
}
