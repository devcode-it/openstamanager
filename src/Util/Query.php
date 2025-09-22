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

namespace Util;

/**
 * Classe per la gestione delle interazione di base per le query dinamiche.
 *
 * @since 2.4.7
 */
class Query
{
    protected static $segments = true;

    /** @var array Cache per le query processate */
    protected static $query_cache = [];

    /** @var int Limite massimo per la cache */
    protected static $cache_limit = 100;

    /**
     * Imposta l'utilizzo o meno dei segmenti per le query.
     *
     * @param bool $segments
     */
    public static function setSegments($segments)
    {
        self::$segments = $segments;
    }

    /**
     * Restituisce un'insieme di array comprendenti le informazioni per la costruzione della query del modulo indicato.
     *
     * @param array $element
     *
     * @throws \Exception
     *
     * @return array
     */
    public static function readQuery($element)
    {
        if (string_contains($element['option'], '|select|')) {
            $result = self::readNewQuery($element);
        } else {
            $result = self::readOldQuery($element);
        }

        return $result;
    }

    /**
     * Sostituisce i valori previsti all'interno delle query di moduli/plugin.
     *
     * @param string $query
     *
     * @return string
     */
    public static function replacePlaceholder($query)
    {
        $id_parent = filter('id_parent');

        $id_module = \Modules::getCurrent()['id'];
        $segment = !empty(self::$segments) ? ($_SESSION['module_'.$id_module]['id_segment'] ?? null) : null;

        // Ottimizzazione: evita query se segment è null
        $is_sezionale = false;
        if (!empty($segment)) {
            $segment_data = database()->fetchOne('SELECT `is_sezionale` FROM `zz_segments` WHERE `id` = '.prepare($segment));
            $is_sezionale = !empty($segment_data) ? $segment_data['is_sezionale'] : false;
        }

        $lang = \Models\Locale::getDefault()->id;
        $user = \Auth::user();

        // Sostituzione periodi temporali
        $date_query = $date_filter = null;
        if (preg_match('|date_period\((.+?)\)|', (string) $query, $matches)) {
            $dates = array_map('trim', explode(',', $matches[1]));
            $date_filter = $matches[0];

            $filters = [];
            if (!empty($dates) && $dates[0] !== 'custom') {
                foreach ($dates as $date) {
                    if (!empty($date)) {
                        $filters[] = $date." BETWEEN '|period_start|' AND '|period_end|'";
                    }
                }
            } else {
                // Per filtri custom, salta il primo elemento e processa il resto
                for ($k = 1; $k < count($dates); ++$k) {
                    if (!empty($dates[$k])) {
                        $filters[] = trim($dates[$k]);
                    }
                }
            }
            $date_query = !empty($filters) && !empty(self::$segments) ? ' AND ('.implode(' OR ', $filters).')' : '';
        }

        // Sostituzione segmenti
        $segment_name = 'id_segment';
        $segment_filter = 'segment';
        if (preg_match('|segment\((.+?)\)|', (string) $query, $matches)) {
            $segment_name = !empty($matches[1]) ? trim($matches[1]) : 'id_segment';
            $segment_filter = $matches[0];
        }

        // Validazione sicurezza per period_start e period_end
        $period_start = $_SESSION['period_start'] ?? date('Y-01-01');
        $period_end = $_SESSION['period_end'] ?? date('Y-12-31');

        // Validazione formato date
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $period_start)) {
            $period_start = date('Y-01-01');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $period_end)) {
            $period_end = date('Y-12-31');
        }

        // Elenco delle sostituzioni
        $replace = [
            // Identificatori
            '|id_anagrafica|' => prepare($user['idanagrafica'] ?? 0),
            '|id_utente|' => prepare($user['id'] ?? 0),
            '|id_parent|' => prepare($id_parent),

            // Filtro temporale
            '|'.$date_filter.'|' => $date_query,

            // Date
            '|period_start|' => $period_start,
            '|period_end|' => $period_end.' 23:59:59',

            // Segmenti
            '|'.$segment_filter.'|' => (!empty($segment) && $is_sezionale) ? ' AND '.$segment_name.' = '.prepare($segment) : '',

            // Filtro dinamico per il modulo Giacenze sedi - con validazione
            '|giacenze_sedi_idsede|' => prepare($_SESSION['giacenze_sedi']['idsede'] ?? null),

            // Filtro per lingua
            '|lang|' => '`id_lang` = '.prepare($lang),
        ];

        // Sostituzione dei formati
        try {
            $patterns = formatter()->getSQLPatterns();
            foreach ($patterns as $key => $value) {
                $replace['|'.$key.'_format|'] = "'".addslashes((string) $value)."'";
            }
        } catch (\Exception) {
        }

        // Sostituzione effettiva
        $query = replace($query, $replace);

        return $query;
    }

    /**
     * Genera la query prevista dalla struttura indicata.
     *
     * @param array $search
     * @param array $order
     * @param array $limit
     *
     * @throws \Exception
     *
     * @return mixed|string
     */
    public static function getQuery($structure, $search = [], $order = [], $limit = [], $total = [])
    {
        if (empty($total)) {
            $total = self::readQuery($structure);
        }

        // Lettura parametri modulo
        $query = $total['query'];

        if (empty($query) || $query == 'menu' || $query == 'custom') {
            return '';
        }

        // Filtri di ricerca
        $search_filters = [];
        foreach ($search as $field => $original_value) {
            $pos = array_search($field, $total['fields']);
            $value = is_array($original_value) ? $original_value : trim((string) $original_value);

            if (empty($value) && $value !== '0' && $value !== 0) {
                continue;
            }

            if (isset($value) && $pos !== false) {
                $search_query = $total['search_inside'][$pos];

                // Campo con ricerca personalizzata
                if (string_contains($search_query, '|search|')) {
                    $pieces = array_filter(array_map('trim', explode(',', $value)));
                    foreach ($pieces as $piece) {
                        if (!empty($piece)) {
                            $search_filters[] = str_replace('|search|', prepare('%'.$piece.'%'), $search_query);
                        }
                    }
                }
                // Campi tradizionali: ricerca tramite like
                else {
                    $search_filters[] = self::buildSearchFilter($field, $value, $search_query);
                }
            }
            // Campo id: ricerca tramite comparazione
            elseif ($field === 'id') {
                $search_filters[] = self::buildIdFilter($field, $original_value);
            }
        }

        // Applicazione filtri di ricerca
        if (!empty($search_filters)) {
            $filters_string = implode(' AND ', array_filter($search_filters));
            if (!empty($filters_string)) {
                $query = str_replace('2=2', '2=2 AND ('.$filters_string.') ', $query);
            }
        }

        // Ordinamento dei risultati
        if (isset($order['dir'], $order['column'])) {
            $column_index = $order['column'];
            $direction = strtoupper($order['dir']);

            // Validazione direzione ordinamento
            if (!in_array($direction, ['ASC', 'DESC'])) {
                $direction = 'ASC';
            }

            if (isset($total['order_by'][$column_index])) {
                // Rimozione ORDER BY esistente in modo più efficiente
                $query = preg_replace('/\s+ORDER\s+BY\s+.+$/i', '', $query);
                $order_clause = $total['order_by'][$column_index];

                // Sanitizzazione della clausola ORDER BY
                if (!empty($order_clause)) {
                    $query .= ' ORDER BY '.$order_clause.' '.$direction;
                }
            }
        }

        // Paginazione
        if (!empty($limit) && isset($limit['length'], $limit['start'])) {
            $length = intval($limit['length']);
            $start = intval($limit['start']);

            if ($length > 0 && $start >= 0) {
                // Limite massimo per sicurezza
                $max_limit = 10000;
                if ($length > $max_limit) {
                    $length = $max_limit;
                }

                $query .= ' LIMIT '.$start.', '.$length;
            }
        }

        return $query;
    }

    public static function executeAndCount($query)
    {
        $database = database();

        try {
            // Esecuzione della query
            $query = self::str_replace_once('SELECT', 'SELECT SQL_CALC_FOUND_ROWS', $query);
            $results = $database->fetchArray($query);

            // Conteggio dei record filtrati
            $count = $database->fetchOne('SELECT FOUND_ROWS() AS count');

            return [
                'results' => $results,
                'count' => intval($count['count'] ?? 0),
            ];
        } catch (\Exception $e) {
            // Log dell'errore e fallback
            error_log('Query execution error: '.$e->getMessage());

            return [
                'results' => [],
                'count' => 0,
            ];
        }
    }

    /**
     * Restituisce le somme richieste dalla query prevista dalla struttura.
     *
     * @param array $search
     *
     * @throws \Exception
     *
     * @return array
     */
    public static function getSums($structure, $search = [])
    {
        $total = self::readQuery($structure);

        // Calcolo di eventuali somme
        if (empty($total['summable'])) {
            return [];
        }

        $result_query = self::getQuery($structure, $search);

        // Filtri derivanti dai permessi (eventuali)
        if (empty($structure->originalModule)) {
            $result_query = \Modules::replaceAdditionals($structure->id, $result_query);
        }

        $query = self::str_replace_once('SELECT', 'SELECT '.implode(', ', $total['summable']).' FROM(SELECT ', $result_query).') AS `z`';
        $sums = database()->fetchOne($query);

        $results = [];
        if (!empty($sums)) {
            foreach ($sums as $key => $sum) {
                if (string_contains($key, 'sum_')) {
                    $results[str_replace('sum_', '', $key)] = \Translator::numberToLocale($sum);
                }
            }
        }

        return $results;
    }

    /**
     * Restituisce la media dei valori dalla query prevista dalla struttura.
     *
     * @param array $search
     *
     * @throws \Exception
     *
     * @return array
     */
    public static function getAverages($structure, $search = [])
    {
        $total = self::readQuery($structure);

        // Calcolo di eventuali somme
        if (empty($total['avg'])) {
            return [];
        }

        $result_query = self::getQuery($structure, $search);

        // Filtri derivanti dai permessi (eventuali)
        if (empty($structure->originalModule)) {
            $result_query = \Modules::replaceAdditionals($structure->id, $result_query);
        }

        $query = self::str_replace_once('SELECT', 'SELECT '.implode(', ', $total['avg']).' FROM(SELECT ', $result_query).') AS `z`';
        $avgs = database()->fetchOne($query);

        $results = [];
        if (!empty($avgs)) {
            foreach ($avgs as $key => $avg) {
                if (string_contains($key, 'avg_')) {
                    $results[str_replace('avg_', '', $key)] = \Translator::numberToLocale($avg);
                }
            }
        }

        return $results;
    }

    /**
     * Pulisce la cache delle query per liberare memoria.
     */
    public static function clearCache()
    {
        self::$query_cache = [];
    }

    /**
     * Costruisce un filtro di ricerca ottimizzato per un campo specifico.
     *
     * @param string $field
     * @param string $search_query
     *
     * @return string|null
     */
    protected static function buildSearchFilter($field, $value, $search_query)
    {
        // Ricerca nei titoli icon_title_* per le icone icon_*
        if (preg_match('/^icon_(.+?)$/', $field, $m)) {
            $search_query = '`icon_title_'.$m[1].'`';
        }
        // Ricerca nei titoli color_title_* per i colori color_*
        elseif (preg_match('/^color_(.+?)$/', $field, $m)) {
            $search_query = '`color_title_'.$m[1].'`';
        }

        // Gestione confronti - ottimizzata
        $real_value = trim(str_replace(['&lt;', '&gt;'], ['<', '>'], $value));

        // Controlli ottimizzati per operatori
        $operators = [
            '>=' => ['>=', '> ='],
            '>' => ['>'],
            '<=' => ['<=', '< ='],
            '<' => ['<'],
            '=' => ['='],
            '!=' => ['!='],
        ];

        $operator = null;
        foreach ($operators as $op => $patterns) {
            foreach ($patterns as $pattern) {
                if (string_starts_with($real_value, $pattern)) {
                    $operator = $op;
                    break 2;
                }
            }
        }

        $start_with = string_starts_with($real_value, '^');
        $end_with = string_ends_with($real_value, '$');

        // Gestione operatori di confronto
        if ($operator && in_array($operator, ['>=', '>', '<=', '<'])) {
            return self::buildComparisonFilter($search_query, $operator, $value);
        }
        // Gestione uguaglianza
        elseif ($operator === '=') {
            return self::buildEqualityFilter($search_query, $value);
        }
        // Gestione disuguaglianza
        elseif ($operator === '!=') {
            return self::buildInequalityFilter($search_query, $value);
        }
        // Gestione pattern matching
        elseif ($start_with) {
            $clean_value = trim(str_replace(['^'], '', $value));

            return $search_query.' LIKE '.prepare($clean_value.'%');
        } elseif ($end_with) {
            $clean_value = trim(str_replace(['$'], '', $value));

            return $search_query.' LIKE '.prepare('%'.$clean_value);
        }
        // Gestione lista valori
        elseif (str_contains((string) $value, ',')) {
            $values = array_filter(array_map('trim', explode(',', (string) $value)));
            if (!empty($values)) {
                $escaped_values = array_map('prepare', $values);

                return $search_query.' IN ('.implode(', ', $escaped_values).')';
            }
        }
        // Ricerca standard LIKE
        else {
            return $search_query.' LIKE '.prepare('%'.$value.'%');
        }

        return null;
    }

    /**
     * Costruisce un filtro per confronti numerici e date.
     *
     * @param string $search_query
     * @param string $operator
     * @param string $value
     *
     * @return string
     */
    protected static function buildComparisonFilter($search_query, $operator, $value)
    {
        $clean_value = trim(str_replace(['&lt;', '&gt;', '>=', '>', '<=', '<', '> =', '< ='], '', $value));

        // Gestione date in formato DD/MM/YYYY
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $clean_value, $m)) {
            $date = "'{$m[3]}-{$m[2]}-{$m[1]}'";

            return $search_query.' '.$operator.' '.$date;
        }
        // Gestione valori numerici
        elseif (is_numeric($clean_value)) {
            return 'CAST('.$search_query.' AS DECIMAL(15,2)) '.$operator.' '.prepare($clean_value);
        }
        // Gestione valori stringa
        else {
            return $search_query.' '.$operator.' '.prepare($clean_value);
        }
    }

    /**
     * Costruisce un filtro per uguaglianza con gestione date.
     *
     * @param string $search_query
     * @param string $value
     *
     * @return string|null
     */
    protected static function buildEqualityFilter($search_query, $value)
    {
        $clean_value = trim(str_replace(['='], '', $value));

        // Gestione date
        if (str_contains($clean_value, '/')) {
            $date_parts = explode('/', $clean_value);
            if (count($date_parts) === 3) {
                [$giorno, $mese, $anno] = $date_parts;
                if (!empty($anno) && !empty($giorno) && !empty($mese)) {
                    $date = "'{$anno}-{$mese}-{$giorno}'";
                    if ($date !== "'1970-01-01'") {
                        return 'DATE('.$search_query.') = '.$date;
                    }
                }
            }
        }

        if (!$clean_value) {
            return '('.$search_query.' IS NULL OR '.$search_query.' = \'\')';
        }

        return $search_query.' = '.prepare($clean_value);
    }

    /**
     * Costruisce un filtro per disuguaglianza ottimizzato.
     *
     * @param string $search_query
     * @param string $value
     *
     * @return string
     */
    protected static function buildInequalityFilter($search_query, $value)
    {
        $clean_value = trim(str_replace(['!='], '', $value));
        $prepared_value = prepare($clean_value);

        return '('.$search_query.' != '.$prepared_value.')';
    }

    /**
     * Costruisce un filtro per il campo ID.
     *
     * @param string $field
     *
     * @return string|null
     */
    protected static function buildIdFilter($field, $original_value)
    {
        if (is_array($original_value)) {
            if (!empty($original_value)) {
                $safe_ids = array_filter(array_map('intval', $original_value));
                if (!empty($safe_ids)) {
                    return $field.' IN ('.implode(', ', $safe_ids).')';
                }
            }
        } else {
            $id = intval($original_value);
            if ($id > 0) {
                return $field.' = '.$id;
            }
        }

        return null;
    }

    /**
     * Sostituisce la prima occorenza di una determinata stringa.
     *
     * @param string $str_pattern
     * @param string $str_replacement
     * @param string $string
     *
     * @since 2.3
     *
     * @return string
     */
    protected static function str_replace_once($str_pattern, $str_replacement, $string)
    {
        if (str_contains($string, $str_pattern)) {
            return substr_replace($string, $str_replacement, strpos($string, $str_pattern), strlen($str_pattern));
        }

        return $string;
    }

    /**
     * Ottimizza una query rimuovendo spazi extra e migliorando la struttura.
     *
     * @param string $query
     *
     * @return string
     */
    protected static function optimizeQuery($query)
    {
        // Rimozione spazi multipli
        $query = preg_replace('/\s+/', ' ', $query);

        // Rimozione spazi attorno agli operatori
        $query = preg_replace('/\s*(=|!=|<|>|<=|>=)\s*/', '$1', (string) $query);

        // Trim generale
        return trim((string) $query);
    }

    /**
     * Valida una query per sicurezza di base.
     *
     * @param string $query
     *
     * @return bool
     */
    protected static function validateQuery($query)
    {
        // Lista di parole chiave pericolose
        $dangerous_keywords = [
            'DROP', 'DELETE', 'TRUNCATE', 'ALTER', 'CREATE', 'INSERT', 'UPDATE',
            'GRANT', 'REVOKE', 'EXEC', 'EXECUTE', 'UNION', 'LOAD_FILE', 'INTO OUTFILE',
        ];

        $upper_query = strtoupper($query);
        foreach ($dangerous_keywords as $keyword) {
            if (str_contains($upper_query, $keyword)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Interpreta lo standard modulare per l'individuazione delle query di un modulo/plugin del progetto.
     *
     * @throws \Exception
     *
     * @return array
     */
    protected static function readNewQuery($element)
    {
        $fields = [];
        $summable = [];
        $avg = [];
        $search_inside = [];
        $search = [];
        $format = [];
        $slow = [];
        $order_by = [];

        $query = $element['option'];

        // Aggiunta eventuali filtri dai segmenti per eseguire la query filtrata
        $query = str_replace('1=1', '1=1 '.\Modules::getAdditionalsQuery($element->id, null, self::$segments), $query);
        $views = self::getViews($element);
        $select = [];

        foreach ($views as $view) {
            $select[] = $view['query'].(!empty($view['title']) ? " AS '".$view['title']."'" : '');

            if (!empty($view['visible'])) {
                if (!empty($view['title'])) {
                    $view['title'] = trim((string) $view['title']);
                }
                if (!empty($view['search_inside'])) {
                    $view['search_inside'] = trim((string) $view['search_inside']);
                }
                if (!empty($view['order_by'])) {
                    $view['order_by'] = trim((string) $view['order_by']);
                }

                $fields[] = $view['title'] ? trim((string) $view['title']) : $view['name'];

                $search_inside[] = !empty($view['search_inside']) ? $view['search_inside'] : '`'.$view['title'].'`';
                $order_by[] = !empty($view['order_by']) ? $view['order_by'] : '`'.$view['title'].'`';
                $search[] = $view['search'];
                $slow[] = $view['slow'];
                $format[] = $view['format'];
                $html_format[] = $view['html_format'];

                if ($view['summable']) {
                    $summable[] = 'SUM(`'.trim($view['title']."`) AS 'sum_".(count($fields) - 1)."'");
                }

                if ($view['avg']) {
                    $avg[] = 'AVG(`'.trim($view['title']."`) AS 'avg_".(count($fields) - 1)."'");
                }
            }
        }

        $select = empty($select) ? '*' : implode(', ', $select);

        $query = str_replace('|select|', $select, $query);

        return [
            'query' => self::replacePlaceholder($query),
            'fields' => $fields,
            'search_inside' => $search_inside,
            'order_by' => $order_by,
            'search' => $search,
            'slow' => $slow,
            'format' => $format,
            'html_format' => $html_format,
            'summable' => $summable,
            'avg' => $avg,
        ];
    }

    /**
     * Interpreta lo standard JSON per l'individuazione delle query di un modulo/plugin del progetto.
     *
     * @param array $element
     *
     * @return array
     */
    protected static function readOldQuery($element)
    {
        $options = str_replace(["\r", "\n", "\t"], ' ', $element['option']);
        $options = json_decode($options, true);
        $options = $options['main_query'][0];

        $fields = [];
        $order_by = [];

        $search = [];
        $slow = [];
        $format = [];

        $query = $options['query'];
        $views = $options ? explode(',', (string) $options['fields']) : [];
        foreach ($views as $view) {
            $fields[] = trim($view);
            $order_by[] = '`'.trim($view).'`';

            $search[] = 1;
            $slow[] = 0;
            $format[] = 0;
        }

        $search_inside = $order_by;

        return [
            'query' => self::replacePlaceholder($query),
            'fields' => $fields,
            'search_inside' => $search_inside,
            'order_by' => $order_by,
            'search' => $search,
            'slow' => $slow,
            'format' => $format,
            'summable' => [],
        ];
    }

    /**
     * Restituisce le singole componenti delle query per un determinato modulo/plugin.
     *
     * @throws \Exception
     *
     * @return array
     */
    protected static function getViews($element)
    {
        $database = database();

        $user = \Auth::user();

        $views = $database->fetchArray('SELECT `zz_views`.*, `zz_views_lang`.*
        FROM 
            `zz_views` 
            LEFT JOIN `zz_views_lang` ON (`zz_views`.`id` = `zz_views_lang`.`id_record` AND `zz_views_lang`.`id_lang` = '.prepare(\Models\Locale::getDefault()->id).') 
            LEFT JOIN `zz_group_view` ON `zz_views`.`id` = `zz_group_view`.`id_vista`
            LEFT JOIN `zz_users` ON `zz_users`.`idgruppo` = `zz_group_view`.`id_gruppo`
        WHERE 
            `id_module`='.prepare($element['id']).' AND
            `zz_users`.`id` = '.prepare($user['id']).'
        ORDER BY 
            `order` ASC');

        return $views;
    }
}
