<?php

/**
 * Classe dedicata alla gestione delle procedure di aggiornamento del database del progetto.
 *
 * @since 2.3
 */
class Update
{
    /** @var array Lista degli aggiornamenti da completare */
    protected static $updates;

    protected static function prepareToUpdate()
    {
        $database = Database::getConnection();

        $database_ready = $database->isConnected() && $database->fetchNum("SHOW TABLES LIKE 'updates'");

        // Individuazione di tutti gli aggiornamenti fisicamente presenti
        $results = [];

        // Aggiornamenti del gestionale
        $core = (array) glob(DOCROOT.'/update/*.{php,sql}', GLOB_BRACE);
        foreach ($core as $value) {
            $infos = pathinfo($value);
            $value = str_replace('_', '.', $infos['filename']);

            if (self::isVersion($value)) {
                $results[] = $value;
            }
        }

        // Aggiornamenti dei moduli
        $modules = (array) glob(DOCROOT.'/modules/*/update/*.{php,sql}', GLOB_BRACE);
        foreach ($modules as $value) {
            $infos = pathinfo($value);

            $module = end(explode('/', dirname($infos['dirname'])));

            $value = str_replace('_', '.', $infos['filename']);

            if (self::isVersion($value)) {
                $results[] = $module.'_'.$value;
            }
        }

        $results = array_unique($results);
        asort($results);

        // Individuazione di tutti gli aggiornamenti inseriti
        $updates = ($database_ready) ? $database->fetchArray('SELECT * FROM `updates`') : [];
        $versions = array_column($updates, 'version');

        $reset = count(array_intersect($results, $versions)) != count($results);

        if ($reset && $database->isConnected()) {
            // Individua le versioni che sono state installate, anche solo parzialmente
            $done = ($database_ready) ? $database->fetchArray('SELECT version, done FROM updates WHERE `done` IS NOT NULL') : [];

            // Reimpostazione della tabella degli aggiornamenti
            $create = DOCROOT.'/update/create_updates.sql';
            if (file_exists($create)) {
                $database->query('DROP TABLE IF EXISTS `updates`');
                $database->multiQuery($create);
            }

            // Inserimento degli aggiornamenti individuati
            foreach ($results as $result) {
                // Individuazione di script e sql
                $temp = explode('_', $result);
                $file = DOCROOT.((str_contains($result, '_')) ? '/modules/'.implode('_', explode('_', $result, -1)) : '').'/update/'.str_replace('.', '_', end($temp));

                $sql = file_exists($file.'.sql') ? 1 : 0;
                $script = file_exists($file.'.php') ? 1 : 0;

                // Reimpostazione degli stati per gli aggiornamenti precedentemente presenti
                $pos = array_search($result, $versions);
                $done = ($pos !== false) ? prepare($updates[$pos]['done']) : 'NULL';

                $database->query('INSERT INTO `updates` (`version`, `sql`, `script`, `done`) VALUES ('.prepare($result).', '.prepare($sql).', '.prepare($script).', '.$done.')');
            }

            // Normalizzazione di charset e collation
            self::normalizeDatabase($database->getDatabaseName());
        }
    }

    public static function getTodos()
    {
        if (!is_array(self::$updates)) {
            self::prepareToUpdate();

            $database = Database::getConnection();

            $updates = $database->isConnected() ? $database->fetchArray('SELECT * FROM `updates` WHERE `done` != 1 OR `done` IS NULL ORDER BY `done` DESC, `id` ASC') : [];

            foreach ($updates as $key => $value) {
                $updates[$key]['name'] = ucwords(str_replace('_', ' ', $value['version']));

                $temp = explode('_', $value['version']);
                $updates[$key]['filename'] = str_replace('.', '_', end($temp));

                $updates[$key]['directory'] = ((str_contains($value['version'], '_')) ? '/modules/'.implode('_', explode('_', $value['version'], -1)) : '').'/update/';
            }

            self::$updates = $updates;
        }

        return self::$updates;
    }

    public static function getUpdate()
    {
        if (!empty(self::getTodos())) {
            return self::getTodos()[0];
        }
    }

    public static function isVersion($string)
    {
        return preg_match('/^\d+(?:\.\d+)+$/', $string);
    }

    public static function isUpdateAvailable()
    {
        return !empty(self::getTodos());
    }

    public static function isUpdateCompleted()
    {
        return !self::isUpdateAvailable();
    }

    public static function isUpdateLocked()
    {
        $todos = array_column(self::getTodos(), 'done');
        foreach ($todos as $todo) {
            if ($todo !== null && $todo !== 1) {
                return true;
            }
        }

        return false;
    }

    public static function getDatabaseVersion()
    {
        $database = Database::getConnection();

        $results = $database->fetchArray("SELECT version FROM `updates` WHERE version NOT LIKE '%\_%' ORDER BY version DESC LIMIT 1");

        return $results[0]['version'];
    }

    public static function getVersion()
    {
        return self::getFile('VERSION');
    }

    public static function getRevision()
    {
        return self::getFile('REVISION');
    }

    protected static function getFile($file)
    {
        $file = (str_contains($file, DOCROOT.DIRECTORY_SEPARATOR)) ? $file : DOCROOT.DIRECTORY_SEPARATOR.$file;

        $result = '';

        $filepath = realpath($file);
        if (!empty($filepath)) {
            $result = file_get_contents($filepath);
            $result = str_replace(["\r\n", "\n"], '', $result);
        }

        return trim($result);
    }

    public static function updateCleanup()
    {
        if (self::isUpdateCompleted()) {
            $database = Database::getConnection();

            // Aggiornamento all'ultima release della versione e compatibilità moduli
            $database->query('UPDATE `zz_modules` SET `compatibility`='.prepare(self::getVersion()).', `version`='.prepare(self::getVersion()).' WHERE `default` = 1');

            // Normalizzazione di charset e collation
            self::normalizeDatabase($database->getDatabaseName());

            return true;
        }

        return false;
    }

    public static function doUpdate($rate = 20)
    {
        global $logger;

        set_time_limit(0);
        ignore_user_abort(true);

        if (!self::isUpdateCompleted()) {
            $update = self::getUpdate();

            $file = DOCROOT.$update['directory'].$update['filename'];

            $database = Database::getConnection();

            try {
                // Esecuzione query release
                if (!empty($update['sql']) && (!empty($update['done']) || is_null($update['done'])) && file_exists($file.'.sql')) {
                    $queries = readSQLFile($file.'.sql', ';');
                    $count = count($queries);

                    $start = empty($update['done']) ? 0 : $update['done'] - 2;
                    $end = ($start + $rate + 1) > $count ? $count : $start + $rate + 1;

                    if ($start < $end) {
                        for ($i = $start; $i < $end; ++$i) {
                            $database->query($queries[$i], _('Aggiornamento fallito').': '.$queries[$i]);

                            $database->query('UPDATE `updates` SET `done` = '.prepare($i + 3).' WHERE id = '.prepare($update['id']));
                        }

                        return [
                            $start,
                            $end,
                            $count,
                        ];
                    }
                }

                $database->query('UPDATE `updates` SET `done` = 0 WHERE id = '.prepare($update['id']));

                // Esecuzione script release
                if (!empty($update['script']) && file_exists($file.'.php')) {
                    self::executeScript($file.'.php');
                }

                $database->query('UPDATE `updates` SET `done` = 1 WHERE id = '.prepare($update['id']));

                // Normalizzazione di charset e collation
                self::normalizeDatabase($database->getDatabaseName());

                return true;
            } catch (\Exception $e) {
                $logger->addRecord(\Monolog\Logger::EMERGENCY, $e->getMessage());
            }

            return false;
        }
    }

    protected static function normalizeDatabase($database_name)
    {
        set_time_limit(0);
        ignore_user_abort(true);

        $database = Database::getConnection();

        $mysql_ver = $database->getMySQLVersion();

        if (version_compare($mysql_ver, '5.5.3') >= 0) {
            $character_set = 'utf8mb4';
            $collation = 'utf8mb4_general_ci';
        } else {
            $character_set = 'utf8';
            $collation = 'utf8_general_ci';
        }

        // Normalizzazione del database (le nuove tabelle verranno automaticamente impostate secondo la codifica predefinita)
        $default_collation = $database->fetchArray('SELECT DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = '.prepare($database_name).' LIMIT 1')[0]['DEFAULT_COLLATION_NAME'];

        if ($default_collation != $collation) {
            $database->query('ALTER DATABASE `'.$database_name.'` CHARACTER SET '.$character_set.' COLLATE '.$collation);
        }

        // Normalizzazione delle tabelle
        $tables = $database->fetchArray('SHOW TABLE STATUS IN `'.$database_name.'` WHERE Collation != '.prepare($collation)." AND Name != 'updates'");

        if (!empty($tables)) {
            $database->query('SET foreign_key_checks = 0');

            // Conversione delle tabelle
            foreach ($tables as $table) {
                $database->query('ALTER TABLE `'.$table['Name'].'` CONVERT TO CHARACTER SET '.$character_set.' COLLATE '.$collation);
            }

            $database->query('SET foreign_key_checks = 1');
        }
    }

    protected static function executeScript($script)
    {
        include __DIR__.'/../../core.php';

        $database = $dbo;

        // Informazioni relative a MySQL
        $mysql_ver = $database->getMySQLVersion();

        include $script;
    }
}
