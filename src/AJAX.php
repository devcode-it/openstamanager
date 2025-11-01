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

use Util\Query;

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
     * @return bool
     */
    public static function isAjaxRequest()
    {
        return Whoops\Util\Misc::isAjaxRequest() && filter('ajax') !== null;
    }

    /**
     * Individua le opzioni di selezione a partire dalla risorsa richiesta.
     *
     * @param string $resource
     * @param array  $elements
     * @param int    $page
     * @param int    $length
     * @param array  $options
     *
     * @return array
     */
    public static function select($resource, $elements = [], $search = null, $page = 0, $length = 100, $options = [])
    {
        if (!isset($elements)) {
            $elements = [];
        }
        $elements = (!is_array($elements)) ? explode(',', $elements) : $elements;

        $files = self::find('ajax/select.php', false);

        // File di gestione predefinita
        array_unshift($files, base_dir().'/ajax_select.php');

        foreach ($files as $file) {
            $results = self::getSelectResults($file, $resource, $elements, [
                'offset' => $page * $length,
                'length' => $length,
            ], $search, $options);

            if (isset($results)) {
                break;
            }
        }

        $results ??= [];

        $link = $options['link'] ?? ($results['link'] ?? null);
        unset($results['link']);

        $total = array_key_exists('recordsFiltered', $results) ? $results['recordsFiltered'] : count($results);
        $list = array_key_exists('results', $results) ? $results['results'] : $results;

        // Applicazione della trasformazione dei link se specificata nelle opzioni
        if (!empty($link) && !empty($list)) {
            $list = self::applyLinkTransformation($list, $link);
        }

        return [
            'results' => $list ?: [],
            'recordsFiltered' => $total,
        ];
    }

    /**
     * Completa la query SQL a partire da parametri definiti e ne restituisce i risultati.
     *
     * @param string $query
     * @param array  $where
     * @param array  $filter
     * @param array  $search
     * @param array  $limit
     * @param array  $custom
     *
     * @return array
     */
    public static function selectResults($query, $where, $filter = [], $search = [], $limit = [], $custom = [])
    {
        if (string_contains($query, '|filter|')) {
            $query = str_replace('|filter|', !empty($filter) ? 'WHERE '.implode(' OR ', $filter) : '', $query);
        } elseif (!empty($filter)) {
            $where[] = '('.implode(' OR ', $filter).')';
        }

        if (!empty($search)) {
            $where[] = '('.implode(' OR ', $search).')';
        }

        $query = str_replace('|where|', !empty($where) ? 'WHERE '.implode(' AND ', $where) : '', $query);
        $query .= ' LIMIT '.$limit['offset'].', '.$limit['length'];

        $data = Query::executeAndCount($query);
        $rows = $data['results'];

        $results = [];
        foreach ($rows as $row) {
            $result = $row;
            foreach ($custom as $key => $value) {
                $result[$key] = $row[$value];
            }

            $results[] = $result;
        }

        return [
            'results' => $results,
            'recordsFiltered' => $data['count'],
            'link' => $custom['link'] ?? null,
        ];
    }

    /**
     * Effettua la ricerca di un termine all'intero delle risorse disponibili.
     *
     * @param string $term
     *
     * @return array
     */
    public static function search($term)
    {
        if (strlen($term) < 2) {
            return [];
        }

        $files = self::find('ajax/search.php');

        // File di gestione predefinita
        array_unshift($files, base_dir().'/ajax_search.php');

        $results = [];
        foreach ($files as $file) {
            try {
                $module_results = self::getSearchResults($file, $term);

                if (is_array($module_results)) {
                    $results = array_merge($results, $module_results);
                }
            } catch (Exception $e) {
                // Continua con gli altri moduli
            }
        }

        return $results;
    }

    /**
     * Completa il codice HTML per la risorsa richiesta.
     *
     * @param string $resource
     *
     * @return string
     */
    public static function complete($resource)
    {
        $files = self::find('ajax/complete.php');

        // File di gestione predefinita
        array_unshift($files, base_dir().'/ajax_complete.php');

        foreach ($files as $file) {
            $result = self::getCompleteResults($file, $resource);
            if (!empty($result)) {
                break;
            }
        }

        return $result;
    }

    /**
     * Individua i file per cui l'utente possiede i permessi di accesso.
     *
     * @param string $file
     * @param bool   $permissions
     *
     * @return array
     */
    public static function find($file, $permissions = true)
    {
        $dirname = substr($file, 0, strrpos($file, '/') + 1);

        // Individuazione delle cartelle accessibili
        if (!empty($permissions)) {
            $modules = Modules::getAvailableModules();
        } else {
            $modules = Models\Module::withoutGlobalScope('enabled')->get();
        }

        $modules = $modules->toArray();

        $dirs = array_unique(array_column($modules, 'directory'));
        $pieces = array_chunk($dirs, 5);

        // Individuazione dei file esistenti
        $list = [];
        foreach ($pieces as $piece) {
            // File nativi
            $files = glob(base_dir().'/modules/{'.implode(',', $piece).'}/'.$file, GLOB_BRACE);

            // File personalizzati
            $custom_files = glob(base_dir().'/modules/{'.implode(',', $piece).'}/custom/'.$file, GLOB_BRACE);

            // Pulizia dei file nativi che sono stati personalizzati
            foreach ($custom_files as $key => $value) {
                $index = array_search(str_replace('custom/'.$dirname, $dirname, $value), $files);
                if ($index !== false) {
                    unset($files[$index]);
                }
            }

            $list = array_merge($list, $files, $custom_files);
        }

        asort($list);

        return $list;
    }

    /**
     * Ottiene i risultati del select all'interno di un file specifico (modulo).
     *
     * @param string $file
     * @param string $resource
     * @param array  $elements
     * @param array  $limit
     * @param array  $options
     *
     * @return array|null
     */
    protected static function getSelectResults($file, $resource, $elements = [], $limit = [], $search = null, $options = [])
    {
        $where = [];
        $filter = [];
        $search_fields = [];

        $custom = [
            'id' => 'id',
            'text' => 'descrizione',
        ];

        // Database
        $dbo = $database = database();

        // Opzioni di selezione
        $superselect = $options;

        require $file;

        if (!isset($results) && !empty($query)) {
            $results = self::selectResults($query, $where, $filter, $search_fields, $limit, $custom);
        }

        return $results ?? null;
    }

    /**
     * Applica la trasformazione dei link agli elementi del select.
     *
     * @param array  $list
     * @param string $link
     *
     * @return array
     */
    protected static function applyLinkTransformation($list, $link)
    {
        foreach ($list as &$element) {
            // Gestione degli elementi con children (optgroup)
            if (isset($element['children']) && is_array($element['children'])) {
                $element['children'] = self::applyLinkTransformation($element['children'], $link);
            } else {
                // Applicazione della trasformazione del link all'elemento singolo
                $element = self::transformElementLink($element, $link);
            }
        }

        return $list;
    }

    /**
     * Trasforma il link di un singolo elemento applicando la stessa logica del SelectHandler.
     *
     * @param array  $element
     * @param string $link
     *
     * @return array
     */
    protected static function transformElementLink($element, $link)
    {
        if ($link == 'stampa') {
            $element['title'] = ' ';
            $element['text'] = '<a href="'.Prints::getHref($element['id'], get('id_record')).'" target="_blank">'.$element['text'].' <i class="fa fa-external-link"></i></a>';
        } elseif ($link == 'allegato') {
            $element['title'] = ' ';
            $element['text'] = '<a href="'.base_path_osm().'/view.php?file_id='.$element['id'].'" target="_blank">'.$element['text'].' <i class="fa fa-external-link"></i></a>';
        } elseif (string_contains($link, 'module:')) {
            $element['title'] = ' ';
            $element['text'] = Modules::link(str_replace('module:', '', $link), $element['id'], $element['text'], false, ' target="_blank"');
        } elseif (string_contains($link, 'plugin:')) {
            $element['title'] = ' ';
            $element['text'] = Plugins::link(str_replace('plugin:', '', $link), $element['id'], $element['text'], false, ' target="_blank"');
        }

        return $element;
    }

    /**
     * Ottiene i risultati della ricerca all'interno di un file specifico (modulo).
     *
     * @param string $file
     * @param string $term
     *
     * @return array
     */
    protected static function getSearchResults($file, $term)
    {
        // Verifica che il file esista
        if (!file_exists($file)) {
            return [];
        }

        // Database
        $dbo = $database = database();

        // Ricerca anagrafiche per ragione sociale per potere mostrare gli interventi, fatture,
        // ordini, ecc della persona ricercata
        $idanagrafiche = ['-1'];
        $ragioni_sociali = ['-1'];

        try {
            $rs = $dbo->fetchArray('SELECT idanagrafica, ragione_sociale FROM an_anagrafiche WHERE ragione_sociale LIKE '.prepare('%'.$term.'%'));

            for ($a = 0; $a < sizeof($rs); ++$a) {
                $idanagrafiche[] = $rs[$a]['idanagrafica'];
                $ragioni_sociali[$rs[$a]['idanagrafica']] = $rs[$a]['ragione_sociale'];
            }
        } catch (Exception $e) {
            // Continua senza anagrafiche
        }

        $results = [];

        try {
            require $file;
        } catch (Exception $e) {
            return [];
        }

        $results = (array) $results;
        foreach ($results as $key => $value) {
            if (is_array($value)) {
                $results[$key]['value'] = $key;
            }
        }

        return $results;
    }

    /**
     * Ottiene i risultati della richiesta di completamento all'interno di un file specifico (modulo).
     *
     * @param string $file
     * @param string $resource
     *
     * @return string
     */
    protected static function getCompleteResults($file, $resource)
    {
        // Database
        $dbo = $database = database();

        ob_start();
        require $file;
        $result = ob_get_clean();

        return $result;
    }
}
