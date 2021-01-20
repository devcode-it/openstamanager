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

use Auth;
use Modules;
use Translator;

/**
 * Classe per la gestione delle interazione di base per le query dinamiche.
 *
 * @since 2.4.7
 */
class Query
{
    protected static $segments = true;

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

        $id_module = Modules::getCurrent()['id'];
        $segment = !empty(self::$segments) ? $_SESSION['module_'.$id_module]['id_segment'] : null;

        $user = Auth::user();

        // Sostituzione periodi temporali
        preg_match('|date_period\((.+?)\)|', $query, $matches);
        $date_query = $date_filter = null;
        if (!empty($matches)) {
            $dates = explode(',', $matches[1]);
            $date_filter = $matches[0];

            $filters = [];
            if ($dates[0] != 'custom') {
                foreach ($dates as $date) {
                    $filters[] = $date." BETWEEN '|period_start|' AND '|period_end|'";
                }
            } else {
                foreach ($dates as $k => $v) {
                    if ($k < 1) {
                        continue;
                    }
                    $filters[] = $v;
                }
            }
            $date_query = !empty($filters) && !empty(self::$segments) ? ' AND ('.implode(' OR ', $filters).')' : '';
        }

        // Sostituzione periodi temporali
        preg_match('|segment\((.+?)\)|', $query, $matches);
        $segment_name = !empty($matches[1]) ? $matches[1] : 'id_segment';
        $segment_filter = !empty($matches[0]) ? $matches[0] : 'segment';

        // Elenco delle sostituzioni
        $replace = [
            // Identificatori
            '|id_anagrafica|' => prepare($user['idanagrafica']),
            '|id_utente|' => prepare($user['id']),
            '|id_parent|' => prepare($id_parent),

            // Filtro temporale
            '|'.$date_filter.'|' => $date_query,

            // Date
            '|period_start|' => $_SESSION['period_start'],
            '|period_end|' => $_SESSION['period_end'].' 23:59:59',

            // Segmenti
            '|'.$segment_filter.'|' => !empty($segment) ? ' AND '.$segment_name.' = '.prepare($segment) : '',

            // Filtro dinamico per il modulo Giacenze sedi
            '|giacenze_sedi_idsede|' => prepare(isset($_SESSION['giacenze_sedi']) ? $_SESSION['giacenze_sedi']['idsede'] : null),
        ];

        // Sostituzione dei formati
        $patterns = formatter()->getSQLPatterns();

        foreach ($patterns as $key => $value) {
            $replace['|'.$key.'_format|'] = "'".$value."'";
        }

        // Sostituzione effettiva
        $query = replace($query, $replace);

        return $query;
    }

    /**
     * Genera la query prevista dalla struttura indicata.
     *
     * @param $structure
     * @param array $search
     * @param array $order
     * @param array $limit
     *
     * @throws \Exception
     *
     * @return mixed|string
     */
    public static function getQuery($structure, $search = [], $order = [], $limit = [])
    {
        $total = self::readQuery($structure);

        // Lettura parametri modulo
        $query = $total['query'];

        if (empty($query) || $query == 'menu' || $query == 'custom') {
            return '';
        }

        // Filtri di ricerca
        $search_filters = [];
        foreach ($search as $field => $original_value) {
            $pos = array_search($field, $total['fields']);
            $value = trim($original_value);

            if (isset($value) && $pos !== false) {
                $search_query = $total['search_inside'][$pos];

                // Campo con ricerca personalizzata
                if (string_contains($search_query, '|search|')) {
                    $pieces = explode(',', $value);
                    foreach ($pieces as $piece) {
                        $piece = trim($piece);
                        $search_filters[] = str_replace('|search|', prepare('%'.$piece.'%'), $search_query);
                    }
                }

                // Campi tradizionali: ricerca tramite like
                else {
                    // Ricerca nei titoli icon_title_* per le icone icon_*
                    if (preg_match('/^icon_(.+?)$/', $field, $m)) {
                        $search_query = '`icon_title_'.$m[1].'`';
                    }

                    // Ricerca nei titoli color_title_* per i colori color_*
                    elseif (preg_match('/^color_(.+?)$/', $field, $m)) {
                        $search_query = '`color_title_'.$m[1].'`';
                    }

                    // Gestione confronti
                    $real_value = trim(str_replace(['&lt;', '&gt;'], ['<', '>'], $value));
                    $more = string_starts_with($real_value, '>=') || string_starts_with($real_value, '> =') || string_starts_with($real_value, '>');
                    $minus = string_starts_with($real_value, '<=') || string_starts_with($real_value, '< =') || string_starts_with($real_value, '<');
                    $equal = string_starts_with($real_value, '=');

                    if ($minus || $more || $equal) {
                        $sign = string_contains($real_value, '=') ? '=' : '';
                        if ($more) {
                            $sign = '>'.$sign;
                        } elseif ($minus) {
                            $sign = '<'.$sign;
                        } else {
                            $sign = '=';
                        }

                        $value = trim(str_replace(['&lt;', '=', '&gt;'], '', $value));

                        if ($more || $minus) {
                            $search_filters[] = 'CAST('.$search_query.' AS UNSIGNED) '.$sign.' '.prepare($value);
                        } else {
                            $search_filters[] = $search_query.' = '.prepare($value);
                        }
                    } else {
                        $search_filters[] = $search_query.' LIKE '.prepare('%'.$value.'%');
                    }
                }
            }

            // Campo id: ricerca tramite comparazione
            elseif ($field == 'id') {
                // Filtro per una serie di ID
                if (is_array($original_value)) {
                    if (!empty($original_value)) {
                        $search_filters[] = $field.' IN ('.implode(', ', $original_value).')';
                    }
                } else {
                    $search_filters[] = $field.' = '.prepare($value);
                }
            }

            // Ricerca
            if (!empty($search_filters)) {
                $query = str_replace('2=2', '2=2 AND ('.implode(' AND ', $search_filters).') ', $query);
            }
        }

        // Ordinamento dei risultati
        if (isset($order['dir']) && isset($order['column'])) {
            //$pos = array_search($order['column'], $total['fields']);
            $pos = $order['column'];

            if ($pos !== false) {
                $pieces = explode('ORDER', $query);

                $count = count($pieces);
                if ($count > 1) {
                    unset($pieces[$count - 1]);
                }

                $query = implode('ORDER', $pieces).' ORDER BY '.$total['order_by'][$order['column']].' '.$order['dir'];
            }
        }

        // Paginazione
        if (!empty($limit) && intval($limit['length']) > 0) {
            $query .= ' LIMIT '.$limit['start'].', '.$limit['length'];
        }

        return $query;
    }

    public static function executeAndCount($query)
    {
        $database = database();

        // Esecuzione della query
        $query = self::str_replace_once('SELECT', 'SELECT SQL_CALC_FOUND_ROWS', $query);
        $results = $database->fetchArray($query);

        // Conteggio dei record filtrati
        $count = $database->fetchOne('SELECT FOUND_ROWS() AS count');

        return [
            'results' => $results,
            'count' => $count['count'],
        ];
    }

    /**
     * Restituisce le somme richieste dalla query prevista dalla struttura.
     *
     * @param $structure
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
            $result_query = Modules::replaceAdditionals($structure->id, $result_query);
        }

        $query = self::str_replace_once('SELECT', 'SELECT '.implode(', ', $total['summable']).' FROM(SELECT ', $result_query).') AS `z`';
        $sums = database()->fetchOne($query);

        $results = [];
        if (!empty($sums)) {
            foreach ($sums as $key => $sum) {
                if (string_contains($key, 'sum_')) {
                    $results[str_replace('sum_', '', $key)] = Translator::numberToLocale($sum);
                }
            }
        }

        return $results;
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
        if (strpos($string, $str_pattern) !== false) {
            $occurrence = strpos($string, $str_pattern);

            return substr_replace($string, $str_replacement, strpos($string, $str_pattern), strlen($str_pattern));
        }

        return $string;
    }

    /**
     * Interpreta lo standard modulare per l'individuazione delle query di un modulo/plugin del progetto.
     *
     * @param $element
     *
     * @throws \Exception
     *
     * @return array
     */
    protected static function readNewQuery($element)
    {
        $fields = [];
        $summable = [];
        $search_inside = [];
        $search = [];
        $format = [];
        $slow = [];
        $order_by = [];

        $query = $element['option'];

        // Aggiunta eventuali filtri dai segmenti per eseguire la query filtrata
        $query = str_replace('1=1', '1=1 '.Modules::getAdditionalsQuery($element['attributes']['name'], null, self::$segments), $query);
        $views = self::getViews($element);

        $select = [];

        foreach ($views as $view) {
            $select[] = $view['query'].(!empty($view['name']) ? " AS '".$view['name']."'" : '');

            if (!empty($view['visible'])) {
                $view['name'] = trim($view['name']);
                $view['search_inside'] = trim($view['search_inside']);
                $view['order_by'] = trim($view['order_by']);

                $fields[] = trim($view['name']);

                $search_inside[] = !empty($view['search_inside']) ? $view['search_inside'] : '`'.$view['name'].'`';
                $order_by[] = !empty($view['order_by']) ? $view['order_by'] : '`'.$view['name'].'`';
                $search[] = $view['search'];
                $slow[] = $view['slow'];
                $format[] = $view['format'];

                if ($view['summable']) {
                    $summable[] = 'SUM(`'.trim($view['name']."`) AS 'sum_".(count($fields) - 1)."'");
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
            'summable' => $summable,
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
        $views = explode(',', $options['fields']);
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
     * @param $element
     *
     * @throws \Exception
     *
     * @return array
     */
    protected static function getViews($element)
    {
        $database = database();

        $user = Auth::user();

        $views = $database->fetchArray('SELECT * FROM `zz_views` WHERE `id_module`='.prepare($element['id']).' AND
        `id` IN (
            SELECT `id_vista` FROM `zz_group_view` WHERE `id_gruppo`=(
                SELECT `idgruppo` FROM `zz_users` WHERE `id`='.prepare($user['id']).'
            ))
        ORDER BY `order` ASC');

        return $views;
    }
}
