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

/*
 * Controlla se il database presenta alcune sezioni personalizzate.
 *
 * @return array
 */

if (!function_exists('customStructure')) {
    function customStructure()
    {
        $results = [];

        $dirs = [
            'modules',
            'templates',
            'plugins',
        ];

        // Controlli di personalizzazione fisica
        foreach ($dirs as $dir) {
            $files = glob(base_dir().'/'.$dir.'/*/custom/*.{php,html}', GLOB_BRACE);
            $recursive_files = glob(base_dir().'/'.$dir.'/*/custom/**/*.{php,html}', GLOB_BRACE);

            $files = array_merge($files, $recursive_files);

            foreach ($files as $file) {
                $file = str_replace(base_dir().'/', '', $file);
                $result = explode('/custom/', $file)[0];

                if (!in_array($result, $results)) {
                    $results[] = $result;
                }
            }
        }

        // Gestione cartella include
        $files = glob(base_dir().'/include/custom/*.{php,html}', GLOB_BRACE);
        $recursive_files = glob(base_dir().'/include/custom/**/*.{php,html}', GLOB_BRACE);

        $files = array_merge($files, $recursive_files);

        foreach ($files as $file) {
            $file = str_replace(base_dir().'/', '', $file);
            $result = explode('/custom/', $file)[0];

            if (!in_array($result, $results)) {
                $results[] = $result;
            }
        }

        return $results;
    }
}

/*
 * Controlla se il database presenta alcune sezioni personalizzate.
 *
 * @return array
 */
if (!function_exists('customTables')) {
    function customTables()
    {
        $tables = include base_dir().'/update/tables.php';

        $names = [];
        foreach ($tables as $table) {
            $names[] = prepare($table);
        }

        $database = database();

        $results = $database->fetchArray('SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '.prepare($database->getDatabaseName()).' AND TABLE_NAME NOT IN ('.implode(',', $names).") AND TABLE_NAME != 'updates'");

        return array_column($results, 'TABLE_NAME');
    }
}

/*
 * Controlla se il database presenta alcune sezioni personalizzate.
 *
 * @return array
 */

if (!function_exists('customDatabase')) {
    function customDatabase()
    {
        $database = database();
        $modules = $database->fetchArray("SELECT `title`, CONCAT('modules/', `directory`) AS directory FROM `zz_modules` LEFT JOIN `zz_modules_lang` ON (`zz_modules`.`id` = `zz_modules_lang`.`id_record` AND `zz_modules_lang`.`id_lang` = ".prepare(Models\Locale::getDefault()->id).") WHERE `options2` != ''");
        $plugins = $database->fetchArray("SELECT `title`, CONCAT('plugins/', `directory`) AS directory FROM `zz_plugins` LEFT JOIN `zz_plugins_lang` ON (`zz_plugins`.`id` = `zz_plugins_lang`.`id_record` AND `zz_plugins_lang`.`id_lang` = ".prepare(Models\Locale::getDefault()->id).") WHERE `options2` != ''");

        $results = array_merge($modules, $plugins);

        return $results;
    }
}

if (!function_exists('customComponents')) {
    function customComponents()
    {
        $database_check = customDatabase();
        $structure_check = customStructure();

        $list = [];
        foreach ($database_check as $element) {
            $pos = array_search($element['directory'], $structure_check);

            $list[] = [
                'path' => $element['directory'],
                'database' => true,
                'directory' => $pos !== false,
            ];

            if ($pos !== false) {
                unset($structure_check[$pos]);
            }
        }

        foreach ($structure_check as $element) {
            $list[] = [
                'path' => $element,
                'database' => false,
                'directory' => true,
            ];
        }

        return $list;
    }
}

/*
 * Ottiene l'elenco dei campi personalizzati aggiunti al sistema.
 *
 * @return array
 */
if (!function_exists('customFields')) {
    function customFields()
    {
        $database = database();

        // Ottieni l'ID della lingua di default
        $default_lang = $database->fetchOne("SELECT valore FROM zz_settings WHERE nome = 'Lingua'")['valore'] ?? 1;

        $query = "SELECT
            zz_fields.id,
            zz_fields.name,
            COALESCE(zz_modules_lang.title, zz_modules.name) as module_name,
            COALESCE(zz_plugins_lang.title, zz_plugins.name) as plugin_name,
            zz_fields.created_at,
            zz_fields.updated_at
        FROM zz_fields
        LEFT JOIN zz_modules ON zz_fields.id_module = zz_modules.id
        LEFT JOIN zz_modules_lang ON (zz_modules.id = zz_modules_lang.id_record AND zz_modules_lang.id_lang = ".prepare($default_lang).")
        LEFT JOIN zz_plugins ON zz_fields.id_plugin = zz_plugins.id
        LEFT JOIN zz_plugins_lang ON (zz_plugins.id = zz_plugins_lang.id_record AND zz_plugins_lang.id_lang = ".prepare($default_lang).")
        ORDER BY module_name, plugin_name, zz_fields.name";

        $results = $database->fetchArray($query);

        return $results;
    }
}

/*
 * Ottiene l'elenco delle viste modificate (con query personalizzata).
 *
 * @return array
 */
if (!function_exists('customViews')) {
    function customViews()
    {
        $database = database();

        // Ottieni l'ID della lingua di default
        $default_lang = $database->fetchOne("SELECT valore FROM zz_settings WHERE nome = 'Lingua'")['valore'] ?? 1;

        $query = "SELECT
            zz_modules.id,
            COALESCE(zz_modules_lang.title, zz_modules.name) as module_name,
            zz_modules.directory,
            zz_modules.updated_at
        FROM zz_modules
        LEFT JOIN zz_modules_lang ON (zz_modules.id = zz_modules_lang.id_record AND zz_modules_lang.id_lang = ".prepare($default_lang).")
        WHERE zz_modules.options2 != '' AND zz_modules.options2 IS NOT NULL
        ORDER BY module_name";

        $results = $database->fetchArray($query);

        return $results;
    }
}

/*
 * Ottiene l'elenco dei file presenti nelle cartelle custom.
 *
 * @return array
 */
if (!function_exists('customStructureWithFiles')) {
    function customStructureWithFiles()
    {
        $results = [];

        $dirs = [
            'modules',
            'templates',
            'plugins',
        ];

        // Controlli di personalizzazione fisica
        foreach ($dirs as $dir) {
            $files = glob(base_dir().'/'.$dir.'/*/custom/*.{php,html}', GLOB_BRACE);
            $recursive_files = glob(base_dir().'/'.$dir.'/*/custom/**/*.{php,html}', GLOB_BRACE);

            $files = array_merge($files, $recursive_files);

            $grouped_files = [];
            foreach ($files as $file) {
                $file = str_replace(base_dir().'/', '', $file);
                $path_parts = explode('/custom/', $file);
                $base_path = $path_parts[0];
                $file_name = basename($file);

                if (!isset($grouped_files[$base_path])) {
                    $grouped_files[$base_path] = [];
                }
                $grouped_files[$base_path][] = $file_name;
            }

            foreach ($grouped_files as $path => $file_list) {
                $results[] = [
                    'path' => $path,
                    'files' => $file_list
                ];
            }
        }

        // Gestione cartella include
        $files = glob(base_dir().'/include/custom/*.{php,html}', GLOB_BRACE);
        $recursive_files = glob(base_dir().'/include/custom/**/*.{php,html}', GLOB_BRACE);

        $files = array_merge($files, $recursive_files);

        if (!empty($files)) {
            $include_files = [];
            foreach ($files as $file) {
                $file = str_replace(base_dir().'/', '', $file);
                $include_files[] = basename($file);
            }

            $results[] = [
                'path' => 'include',
                'files' => $include_files
            ];
        }

        return $results;
    }
}

/*
 * Ottiene l'elenco delle viste personalizzate non previste dal gestionale.
 *
 * @return array
 */
if (!function_exists('customViewsNotStandard')) {
    function customViewsNotStandard()
    {
        $database = database();

        // Leggi le viste standard dal file views.json nella root
        $standard_views = [];
        $views_json_path = base_dir().'/views.json';

        if (file_exists($views_json_path)) {
            $views_data = json_decode(file_get_contents($views_json_path), true);

            if (is_array($views_data)) {
                // Il file views.json è organizzato per nome modulo
                foreach ($views_data as $module_name => $module_views) {
                    if (is_array($module_views)) {
                        foreach ($module_views as $view_name => $view_query) {
                            $standard_views[$module_name][$view_name] = $view_query;
                        }
                    }
                }
            }
        }

        // Ottieni tutte le viste presenti nel database
        $query = "SELECT
            zv.id,
            zv.name,
            zv.query,
            zv.id_module,
            zm.name as module_name,
            COALESCE(zml.title, zm.name) as module_display_name
        FROM zz_views zv
        LEFT JOIN zz_modules zm ON zv.id_module = zm.id
        LEFT JOIN zz_modules_lang zml ON (zm.id = zml.id_record AND zml.id_lang = (SELECT valore FROM zz_settings WHERE nome = 'Lingua'))
        WHERE 1=1
        ORDER BY module_display_name, zv.name";

        $all_views = $database->fetchArray($query);
        $custom_views = [];

        foreach ($all_views as $view) {
            $is_custom = false;
            $reason = '';
            $expected_query = '';

            $module_name = $view['module_name'];
            $view_name = $view['name'];

            if (empty($view_name) || empty(trim($view_name))) {
                $custom_views[] = [
                    'id' => $view['id'],
                    'name' => '', // Nome vuoto
                    'module_name' => $view['module_display_name'],
                    'module_id' => $view['id_module'],
                    'reason' => 'Vista mancante',
                    'current_query' => $view['query'],
                    'expected_query' => ''
                ];
                continue;
            }

            $current_query = trim($view['query']);
            $current_query = preg_replace('/<br\s*\/?>/i', '', $current_query);
            $current_query = preg_replace('/\s+/', ' ', $current_query);
            $current_query = str_replace(['"', "'", '`'], "'", $current_query);
            $current_query = html_entity_decode($current_query, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $current_query = trim($current_query);

            if (empty($module_name)) {
                continue;
            }

            if (!isset($standard_views[$module_name])) {
                $is_custom = true;
                $reason = 'Modulo non previsto';
            }

            elseif (!isset($standard_views[$module_name][$view_name])) {
                $is_custom = true;
                $reason = 'Vista aggiuntiva';
            }
            else {
                $expected_query = trim($standard_views[$module_name][$view_name]);

                $expected_query = preg_replace('/<br\s*\/?>/i', '', $expected_query);
                $expected_query = preg_replace('/\s+/', ' ', $expected_query);
                $expected_query = str_replace(['"', "'", '`'], "'", $expected_query);
                $expected_query = html_entity_decode($expected_query, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $expected_query = trim($expected_query);

                if (empty($expected_query)) {
                    $is_custom = true;
                    $reason = 'Vista aggiuntiva';
                } else {
                    if ($current_query !== $expected_query) {
                        $is_custom = true;
                        $reason = 'Query modificata';
                    }
                }
            }

            if ($is_custom) {
                $custom_views[] = [
                    'id' => $view['id'],
                    'name' => $view_name,
                    'module_name' => $view['module_display_name'],
                    'module_id' => $view['id_module'],
                    'reason' => $reason,
                    'current_query' => $view['query'],
                    'expected_query' => $expected_query,
                    'debug_module_name' => $module_name // Per debug
                ];
            }
        }

        $db_views_by_module = [];
        foreach ($all_views as $view) {
            $module_name = $view['module_name'];
            if (!empty($module_name)) {
                $db_views_by_module[$module_name][$view['name']] = true;
            }
        }

        foreach ($standard_views as $module_name => $module_views) {
            foreach ($module_views as $view_name => $expected_query) {
                if (empty(trim($expected_query))) {
                    continue;
                }

                if (!isset($db_views_by_module[$module_name][$view_name])) {
                    $custom_views[] = [
                        'id' => null,
                        'name' => $view_name,
                        'module_name' => $module_name,
                        'module_id' => null,
                        'reason' => 'Vista mancante',
                        'current_query' => '',
                        'expected_query' => $expected_query
                    ];
                }
            }
        }

        return $custom_views;
    }
}
