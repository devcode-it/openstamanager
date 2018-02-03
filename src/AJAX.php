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

    public static function getSelectValues($resource, $elements = [], $search = null)
    {
        if (!isset($elements)) {
            $elements = [];
        }
        $elements = (!is_array($elements)) ? explode(',', $elements) : $elements;

        // Individuazione dei select esistenti
        $customs = glob(DOCROOT.'/modules/*/ajax/select.php');
        array_unshift($customs, DOCROOT.'/ajax_select.php');

        foreach ($customs as $custom) {
            $temp = str_replace('/ajax/', '/custom/ajax/', $custom);
            $file = file_exists($temp) ? $file : $custom;

            $results = self::getFileValues($file, $resource, $elements, $search);
            if (isset($results)) {
                break;
            }
        }

        return $results;
    }

    private static function completeResults($query, $where, $filter = [], $search = [], $custom = [])
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

    private static function getFileValues($file, $resource, $elements = [], $search = null)
    {
        $superselect = self::getSelectInfo();

        $where = [];
        $filter = [];
        $search_fields = [];

        $custom = [
            'id' => 'id',
            'text' => 'descrizione',
        ];

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
}
