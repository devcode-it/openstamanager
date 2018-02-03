<?php

/**
 * Classe per la gestione delle funzioni AJAX richiamabili del progetto.
 *
 * @since 2.4
 */
class AJAX
{
    /**
     * Controlla se è in corso una richiesta AJAX generata dal progetto.
     *
     * @since 2.4
     *
     * @return bool
     */
    public static function isAjaxRequest()
    {
        return \Whoops\Util\Misc::isAjaxRequest() && filter('ajax') !== null;
    }

    public static function select($resource, $elements = [], $search = null)
    {
        if (!isset($elements)) {
            $elements = [];
        }
        $elements = (!is_array($elements)) ? explode(',', $elements) : $elements;

        $modules = Modules::getAvailableModules();

        // Individuazione dei select esistenti
        $dirs = array_column($modules, 'directory');
        $pieces = array_chunk($dirs, 5);

        $customs = [];
        foreach ($pieces as $piece) {
            $files = glob(DOCROOT.'/modules/{'.implode(',', $piece).'}/ajax/select.php', GLOB_BRACE);
            $customs = array_merge($customs, $files);
        }

        // File di gestione predefinita
        array_unshift($customs, DOCROOT.'/ajax_select.php');

        foreach ($customs as $custom) {
            $temp = str_replace('/ajax/', '/custom/ajax/', $custom);
            $file = file_exists($temp) ? $temp : $custom;

            $results = self::getSelectResults($file, $resource, $elements, $search);
            if (isset($results)) {
                break;
            }
        }

        return $results;
    }

    public static function completeResults($query, $where, $filter = [], $search = [], $custom = [])
    {
        if (str_contains($query, '|filter|')) {
            $query = str_replace('|filter|', !empty($filter) ? 'WHERE '.implode(' OR ', $filter) : '', $query);
        } elseif (!empty($filter)) {
            $where[] = '('.implode(' OR ', $filter).')';
        }

        if (!empty($search)) {
            $where[] = '('.implode(' OR ', $search).')';
        }

        $query = str_replace('|where|', !empty($where) ? 'WHERE '.implode(' AND ', $where) : '', $query);

        $database = Database::getConnection();
        $rs = $database->fetchArray($query);

        $results = [];
        foreach ($rs as $r) {
            $result = [];
            foreach ($custom as $key => $value) {
                $result[$key] = $r[$value];
            }

            $results[] = $result;
        }

        return $results;
    }

    private static function getSelectResults($file, $resource, $elements = [], $search = null)
    {
        $superselect = self::getSelectInfo();

        $where = [];
        $filter = [];
        $search_fields = [];

        $custom = [
            'id' => 'id',
            'text' => 'descrizione',
        ];

        // Database
        $database = Database::getConnection();
        $dbo = $database;

        require $file;

        if (!isset($results) && !empty($query)) {
            $results = self::completeResults($query, $where, $filter, $search_fields, $custom);
        }

        return $results;
    }

    private static function getSelectInfo()
    {
        return !empty($_SESSION['superselect']) ? $_SESSION['superselect'] : [];
    }

    public static function search($term)
    {
        if (strlen($term) < 2) {
            return;
        }

        $modules = Modules::getAvailableModules();

        // Individuazione dei select esistenti
        $dirs = array_column($modules, 'directory');
        $pieces = array_chunk($dirs, 5);

        $customs = [];
        foreach ($pieces as $piece) {
            $files = glob(DOCROOT.'/modules/{'.implode(',', $piece).'}/ajax/search.php', GLOB_BRACE);
            $customs = array_merge($customs, $files);
        }

        // File di gestione predefinita
        array_unshift($customs, DOCROOT.'/ajax_search.php');

        $results = [];
        foreach ($customs as $custom) {
            $temp = str_replace('/ajax/', '/custom/ajax/', $custom);
            $file = file_exists($temp) ? $temp : $custom;

            $module_results = self::getSearchResults($file, $term);

            $results = array_merge($results, $module_results);
        }

        return $results;
    }

    private static function getSearchResults($file, $term)
    {
        // Database
        $database = Database::getConnection();
        $dbo = $database;

        // Ricerca anagrafiche per ragione sociale per potere mostrare gli interventi, fatture,
        // ordini, ecc della persona ricercata
        $idanagrafiche = ['-1'];
        $ragioni_sociali = ['-1'];
        $rs = $dbo->fetchArray('SELECT idanagrafica, ragione_sociale FROM an_anagrafiche WHERE ragione_sociale LIKE "%'.$term.'%"');

        for ($a = 0; $a < sizeof($rs); ++$a) {
            $idanagrafiche[] = $rs[$a]['idanagrafica'];
            $ragioni_sociali[$rs[$a]['idanagrafica']] = $rs[$a]['ragione_sociale'];
        }

        $results = [];

        require $file;

        $results = (array) $results;
        foreach ($results as $key => $value) {
            $results[$key]['value'] = $key;
        }

        return $results;
    }

    public static function complete($resource)
    {
        $modules = Modules::getAvailableModules();

        // Individuazione dei select esistenti
        $dirs = array_column($modules, 'directory');
        $pieces = array_chunk($dirs, 5);

        $customs = [];
        foreach ($pieces as $piece) {
            $files = glob(DOCROOT.'/modules/{'.implode(',', $piece).'}/ajax/complete.php', GLOB_BRACE);
            $customs = array_merge($customs, $files);
        }

        // File di gestione predefinita
        array_unshift($customs, DOCROOT.'/ajax_complete.php');

        foreach ($customs as $custom) {
            $temp = str_replace('/ajax/', '/custom/ajax/', $custom);
            $file = file_exists($temp) ? $temp : $custom;

            $result = self::getCompleteResults($file, $resource);
            if (!empty($result)) {
                break;
            }
        }

        return $result;
    }

    private static function getCompleteResults($file, $resource)
    {
        // Database
        $database = Database::getConnection();
        $dbo = $database;

        ob_start();
        require $file;
        $result = ob_get_clean();

        return $result;
    }
}
