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

        // Se il file non esiste, segnala la mancanza e non marcare tutte le viste come non previste
        if (!file_exists($views_json_path)) {
            return [
                [
                    'id' => null,
                    'name' => '',
                    'module_name' => '',
                    'module_id' => null,
                    'reason' => 'File views.json assente',
                    'current_query' => '',
                    'expected_query' => '',
                ],
            ];
        }

        $views_data = json_decode(file_get_contents($views_json_path), true);

        if (is_array($views_data)) {
            // Il file views.json è organizzato per nome modulo
            foreach ($views_data as $module_name => $module_views) {
                $module_key = trim((string) $module_name);

                if ($module_key === '' || !is_array($module_views)) {
                    continue;
                }

                foreach ($module_views as $view_name => $view_query) {
                    $view_key = trim((string) $view_name);

                    if ($view_key === '') {
                        continue;
                    }

                    $standard_views[$module_key][$view_key] = $view_query;
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

            $module_name = trim((string) $view['module_name']);
            $view_name = trim((string) $view['name']);

            if ($view_name === '') {
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
            $current_query = preg_replace('/\s+/', ' ', (string) $current_query);
            $current_query = str_replace(['"', "'"], "'", $current_query);
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
                $expected_query = preg_replace('/\s+/', ' ', (string) $expected_query);
                $expected_query = str_replace(['"', "'"], "'", $expected_query);
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
            $module_name = trim((string) $view['module_name']);
            $view_name = trim((string) $view['name']);

            if ($module_name !== '' && $view_name !== '') {
                $db_views_by_module[$module_name][$view_name] = true;
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

/*
 * Normalizza una stringa rimuovendo elementi che non dovrebbero essere considerati come differenze
 *
 * @param string $text
 * @return string
 */
if (!function_exists('normalizeModuleOptions')) {
    function normalizeModuleOptions($text)
    {
        // Rimuovi tutti i tag BR (tutte le varianti)
        $text = preg_replace('/<br\s*\/?>/i', '', (string)$text);

        // Normalizza spazi multipli
        $text = preg_replace('/\s+/', ' ', $text);

        // Normalizza virgolette (mantieni i backtick per le query SQL)
        $text = str_replace(['"', "'"], "'", $text);

        // Normalizza entità HTML comuni
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim($text);
    }
}

/*
 * Ottiene l'elenco dei moduli personalizzati non previsti dal gestionale.
 *
 * @return array
 */
if (!function_exists('customModulesNotStandard')) {
    function customModulesNotStandard()
    {
        $database = database();

        // Leggi i moduli standard dal file modules.json nella root
        $standard_modules = [];
        $modules_json_path = base_dir().'/modules.json';

        // Se il file non esiste, segnala la mancanza e non marcare tutti i moduli come non previsti
        if (!file_exists($modules_json_path)) {
            return [
                [
                    'id' => null,
                    'name' => '',
                    'module_display_name' => '',
                    'reason' => 'File modules.json assente',
                    'current_options' => '',
                    'current_options2' => '',
                    'expected_options' => '',
                    'expected_options2' => '',
                ],
            ];
        }

        $modules_data = json_decode(file_get_contents($modules_json_path), true);

        if (is_array($modules_data)) {
            // Il file modules.json è organizzato per nome modulo
            foreach ($modules_data as $module_name => $module_data) {
                if (is_array($module_data)) {
                    $standard_modules[$module_name] = [
                        'options' => $module_data['options'] ?? '',
                        'options2' => $module_data['options2'] ?? '',
                    ];
                }
            }
        }

        // Ottieni tutti i moduli presenti nel database
        $query = "SELECT
            zm.id,
            zm.name,
            zm.options,
            zm.options2,
            COALESCE(zml.title, zm.name) as module_display_name
        FROM zz_modules zm
        LEFT JOIN zz_modules_lang zml ON (zm.id = zml.id_record AND zml.id_lang = (SELECT valore FROM zz_settings WHERE nome = 'Lingua'))
        WHERE 1=1
        ORDER BY module_display_name";

        $all_modules = $database->fetchArray($query);
        $custom_modules = [];

        foreach ($all_modules as $module) {
            $module_name = $module['name'];
            $is_custom = false;
            $reason = '';

            // Normalizza le options del modulo corrente
            $current_options = normalizeModuleOptions($module['options']);
            $current_options2 = normalizeModuleOptions($module['options2']);

            if (empty($module_name)) {
                continue;
            }

            // Controlla se il modulo non è previsto nel file standard
            if (!isset($standard_modules[$module_name])) {
                $is_custom = true;
                $reason = 'Modulo non previsto';
            }
            else {
                // Normalizza le options standard
                $expected_options = normalizeModuleOptions($standard_modules[$module_name]['options']);

                // Controlla se options2 è valorizzato (modulo personalizzato)
                if (!empty($current_options2)) {
                    $is_custom = true;
                    $reason = 'Options2 valorizzato';
                }
                // Controlla se options è diverso da quello standard
                elseif ($current_options !== $expected_options) {
                    $is_custom = true;
                    $reason = 'Options modificato';
                }
            }

            if ($is_custom) {
                $custom_modules[] = [
                    'id' => $module['id'],
                    'name' => $module_name,
                    'module_display_name' => $module['module_display_name'],
                    'reason' => $reason,
                    'current_options' => $module['options'],
                    'current_options2' => $module['options2'],
                    'expected_options' => $standard_modules[$module_name]['options'] ?? '',
                    'expected_options2' => $standard_modules[$module_name]['options2'] ?? ''
                ];
            }
        }

        return $custom_modules;
    }
}
