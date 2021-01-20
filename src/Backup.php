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
    const PATTERN = 'OSM backup YYYY-m-d H_i_s';

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
        $result = App::getConfig()['backup_dir'];

        $result = rtrim($result, '/');
        if (!directory($result) || !is_writable($result)) {
            //throw new UnexpectedValueException();
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
        return Generator::read(self::PATTERN, basename($string));
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
            return self::create();
        }

        return false;
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
                'node_modules',
                'tests',
                'tmp',
            ],
        ];

        if (string_starts_with($backup_dir, slashes(base_dir()))) {
            $ignores['dirs'][] = basename($backup_dir);
        }

        // Creazione backup in formato ZIP
        if (extension_loaded('zip')) {
            $result = Zip::create([
                base_dir(),
                self::getDatabaseDirectory(),
            ], $backup_dir.'/'.$backup_name.'.zip', $ignores);
        }

        // Creazione backup attraverso la copia dei file
        else {
            $result = copyr([
                base_dir(),
                self::getDatabaseDirectory(),
            ], $backup_dir.'/'.$backup_name.'.zip', $ignores);
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
     */
    public static function restore($path, $cleanup = true)
    {
        $database = database();
        $extraction_dir = is_dir($path) ? $path : Zip::extract($path);

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
     * Restituisce il nome previsto per il backup successivo.
     *
     * @return string
     */
    protected static function getNextName()
    {
        return Generator::generate(self::PATTERN);
    }
}
