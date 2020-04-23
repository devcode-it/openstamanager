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

            $database = database();

            $results = [];
            foreach ($modules as $module) {
                $file = DOCROOT.'/modules/'.$module['directory'].'|custom|/import.php';

                $original_file = str_replace('|custom|', '', $file);
                $custom_file = str_replace('|custom|', '/custom', $file);

                if (file_exists($custom_file) || file_exists($original_file)) {
                    $files = Uploads::get([
                        'id_module' => Modules::get('Import')['id'],
                        'id_record' => $module['id'],
                    ]);

                    $results[$module['id']] = array_merge($module->toArray(), [
                        'import' => file_exists($custom_file) ? $custom_file : $original_file,
                        'files' => array_reverse($files),
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
        $module = Modules::get($module)['id'];

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

        // Impostazione automatica dei nomi "ufficiali" dei campi
        foreach ($fields as $key => $value) {
            if (!isset($value['names'])) {
                $names = [
                    $value['field'],
                    $value['label'],
                ];
            } else {
                $names = $value['names'];
            }

            // Impostazione dei nomi in minuscolo
            foreach ($names as $k => $v) {
                $names[$k] = str_to_lower($v);
            }

            $fields[$key]['names'] = $names;
        }

        return $fields;
    }

    /**
     * Restituisce i contenuti del file CSV indicato.
     *
     * @param string|int $module
     * @param int        $file_id
     *
     * @return array
     */
    public static function getCSV($module, $file_id)
    {
        $import = self::get($module);

        $ids = array_column($import['files'], 'id');
        $find = array_search($file_id, $ids);

        if ($find == -1) {
            return [];
        }

        $file = Modules::get('Import')->upload_directory.'/'.$import['files'][$find]['filename'];

        // Impostazione automatica per i caratteri di fine riga
        if (!ini_get('auto_detect_line_endings')) {
            ini_set('auto_detect_line_endings', '1');
        }

        // Gestione del file CSV
        $csv = League\Csv\Reader::createFromPath($file, 'r');
        $csv->setDelimiter(';');

        return $csv;
    }
}
