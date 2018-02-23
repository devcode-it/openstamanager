<?php

/**
 * Classe per la gestione delle utenze.
 *
 * @since 2.4
 */
class Import
{
    /** @var int Identificativo del modulo corrente */
    protected static $imports;

    /**
     * Restituisce tutte le informazioni di tutti i moduli installati.
     *
     * @return array
     */
    public static function getImports()
    {
        if (empty(self::$imports)) {
            $modules = Modules::getModules();

            $database = Database::getConnection();

            $results = [];
            foreach ($modules as $module) {
                $file = DOCROOT.'/modules/'.$module['directory'].'|custom|/import.php';

                $original_file = str_replace('|custom|', '', $file);
                $custom_file = str_replace('|custom|', '/custom', $file);

                if (file_exists($custom_file) || file_exists($original_file)) {
                    $files = $database->fetchArray('SELECT * FROM zz_files WHERE id_module='.prepare(Modules::get('Import')['id']).' AND id_record='.prepare($module['id']).' ORDER BY id DESC');

                    $results[$module['id']] = array_merge($module, [
                        'import' => file_exists($custom_file) ? $custom_file : $original_file,
                        'files' => $files,
                    ]);
                }
            }

            self::$imports = $results;
        }

        return self::$imports;
    }

    /**
     * Restituisce le informazioni relative a un singolo modulo specificato.
     *
     * @param string|int $module
     *
     * @return array
     */
    public static function get($module)
    {
        if (!is_numeric($module) && !empty(self::getModules()[$module])) {
            $module = self::getModules()[$module];
        }

        return self::getImports()[$module];
    }

    /**
     * Restituisce l'elenco dei campi previsti dal modulo.
     *
     * @param string|int $module
     *
     * @return array
     */
    public static function getFields($module)
    {
        $import = self::get($module);

        ob_start();
        $fields = require $import['import'];
        ob_end_clean();

        return $fields;
    }

    /**
     * Restituisce i contenuti del file CSV indicato.
     *
     * @param string|int $module
     * @param int        $file_id
     * @param array      $options
     *
     * @return array
     */
    public static function getFile($module, $file_id, $options = [])
    {
        $import = self::get($module);

        $ids = array_column($import['files'], 'id');
        $find = array_search($file_id, $ids);

        if ($find == -1) {
            return [];
        }

        $file = DOCROOT.'/files/'.Modules::get('Import')['directory'].'/'.$import['files'][$find]['filename'];

        // Gestione del file CSV
        $csv = League\Csv\Reader::createFromPath($file, 'r');
        $csv->setDelimiter(';');

        // Ignora la prima riga
        $offset = 0;
        if (!empty($options['headers'])) {
            ++$offset;
        }
        $rows = $csv->setOffset($offset);

        // Limite di righe
        if (!empty($options['limit'])) {
            $rows = $rows->setLimit($options['limit']);
        }

        // Lettura
        $rows = $rows->fetchAll();

        return $rows;
    }
}
