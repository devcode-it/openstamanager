<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

/**
 * Classe dedicata alla gestione delle procedure di aggiornamento del database del progetto.
 *
 * @since 2.3
 */
class Update
{
    protected static $current_version;

    /** @var array Elenco degli aggiornamenti da completare */
    protected static $updates;
    /** @var array Percorsi da controllare per gli aggiornamenti */
    protected static $directories = [
        'modules',
        'plugins',
    ];

    /**
     * Restituisce l'elenco degli aggiornamento incompleti o non ancora effettuati.
     *
     * @return array
     */
    public static function getTodoUpdates()
    {
        if (!is_array(self::$updates)) {
            self::prepareToUpdate();

            $database = database();

            $updates = $database->isConnected() ? $database->fetchArray('SELECT * FROM `updates` WHERE `done` != 1 OR `done` IS NULL ORDER BY `done` DESC, `id` ASC') : [];

            foreach ($updates as $key => $value) {
                $name = explode('/', $value['directory']);
                $updates[$key]['name'] = ucwords(end($name)).' '.$value['version'];

                $updates[$key]['filename'] = str_replace('.', '_', $value['version']);

                $updates[$key]['directory'] = $value['directory'].'/update/';
            }

            self::$updates = $updates;
        }

        return self::$updates;
    }

    /**
     * Restituisce il primo aggiornamento che deve essere completato.
     *
     * @return array
     */
    public static function getCurrentUpdate()
    {
        $todos = self::getTodoUpdates();

        return !empty($todos) ? $todos[0] : null;
    }

    /**
     * Controlla che la stringa inserita possieda una struttura corrispondente a quella di una versione.
     *
     * @param string $string
     *
     * @return bool
     */
    public static function isVersion($string)
    {
        return preg_match('/^\d+(?:\.\d+)+$/', $string) === 1;
    }

    /**
     * Controlla ci sono aggiornamenti da fare per il database.
     *
     * @return bool
     */
    public static function isUpdateAvailable()
    {
        $todos = self::getTodoUpdates();

        return !empty($todos);
    }

    /**
     * Controlla se la procedura di aggiornamento è conclusa.
     *
     * @return bool
     */
    public static function isUpdateCompleted()
    {
        return !self::isUpdateAvailable();
    }

    /**
     * Controlla se l'aggiornamento è in esecuzione.
     *
     * @return bool
     */
    public static function isUpdateLocked()
    {
        $todos = array_column(self::getTodoUpdates(), 'done');
        foreach ($todos as $todo) {
            if ($todo !== null && $todo !== 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Restituisce la versione corrente del software gestita dal database.
     *
     * @return string
     */
    public static function getDatabaseVersion()
    {
        if (!isset(self::$current_version)) {
            $database = database();

            $results = $database->fetchArray("SELECT version FROM `updates` WHERE version NOT LIKE '%\_%' ORDER BY INET_ATON(SUBSTRING_INDEX(CONCAT(version,'.0.0.0'),'.',4)) DESC LIMIT 1");
            self::$current_version = $results[0]['version'];
        }

        return self::$current_version;
    }

    /**
     * Restituisce la versione corrente del software (file VERSION nella root e versione a database).
     *
     * @return string
     */
    public static function getVersion()
    {
        $result = self::getFile('VERSION');

        if (empty($result)) {
            $database = database();

            if ($database->isInstalled()) {
                $result = self::getDatabaseVersion();
            } else {
                $updatelist = self::getCoreUpdates();
                $result = end($updatelist)['version'];
            }
        }

        return $result;
    }

    /**
     * Controlla se la versione corrente del software è una beta (versione instabile).
     *
     * @return bool
     */
    public static function isBeta()
    {
        $version = self::getVersion();

        return str_contains($version, 'beta');
    }

    /**
     * Restituisce la revisione corrente del software gestita dal file system (file REVISION nella root).
     *
     * @return string
     */
    public static function getRevision()
    {
        return self::getFile('REVISION');
    }

    /**
     * Effettua una pulizia del database a seguito del completamento dell'aggiornamento.
     *
     * @return bool
     */
    public static function updateCleanup()
    {
        if (self::isUpdateCompleted()) {
            $database = database();

            // Aggiornamento all'ultima release della versione e compatibilità moduli
            $database->query('UPDATE `zz_modules` SET `compatibility`='.prepare(self::getVersion()).', `version`='.prepare(self::getVersion()).' WHERE `default` = 1');

            // Normalizzazione di charset e collation
            self::normalizeDatabase($database->getDatabaseName());

            if (class_exists('\Models\Cache')) {
                \Models\Cache::pool('Ultima versione di OpenSTAManager disponibile')->set(null);
            }

            return true;
        }

        return false;
    }

    /**
     * Esegue una precisa sezione dell'aggiornamento fa fare, partendo dalle query e passando poi allo script relativo.
     * Prima dell'esecuzione dello script viene inoltre eseguita un'operazione di normalizzazione dei campi delle tabelle del database finalizzata a generalizzare la gestione delle informazioni per l'API: vengono quindi aggiunti i campi <b>created_at</b> e, se permesso dalla versione di MySQL, <b>updated_at</b> ad ogni tabella registrata del software.
     *
     * @param int $rate Numero di singole query da eseguire dell'aggiornamento corrente
     *
     * @return array|bool
     */
    public static function doUpdate($rate = 20)
    {
        set_time_limit(0);
        ignore_user_abort(true);

        if (!self::isUpdateCompleted()) {
            $update = self::getCurrentUpdate();

            $file = base_dir().'/'.$update['directory'].$update['filename'];

            $database = database();

            try {
                // Esecuzione delle query
                if (!empty($update['sql']) && (!empty($update['done']) || is_null($update['done'])) && file_exists($file.'.sql')) {
                    $queries = readSQLFile($file.'.sql', ';');
                    $count = count($queries);

                    $start = empty($update['done']) ? 0 : $update['done'] - 2;
                    $end = ($start + $rate + 1) > $count ? $count : $start + $rate + 1;

                    if ($start < $end) {
                        for ($i = $start; $i < $end; ++$i) {
                            try {
                                $database->query($queries[$i]);
                            } catch (\Exception $e) {
                                throw new PDOException(tr('Aggiornamento fallito').': '.$queries[$i]);
                            }

                            $database->query('UPDATE `updates` SET `done` = :done WHERE id = :id', [
                                ':done' => $i + 3,
                                ':id' => $update['id'],
                            ]);
                        }

                        // Restituisce l'indice della prima e dell'ultima query eseguita, con la differenza relativa per l'avanzamento dell'aggiornamento
                        return [
                            $start,
                            $end,
                            $count,
                        ];
                    }
                }

                // Imposta l'aggiornamento nello stato di esecuzione dello script
                $database->query('UPDATE `updates` SET `done` = :done WHERE id = :id', [
                    ':done' => 0,
                    ':id' => $update['id'],
                ]);

                // Permessi di default delle viste
                if ($database->tableExists('zz_views')) {
                    $gruppi = $database->fetchArray('SELECT `id` FROM `zz_groups`');
                    $viste = $database->fetchArray('SELECT `id` FROM `zz_views` WHERE `id` NOT IN (SELECT `id_vista` FROM `zz_group_view`)');

                    $array = [];
                    foreach ($viste as $vista) {
                        foreach ($gruppi as $gruppo) {
                            $array[] = [
                                'id_gruppo' => $gruppo['id'],
                                'id_vista' => $vista['id'],
                            ];
                        }
                    }
                    if (!empty($array)) {
                        $database->insert('zz_group_view', $array);
                    }
                }

                // Normalizzazione di charset e collation
                self::normalizeDatabase($database->getDatabaseName());

                // Normalizzazione dei campi per l'API
                self::executeScript(base_dir().'/update/api.php');

                // Esecuzione dello script
                if (!empty($update['script']) && file_exists($file.'.php')) {
                    self::executeScript($file.'.php');
                }

                // Imposta l'aggiornamento come completato
                $database->query('UPDATE `updates` SET `done` = :done WHERE id = :id', [
                    ':done' => 1,
                    ':id' => $update['id'],
                ]);

                // Normalizzazione di charset e collation
                self::normalizeDatabase($database->getDatabaseName());

                return true;
            } catch (\Exception $e) {
                $logger = logger();
                $logger->addRecord(\Monolog\Logger::EMERGENCY, $e->getMessage());
            }

            return false;
        }

        return true;
    }

    /**
     * Restituisce un riepilogo sulla struttura delle tabelle del gestionale.
     *
     * @throws Exception
     *
     * @return array
     */
    public static function getDatabaseStructure()
    {
        // Tabelle registrate per il gestionale
        $tables = include base_dir().'/update/tables.php';

        $database = database();
        $database_name = $database->getDatabaseName();

        $info = [];
        foreach ($tables as $table) {
            if ($database->tableExists($table)) {
                // Individuazione delle colonne per la tabella
                $query = 'SHOW COLUMNS FROM `'.$table.'` IN `'.$database_name.'`';
                $columns_found = $database->fetchArray($query);

                // Organizzazione delle colonne per nome
                $columns = [];
                foreach ($columns_found as $column) {
                    $column = array_change_key_case($column);
                    $name = $column['field'];
                    unset($column['field']);

                    $columns[$name] = $column;
                }

                // Individuazione delle chiavi esterne della tabella
                $fk_query = 'SELECT
                    CONSTRAINT_NAME AS `name`,
                    COLUMN_NAME AS `column`,
                    REFERENCED_TABLE_NAME AS `referenced_table`,
                    REFERENCED_COLUMN_NAME AS `referenced_column`
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_NAME = '.prepare($table).'
                    AND TABLE_SCHEMA = '.prepare($database_name).'
                    AND REFERENCED_TABLE_SCHEMA = '.prepare($database_name);
                $fks_found = $database->fetchArray($fk_query);

                // Organizzazione delle chiavi esterne per nome
                $fks = [];
                foreach ($fks_found as $fk) {
                    $fk = array_change_key_case($fk);
                    $name = $fk['name'];
                    unset($fk['name']);

                    $fks[$name] = $fk;
                }

                $info[$table] = array_merge($columns, [
                    'foreign_keys' => $fks,
                ]);
            }
        }

        return $info;
    }

    /**
     * Controlla la presenza di aggiornamenti e prepara il database per la procedura.
     */
    protected static function prepareToUpdate()
    {
        $database = database();

        $database_ready = $database->isConnected() && $database->tableExists('updates');

        // Individuazione di tutti gli aggiornamenti presenti
        // Aggiornamenti del gestionale
        $core = self::getCoreUpdates();

        // Aggiornamenti supportati
        $modules = self::getCustomUpdates();

        $results = array_merge($core, $modules);
        $paths = array_column($results, 'path');

        // Individuazione di tutti gli aggiornamenti inseriti nel database
        $updates = ($database_ready) ? $database->fetchArray('SELECT * FROM `updates`') : [];
        $versions = [];
        foreach ($updates as $update) {
            $versions[] = self::findUpdatePath($update);
        }

        $reset = count(array_intersect($paths, $versions)) != count($results);

        // Memorizzazione degli aggiornamenti
        if ($reset && $database->isConnected()) {
            // Reimpostazione della tabella degli aggiornamenti
            $create = base_dir().'/update/create_updates.sql';
            if (file_exists($create)) {
                $database->query('DROP TABLE IF EXISTS `updates`');
                $database->multiQuery($create);
            }

            // Inserimento degli aggiornamenti individuati
            foreach ($results as $result) {
                // Individuazione di script e sql
                $sql = file_exists($result['path'].'.sql') ? 1 : 0;
                $script = file_exists($result['path'].'.php') ? 1 : 0;

                // Reimpostazione degli stati per gli aggiornamenti precedentemente presenti
                $pos = array_search($result['path'], $versions);
                $done = ($pos !== false) ? $updates[$pos]['done'] : null;

                $directory = explode('update/', $result['path'])[0];
                $database->insert('updates', [
                    'directory' => rtrim($directory, '/'),
                    'version' => $result['version'],
                    'sql' => $sql,
                    'script' => $script,
                    'done' => $done,
                ]);
            }

            // Normalizzazione di charset e collation
            self::normalizeDatabase($database->getDatabaseName());
        }
    }

    /**
     * Restituisce l'elenco degli aggiornamento del gestionale presenti nella cartella <b>update<b>.
     *
     * @return array
     */
    protected static function getCoreUpdates()
    {
        return self::getUpdates(base_dir().'/update');
    }

    /**
     * Restituisce l'elenco degli aggiornamento nel percorso indicato.
     *
     * @param string $directory
     *
     * @return array
     */
    protected static function getUpdates($directory)
    {
        $results = [];
        $previous = [];

        $files = glob($directory.'/*.{php,sql}', GLOB_BRACE);
        natsort($files);
        foreach ($files as $file) {
            $infos = pathinfo($file);
            $version = str_replace('_', '.', $infos['filename']);

            if (array_search($version, $previous) === false && self::isVersion($version)) {
                $path = str_replace(base_dir(), '', $infos['dirname'].'/'.$infos['filename']);
                $path = ltrim($path, '/');

                $results[] = [
                    'path' => $path,
                    'version' => $version,
                ];
                $previous[] = $version;
            }
        }

        $results;

        return $results;
    }

    /**
     * Restituisce l'elenco degli aggiornamento delle strutture supportate, presenti nella cartella <b>update<b>.
     *
     * @return array
     */
    protected static function getCustomUpdates()
    {
        $results = [];

        foreach (self::$directories as $dir) {
            $folders = glob(base_dir().'/'.$dir.'/*/update', GLOB_ONLYDIR);

            foreach ($folders as $folder) {
                $results = array_merge($results, self::getUpdates($folder));
            }
        }

        return $results;
    }

    protected static function findUpdatePath($update)
    {
        $version = str_replace('.', '_', $update['version']);

        $old_standard = str_contains($update['version'], '_');
        if (empty($update['directory']) && !$old_standard) {
            return 'update/'.$version;
        }

        if ($old_standard) {
            $module = implode('_', explode('_', $update['version'], -1));
            $version = explode('_', $update['version']);
            $version = end($version);

            $version = str_replace('.', '_', $version);

            return 'modules/'.$module.'/update/'.$version;
        }

        return  $update['directory'].'/update/'.$version;
    }

    /**
     * Ottiene i contenuti di un file.
     *
     * @param string $file
     *
     * @return string
     */
    protected static function getFile($file)
    {
        $file = (str_contains($file, base_dir().DIRECTORY_SEPARATOR)) ? $file : base_dir().DIRECTORY_SEPARATOR.$file;

        $result = '';

        $filepath = realpath($file);
        if (!empty($filepath)) {
            $result = file_get_contents($filepath);
            $result = str_replace(["\r\n", "\n"], '', $result);
        }

        return trim($result);
    }

    /**
     * Normalizza l'infrastruttura del database indicato, generalizzando charset e collation all'interno del database e delle tabelle ed effettuando una conversione delle tabelle all'engine InnoDB.
     * <b>Attenzione</b>: se l'engine InnoDB non è supportato, il server ignorerà la conversione dell'engine e le foreign key del gestionale non funzioneranno adeguatamente.
     *
     * @param string $database_name
     */
    protected static function normalizeDatabase($database_name)
    {
        set_time_limit(0);
        ignore_user_abort(true);

        $database = database();

        $database->getPDO()->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

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

        // Normalizzazione dell'engine MySQL
        $engines = $database->fetchArray('SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '.prepare($database_name)." AND ENGINE != 'InnoDB'");
        foreach ($engines as $engine) {
            $database->query('ALTER TABLE `'.$engine['TABLE_NAME'].'` ENGINE=InnoDB');
        }

        $database->getPDO()->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    /**
     * Esegue uno script PHP in un'ambiente il più possibile protetto.
     *
     * @param string $script
     */
    protected static function executeScript($script)
    {
        $dbo = $database = database();

        // Informazioni relative a MySQL
        $mysql_ver = $database->getMySQLVersion();

        include $script;
    }
}
