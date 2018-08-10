<?php

/**
 * Classe dedicata alla gestione delle procedure di aggiornamento del database del progetto.
 *
 * @since 2.3
 */
class Update
{
    /** @var array Elenco degli aggiornamenti da completare */
    protected static $updates;

    /**
     * Controlla la presenza di aggiornamenti e prepara il database per la procedura.
     */
    protected static function prepareToUpdate()
    {
        $database = Database::getConnection();

        $database_ready = $database->isConnected() && $database->tableExists('updates');

        // Individuazione di tutti gli aggiornamenti fisicamente presenti
        // Aggiornamenti del gestionale
        $core = self::getCoreUpdates();
        // Aggiornamenti dei moduli
        $modules = self::getModulesUpdates();

        $results = array_merge($core, $modules);

        // Individuazione di tutti gli aggiornamenti inseriti
        $updates = ($database_ready) ? $database->fetchArray('SELECT * FROM `updates`') : [];
        $versions = array_column($updates, 'version');

        $reset = count(array_intersect($results, $versions)) != count($results);

        // Memorizzazione degli aggiornamenti
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

    /**
     * Restituisce l'elenco degli aggiornamento del gestionale presenti nella cartella <b>update<b>.
     *
     * @return array
     */
    protected static function getCoreUpdates()
    {
        $results = [];

        // Aggiornamenti del gestionale
        $core = glob(DOCROOT.'/update/*.{php,sql}', GLOB_BRACE);
        foreach ($core as $value) {
            $infos = pathinfo($value);
            $value = str_replace('_', '.', $infos['filename']);

            if (self::isVersion($value)) {
                $results[] = $value;
            }
        }

        $results = array_unique($results);
        asort($results);

        return $results;
    }

    /**
     * Restituisce l'elenco degli aggiornamento dei moduli, presenti nella cartella <b>update<b> dei singoli moduli.
     *
     * @return array
     */
    protected static function getModulesUpdates()
    {
        $results = [];

        // Aggiornamenti dei moduli
        $modules = glob(DOCROOT.'/modules/*/update/*.{php,sql}', GLOB_BRACE);
        foreach ($modules as $value) {
            $infos = pathinfo($value);

            $temp = explode('/', dirname($infos['dirname']));
            $module = end($temp);

            $value = str_replace('_', '.', $infos['filename']);

            if (self::isVersion($value)) {
                $results[] = $module.'_'.$value;
            }
        }

        $results = array_unique($results);
        asort($results);

        return $results;
    }

    /**
     * Restituisce l'elenco degli aggiornamento incompleti o non ancora effettuati.
     *
     * @return array
     */
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

    /**
     * Restituisce il primo aggiornamento che deve essere completato.
     *
     * @return array
     */
    public static function getUpdate()
    {
        $todos = self::getTodos();

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
        $todos = self::getTodos();

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
        $todos = array_column(self::getTodos(), 'done');
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
        $database = Database::getConnection();

        $results = $database->fetchArray("SELECT version FROM `updates` WHERE version NOT LIKE '%\_%' ORDER BY version DESC LIMIT 1");

        return $results[0]['version'];
    }

    /**
     * Restituisce la versione corrente del software gestita dal file system (file VERSION nella root).
     *
     * @return string
     */
    public static function getVersion()
    {
        $result = self::getFile('VERSION');

        if (empty($result)) {
            $database = Database::getConnection();

            if ($database->isInstalled()) {
                $result = self::getDatabaseVersion();
            } else {
                $updatelist = self::getCoreUpdates();
                $result = end($updatelist);
            }
        }

        return $result;
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
     * Ottiene i contenuti di un file.
     *
     * @param string $file
     *
     * @return string
     */
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

    /**
     * Effettua una pulizia del database a seguito del completamento dell'aggiornamento.
     *
     * @return bool
     */
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
            $update = self::getUpdate();

            $file = DOCROOT.$update['directory'].$update['filename'];

            $database = Database::getConnection();

            try {
                // Esecuzione delle query
                if (!empty($update['sql']) && (!empty($update['done']) || is_null($update['done'])) && file_exists($file.'.sql')) {
                    $queries = readSQLFile($file.'.sql', ';');
                    $count = count($queries);

                    $start = empty($update['done']) ? 0 : $update['done'] - 2;
                    $end = ($start + $rate + 1) > $count ? $count : $start + $rate + 1;

                    if ($start < $end) {
                        for ($i = $start; $i < $end; ++$i) {
                            $database->query($queries[$i], [], tr('Aggiornamento fallito').': '.$queries[$i]);

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

                // Normalizzazione dei campi per l'API
                self::executeScript(DOCROOT.'/update/api.php');

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

        // Normalizzazione dell'engine MySQL
        $engines = $database->fetchArray('SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '.prepare($database_name)." AND ENGINE != 'InnoDB'");
        foreach ($engines as $engine) {
            $database->query('ALTER TABLE `'.$engine['TABLE_NAME'].'` ENGINE=InnoDB');
        }
    }

    /**
     * Esegue uno script PHP in un'ambiente il più possibile protetto.
     *
     * @param string $script
     */
    protected static function executeScript($script)
    {
        $dbo = $database = Database::getConnection();

        // Informazioni relative a MySQL
        $mysql_ver = $database->getMySQLVersion();

        include $script;
    }
}
