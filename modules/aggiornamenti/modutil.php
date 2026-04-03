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

// ========================================================================
// FUNZIONI HELPER PER RICERCA FILE PERSONALIZZATI
// ========================================================================

/**
 * Cerca file personalizzati in una directory.
 */
function findCustomFilesInDirectory($base_path)
{
    $files = glob($base_path.'/*.{php,html}', GLOB_BRACE) ?: [];
    $recursive_files = glob($base_path.'/**/*.{php,html}', GLOB_BRACE) ?: [];

    return array_merge($files, $recursive_files);
}

/**
 * Estrae il percorso base da un file personalizzato.
 */
function extractBasePathFromCustomFile($file)
{
    $file = str_replace(base_dir().'/', '', $file);

    return explode('/custom/', $file)[0];
}

/*
 * Controlla se il database presenta alcune sezioni personalizzate.
 *
 * @return array
 */
if (!function_exists('customStructure')) {
    function customStructure()
    {
        $results = [];
        $dirs = ['modules', 'templates', 'plugins'];

        // Controlli di personalizzazione fisica
        foreach ($dirs as $dir) {
            $files = findCustomFilesInDirectory(base_dir().'/'.$dir.'/*/custom');
            foreach ($files as $file) {
                $result = extractBasePathFromCustomFile($file);
                if (!in_array($result, $results)) {
                    $results[] = $result;
                }
            }
        }

        // Gestione cartella include
        $files = findCustomFilesInDirectory(base_dir().'/include/custom');
        foreach ($files as $file) {
            $result = extractBasePathFromCustomFile($file);
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

        // Carica e accoda le tabelle dai file tables.php presenti nelle cartelle update di moduli e plugin
        $module_tables_files = array_merge(
            glob(base_dir().'/modules/*/update/tables.php') ?: [],
            glob(base_dir().'/plugins/*/update/tables.php') ?: []
        );

        if (!empty($module_tables_files)) {
            foreach ($module_tables_files as $module_tables_file) {
                $module_tables = include $module_tables_file;

                if (!empty($module_tables) && is_array($module_tables)) {
                    // Accoda le tabelle del modulo a quelle principali
                    $tables = array_merge($tables, $module_tables);
                }
            }
        }

        // Determina il file di riferimento per il database in base al tipo di DBMS
        $file_to_check_database = 'mysql.json';
        $database = database();
        if ($database->getType() === 'MariaDB') {
            $file_to_check_database = 'mariadb_10_x.json';
        } elseif ($database->getType() === 'MySQL') {
            $mysql_min_version = '8.0.0';
            $mysql_max_version = '8.3.99';
            $file_to_check_database = ((version_compare($database->getMySQLVersion(), $mysql_min_version, '>=') && version_compare($database->getMySQLVersion(), $mysql_max_version, '<=')) ? 'mysql.json' : 'mysql_8_3.json');
        }

        // Carica e accoda le tabelle dai file JSON del database presenti nelle sottocartelle di moduli e plugin
        $database_json_files = aggiornamentiGetDatabaseReferenceFiles($file_to_check_database);

        if (!empty($database_json_files)) {
            foreach ($database_json_files as $database_json_file) {
                $database_data = aggiornamentiReadJsonFile($database_json_file);

                if (!empty($database_data) && is_array($database_data)) {
                    // Estrai i nomi delle tabelle dalle chiavi del JSON
                    $module_tables = array_keys($database_data);
                    // Accoda le tabelle del modulo a quelle principali
                    $tables = array_merge($tables, $module_tables);
                }
            }
        }

        $names = [];
        foreach ($tables as $table) {
            $names[] = prepare($table);
        }

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

        $query = 'SELECT
            zz_fields.id,
            zz_fields.name,
            COALESCE(zz_modules_lang.title, zz_modules.name) as module_name,
            COALESCE(zz_plugins_lang.title, zz_plugins.name) as plugin_name,
            zz_fields.created_at,
            zz_fields.updated_at
        FROM zz_fields
        LEFT JOIN zz_modules ON zz_fields.id_module = zz_modules.id
        LEFT JOIN zz_modules_lang ON (zz_modules.id = zz_modules_lang.id_record AND zz_modules_lang.id_lang = '.prepare($default_lang).')
        LEFT JOIN zz_plugins ON zz_fields.id_plugin = zz_plugins.id
        LEFT JOIN zz_plugins_lang ON (zz_plugins.id = zz_plugins_lang.id_record AND zz_plugins_lang.id_lang = '.prepare($default_lang).')
        ORDER BY module_name, plugin_name, zz_fields.name';

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

        $query = 'SELECT
            zz_modules.id,
            COALESCE(zz_modules_lang.title, zz_modules.name) as module_name,
            zz_modules.directory,
            zz_modules.updated_at
        FROM zz_modules
        LEFT JOIN zz_modules_lang ON (zz_modules.id = zz_modules_lang.id_record AND zz_modules_lang.id_lang = '.prepare($default_lang).")
        WHERE zz_modules.options2 != '' AND zz_modules.options2 IS NOT NULL
        ORDER BY module_name";

        $results = $database->fetchArray($query);

        return $results;
    }
}

/**
 * Raggruppa file personalizzati per percorso.
 */
function groupCustomFilesByPath($files)
{
    $grouped = [];
    foreach ($files as $file) {
        $file = str_replace(base_dir().'/', '', $file);
        $path_parts = explode('/custom/', $file);
        $base_path = $path_parts[0];
        $file_name = basename($file);

        if (!isset($grouped[$base_path])) {
            $grouped[$base_path] = [];
        }
        $grouped[$base_path][] = $file_name;
    }

    return $grouped;
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
        $dirs = ['modules', 'templates', 'plugins'];

        // Controlli di personalizzazione fisica
        foreach ($dirs as $dir) {
            $files = findCustomFilesInDirectory(base_dir().'/'.$dir.'/*/custom');
            $grouped_files = groupCustomFilesByPath($files);

            foreach ($grouped_files as $path => $file_list) {
                $results[] = [
                    'path' => $path,
                    'files' => $file_list,
                ];
            }
        }

        // Gestione cartella include
        $files = findCustomFilesInDirectory(base_dir().'/include/custom');
        if (!empty($files)) {
            $include_files = array_map(fn ($file) => basename(str_replace(base_dir().'/', '', $file)), $files);
            $results[] = [
                'path' => 'include',
                'files' => $include_files,
            ];
        }

        return $results;
    }
}

if (!function_exists('aggiornamentiReadJsonFile')) {
    function aggiornamentiReadJsonFile($file_path)
    {
        if (!file_exists($file_path)) {
            return [];
        }

        $contents = file_get_contents($file_path);
        $data = json_decode($contents, true);

        return is_array($data) ? $data : [];
    }
}

if (!function_exists('aggiornamentiGetReferenceJsonFiles')) {
    function aggiornamentiGetReferenceJsonFiles($module_filename, $plugin_filename = null)
    {
        $plugin_filename = $plugin_filename ?: $module_filename;

        return array_merge(
            glob(base_dir().'/modules/*/'.$module_filename) ?: [],
            glob(base_dir().'/plugins/*/'.$plugin_filename) ?: []
        );
    }
}

if (!function_exists('aggiornamentiGetComponentTypeFromPath')) {
    function aggiornamentiGetComponentTypeFromPath($path)
    {
        return str_contains((string) $path, '/plugins/') ? 'plugin' : 'module';
    }
}

if (!function_exists('aggiornamentiGetComponentDisplayName')) {
    function aggiornamentiGetComponentDisplayName($component_dir)
    {
        $component_files = [
            $component_dir.'/MODULE',
            $component_dir.'/PLUGIN',
        ];

        foreach ($component_files as $component_file) {
            if (!file_exists($component_file)) {
                continue;
            }

            $info = Util\Ini::readFile($component_file);
            $name = trim((string) ($info['name'] ?? ''));

            if ($name !== '') {
                return $name;
            }
        }

        return basename((string) $component_dir);
    }
}

if (!function_exists('aggiornamentiNormalizeModuleDefinitions')) {
    function aggiornamentiNormalizeModuleDefinitions($module_data, $default_name = '')
    {
        $normalized = [];
        $default_name = trim((string) $default_name);

        if (empty($module_data) || !is_array($module_data)) {
            return $normalized;
        }

        $is_single_definition = array_key_exists('options', $module_data)
            || array_key_exists('options2', $module_data)
            || array_key_exists('name', $module_data);

        if ($is_single_definition) {
            $module_name = trim((string) ($module_data['name'] ?? $default_name));

            if ($module_name !== '') {
                $normalized[$module_name] = [
                    'options' => $module_data['options'] ?? '',
                    'options2' => $module_data['options2'] ?? '',
                ];
            }

            return $normalized;
        }

        foreach ($module_data as $module_name => $data) {
            if (!is_array($data)) {
                continue;
            }

            $resolved_name = trim((string) ($data['name'] ?? $module_name));

            if ($resolved_name === '') {
                continue;
            }

            $normalized[$resolved_name] = [
                'options' => $data['options'] ?? '',
                'options2' => $data['options2'] ?? '',
            ];
        }

        return $normalized;
    }
}

if (!function_exists('aggiornamentiGetPremiumModuleDefinitions')) {
    function aggiornamentiGetPremiumModuleDefinitions()
    {
        $result = [
            'definitions' => [],
            'component_names' => [],
            'component_types' => [],
            'folders' => [],
        ];

        $module_json_files = aggiornamentiGetReferenceJsonFiles('modules.json', 'module.json');

        foreach ($module_json_files as $module_json_file) {
            $component_dir = dirname((string) $module_json_file);
            $folder_name = basename($component_dir);
            $component_name = aggiornamentiGetComponentDisplayName($component_dir);
            $component_type = aggiornamentiGetComponentTypeFromPath($module_json_file);
            $module_data = aggiornamentiNormalizeModuleDefinitions(
                aggiornamentiReadJsonFile($module_json_file),
                $folder_name
            );

            $result['component_names'][$folder_name] = $component_name;
            $result['component_types'][$folder_name] = $component_type;

            foreach ($module_data as $module_name => $data) {
                $result['definitions'][$module_name] = [
                    'options' => $data['options'] ?? '',
                    'options2' => $data['options2'] ?? '',
                    'folder' => $folder_name,
                    'component_name' => $component_name,
                ];
                $result['folders'][$module_name] = $folder_name;
            }
        }

        return $result;
    }
}

if (!function_exists('aggiornamentiMergeSettingsReferenceData')) {
    function aggiornamentiMergeSettingsReferenceData($data_settings)
    {
        $data_settings = is_array($data_settings) ? $data_settings : [];
        $premium_settings = [];

        foreach (aggiornamentiGetReferenceJsonFiles('settings.json') as $settings_json_file) {
            $settings_data = aggiornamentiReadJsonFile($settings_json_file);

            if (empty($settings_data) || !is_array($settings_data)) {
                continue;
            }

            $component_dir = dirname((string) $settings_json_file);
            $component_name = aggiornamentiGetComponentDisplayName($component_dir);
            $component_type = aggiornamentiGetComponentTypeFromPath($settings_json_file);

            foreach ($settings_data as $setting_name => $setting_type) {
                $setting_key = trim((string) $setting_name);

                if ($setting_key === '') {
                    continue;
                }

                $premium_settings[$setting_key] = [
                    'name' => $component_name,
                    'type' => $component_type,
                ];
            }

            $data_settings = array_merge($data_settings, $settings_data);
        }

        return [
            'data' => $data_settings,
            'premium_settings' => $premium_settings,
        ];
    }
}

if (!function_exists('aggiornamentiGetCurrentPremiumSettings')) {
    function aggiornamentiGetCurrentPremiumSettings($settings, $premium_settings, $data_settings = [])
    {
        $current_premium_settings = [];

        foreach ((array) $settings as $setting_name => $current_type) {
            $setting_key = trim((string) $setting_name);

            if ($setting_key === '' || !isset($premium_settings[$setting_key])) {
                continue;
            }

            $current_premium_settings[$setting_key] = [
                'current' => $current_type,
                'expected' => $data_settings[$setting_key] ?? null,
                'premium_setting' => $premium_settings[$setting_key],
            ];
        }

        return $current_premium_settings;
    }
}

if (!function_exists('aggiornamentiMergeWidgetsReferenceData')) {
    function aggiornamentiMergeWidgetsReferenceData($data_widgets)
    {
        $data_widgets = is_array($data_widgets) ? $data_widgets : [];
        $premium_widgets = [];

        foreach (aggiornamentiGetReferenceJsonFiles('widgets.json') as $widgets_json_file) {
            $widgets_data = aggiornamentiReadJsonFile($widgets_json_file);

            if (empty($widgets_data) || !is_array($widgets_data)) {
                continue;
            }

            $component_dir = dirname((string) $widgets_json_file);
            $component_name = aggiornamentiGetComponentDisplayName($component_dir);
            $component_type = aggiornamentiGetComponentTypeFromPath($widgets_json_file);

            foreach ($widgets_data as $module_name => $module_widgets) {
                if (!is_array($module_widgets)) {
                    continue;
                }

                foreach ($module_widgets as $widget_name => $widget_query) {
                    $widget_key = $module_name.':'.$widget_name;

                    $premium_widgets[$widget_key] = [
                        'name' => $component_name,
                        'type' => $component_type,
                    ];
                }

                // Accoda i widgets del componente a quelli principali
                if (!isset($data_widgets[$module_name])) {
                    $data_widgets[$module_name] = [];
                }
                $data_widgets[$module_name] = array_merge($data_widgets[$module_name], $module_widgets);
            }
        }

        return [
            'data' => $data_widgets,
            'premium_widgets' => $premium_widgets,
        ];
    }
}

if (!function_exists('aggiornamentiFindPremiumWidgetReference')) {
    function aggiornamentiFindPremiumWidgetReference($module_name, $widget_name, $premium_widgets, $data_widgets = [])
    {
        $module_key = trim((string) $module_name);
        $widget_key = trim((string) $widget_name);

        if ($module_key === '' || $widget_key === '') {
            return null;
        }

        $reference_key = $module_key.':'.$widget_key;
        if (isset($premium_widgets[$reference_key])) {
            return [
                'key' => $reference_key,
                'module_name' => $module_key,
                'widget_name' => $widget_key,
                'expected_query' => $data_widgets[$module_key][$widget_key] ?? null,
                'premium_widget' => $premium_widgets[$reference_key],
            ];
        }

        foreach ($premium_widgets as $premium_key => $premium_info) {
            $premium_parts = explode(':', (string) $premium_key, 2);

            if (count($premium_parts) !== 2 || trim((string) $premium_parts[1]) !== $widget_key) {
                continue;
            }

            $premium_module_name = trim((string) $premium_parts[0]);

            return [
                'key' => $premium_key,
                'module_name' => $premium_module_name,
                'widget_name' => $widget_key,
                'expected_query' => $data_widgets[$premium_module_name][$widget_key] ?? null,
                'premium_widget' => $premium_info,
            ];
        }

        return null;
    }
}

if (!function_exists('aggiornamentiGetCurrentPremiumWidgets')) {
    function aggiornamentiGetCurrentPremiumWidgets($widgets, $premium_widgets, $data_widgets = [])
    {
        $current_premium_widgets = [];

        foreach ((array) $widgets as $module_key => $module_widgets) {
            if (!is_array($module_widgets)) {
                continue;
            }

            foreach ($module_widgets as $widget_name => $current_query) {
                $premium_reference = aggiornamentiFindPremiumWidgetReference($module_key, $widget_name, $premium_widgets, $data_widgets);

                if ($premium_reference === null) {
                    continue;
                }

                $current_premium_widgets[$module_key][$widget_name] = [
                    'current' => $current_query,
                    'expected' => $premium_reference['expected_query'],
                    'premium_widget' => $premium_reference['premium_widget'],
                    'reference_module_name' => $premium_reference['module_name'],
                ];
            }
        }

        return $current_premium_widgets;
    }
}

if (!function_exists('aggiornamentiGetDatabaseReferenceFiles')) {
    function aggiornamentiGetDatabaseReferenceFiles($file_to_check_database)
    {
        $component_dirs = array_merge(
            glob(base_dir().'/modules/*', GLOB_ONLYDIR) ?: [],
            glob(base_dir().'/plugins/*', GLOB_ONLYDIR) ?: []
        );
        $files = [];

        foreach ($component_dirs as $component_dir) {
            $preferred_file = $component_dir.'/'.$file_to_check_database;
            $fallback_file = $component_dir.'/mysql.json';

            if (file_exists($preferred_file)) {
                $files[] = $preferred_file;
            } elseif ($file_to_check_database !== 'mysql.json' && file_exists($fallback_file)) {
                $files[] = $fallback_file;
            }
        }

        return $files;
    }
}

if (!function_exists('aggiornamentiMatchModuleNameByFolder')) {
    function aggiornamentiMatchModuleNameByFolder($folder_name, $modules_json_data)
    {
        $module_name = $folder_name;

        if (!empty($modules_json_data) && is_array($modules_json_data)) {
            foreach ($modules_json_data as $name => $module_info) {
                if (stripos(strtolower((string) $folder_name), strtolower((string) $name)) !== false) {
                    $module_name = $name;
                    break;
                }

                if (stripos(strtolower((string) $folder_name), strtolower(str_replace(' ', '', (string) $name))) !== false) {
                    $module_name = $name;
                    break;
                }
            }
        }

        return $module_name;
    }
}

if (!function_exists('aggiornamentiMergeDatabaseReferenceData')) {
    function aggiornamentiMergeDatabaseReferenceData($data, $file_to_check_database)
    {
        $data = is_array($data) ? $data : [];
        $premium_fields = [];
        $premium_foreign_keys = [];
        $modules_json_data = aggiornamentiReadJsonFile(base_dir().'/modules.json');

        foreach (aggiornamentiGetDatabaseReferenceFiles($file_to_check_database) as $database_json_file) {
            $database_data = aggiornamentiReadJsonFile($database_json_file);

            if (empty($database_data)) {
                continue;
            }

            $component_dir = dirname((string) $database_json_file);
            $folder_name = basename($component_dir);
            $component_type = aggiornamentiGetComponentTypeFromPath($database_json_file);

            if ($component_type === 'plugin') {
                $plugin_modules = aggiornamentiNormalizeModuleDefinitions(
                    aggiornamentiReadJsonFile($component_dir.'/module.json'),
                    $folder_name
                );
                $module_name = count($plugin_modules) === 1
                    ? array_key_first($plugin_modules)
                    : aggiornamentiGetComponentDisplayName($component_dir);
            } else {
                $module_name = aggiornamentiMatchModuleNameByFolder($folder_name, $modules_json_data);

                if ($module_name === $folder_name) {
                    $module_name = aggiornamentiGetComponentDisplayName($component_dir);
                }
            }

            foreach ($database_data as $table => $table_data) {
                if (!isset($data[$table])) {
                    $data[$table] = $table_data;
                } else {
                    foreach ($table_data as $field_name => $field_data) {
                        if ($field_name === 'foreign_keys' && is_array($field_data)) {
                            if (!isset($data[$table]['foreign_keys'])) {
                                $data[$table]['foreign_keys'] = [];
                            }

                            foreach ($field_data as $fk_name => $fk_data) {
                                if (!isset($data[$table]['foreign_keys'][$fk_name])) {
                                    $data[$table]['foreign_keys'][$fk_name] = $fk_data;
                                }
                            }
                        } elseif (is_array($field_data)) {
                            if (!isset($data[$table][$field_name])) {
                                $data[$table][$field_name] = $field_data;
                            } else {
                                $data[$table][$field_name] = array_merge($data[$table][$field_name], $field_data);
                            }
                        } else {
                            $data[$table][$field_name] = $field_data;
                        }
                    }
                }
            }

            foreach ($database_data as $table => $table_data) {
                if (!is_array($table_data)) {
                    continue;
                }

                foreach ($table_data as $field_name => $field_data) {
                    if (!isset($premium_fields[$table])) {
                        $premium_fields[$table] = [];
                    }

                    $premium_fields[$table][$field_name] = [
                        'name' => $module_name,
                        'type' => $component_type,
                    ];
                }
            }

            // Traccia le chiavi esterne premium separatamente
            foreach ($database_data as $table => $table_data) {
                if (!is_array($table_data)) {
                    continue;
                }

                if (isset($table_data['foreign_keys']) && is_array($table_data['foreign_keys'])) {
                    if (!isset($premium_foreign_keys[$table])) {
                        $premium_foreign_keys[$table] = [];
                    }

                    foreach ($table_data['foreign_keys'] as $fk_name => $fk_data) {
                        $premium_foreign_keys[$table][$fk_name] = [
                            'name' => $module_name,
                            'type' => $component_type,
                        ];
                    }
                }
            }
        }

        return [
            'data' => $data,
            'premium_fields' => $premium_fields,
            'premium_foreign_keys' => $premium_foreign_keys,
        ];
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

        $views_data = aggiornamentiReadJsonFile($views_json_path);

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

        // Carica e accoda le viste dai file views.json presenti nelle sottocartelle di moduli e plugin
        $views_json_files = aggiornamentiGetReferenceJsonFiles('views.json');
        $premium_modules_data = aggiornamentiGetPremiumModuleDefinitions();

        // Traccia i moduli che provengono dai file modules.json/module.json nelle sottocartelle (moduli premium)
        $premium_modules = array_fill_keys(array_keys($premium_modules_data['definitions']), true);

        // Traccia tutte le viste definite nei file views.json delle sottocartelle (per mostrarle sempre)
        $premium_views = [];

        // Traccia il nome leggibile del componente premium principale per ogni sottocartella
        $premium_module_main_name = $premium_modules_data['component_names'];

        // Traccia il tipo di componente principale per ogni sottocartella
        $premium_component_types = $premium_modules_data['component_types'];

        // Traccia la sottocartella di appartenenza per ogni modulo premium
        $premium_module_folder = $premium_modules_data['folders'];

        if (!empty($views_json_files)) {
            foreach ($views_json_files as $views_json_file) {
                $views_data = aggiornamentiReadJsonFile($views_json_file);

                if (!empty($views_data) && is_array($views_data)) {
                    // Estrai il nome della sottocartella (es. "vendita_banco" da "/path/to/modules/vendita_banco/views.json")
                    $folder_name = basename(dirname((string) $views_json_file));
                    $component_name = $premium_module_main_name[$folder_name] ?? aggiornamentiGetComponentDisplayName(dirname((string) $views_json_file));
                    $component_type = $premium_component_types[$folder_name] ?? aggiornamentiGetComponentTypeFromPath($views_json_file);

                    // Accoda le viste del componente a quelle principali
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

                            // Traccia questa vista come vista premium con il nome leggibile del componente
                            if (!isset($premium_views[$module_key])) {
                                $premium_views[$module_key] = [];
                            }
                            $premium_views[$module_key][$view_key] = [
                                'name' => $component_name,
                                'type' => $component_type,
                            ];

                            // Se view_query è un array (struttura estesa), estrai il campo 'id'
                            if (is_array($view_query) && isset($view_query['id'])) {
                                $standard_views[$module_key][$view_key] = $view_query['id'];
                            } elseif (is_string($view_query)) {
                                // Se è una stringa (struttura semplice), usala direttamente
                                $standard_views[$module_key][$view_key] = $view_query;
                            }
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
                    'expected_query' => '',
                ];
                continue;
            }

            $current_query = trim((string) $view['query']);
            $current_query = preg_replace('/<br\s*\/?>/i', '', $current_query);
            $current_query = preg_replace('/\s+/', ' ', (string) $current_query);
            $current_query = str_replace(['"', "'"], "'", $current_query);
            $current_query = html_entity_decode($current_query, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $current_query = trim($current_query);

            if (empty($module_name)) {
                continue;
            }

            // Verifica se questa vista è definita in un file views.json di una sottocartella (vista premium)
            if (isset($premium_views[$module_name]) && isset($premium_views[$module_name][$view_name])) {
                // Questa è una vista premium, mostrala sempre con l'etichetta blu usando il nome leggibile del modulo principale
                $is_custom = true;
                $premium_view_info = $premium_views[$module_name][$view_name];
                $module_display_name = $premium_view_info['name'] ?? $view['module_display_name'];
                $reason_prefix = (($premium_view_info['type'] ?? 'module') === 'plugin') ? 'Vista plugin ' : 'Vista modulo ';
                $reason = $reason_prefix.$module_display_name;
                $expected_query = trim((string) $standard_views[$module_name][$view_name]);

                $expected_query = preg_replace('/<br\s*\/?>/i', '', $expected_query);
                $expected_query = preg_replace('/\s+/', ' ', (string) $expected_query);
                $expected_query = str_replace(['"', "'"], "'", $expected_query);
                $expected_query = html_entity_decode($expected_query, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $expected_query = trim($expected_query);
            } elseif (!isset($standard_views[$module_name])) {
                $is_custom = true;
                // Verifica se il modulo proviene da un file modules.json in una sottocartella (modulo premium)
                if (isset($premium_modules[$module_name])) {
                    // Usa il nome leggibile del modulo principale se disponibile
                    $folder_name = $premium_module_folder[$module_name] ?? '';
                    $module_display_name = $premium_module_main_name[$folder_name] ?? $module_name;
                    $reason_prefix = (($premium_component_types[$folder_name] ?? 'module') === 'plugin') ? 'Vista plugin ' : 'Vista modulo ';
                    $reason = $reason_prefix.$module_display_name;
                } else {
                    $reason = 'Modulo non previsto';
                }
            } elseif (!isset($standard_views[$module_name][$view_name])) {
                $is_custom = true;
                $reason = 'Vista aggiuntiva';
            } else {
                $expected_query = trim((string) $standard_views[$module_name][$view_name]);

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
                    'debug_module_name' => $module_name, // Per debug
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
                if (empty(trim((string) $expected_query))) {
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
                        'expected_query' => $expected_query,
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
        $text = preg_replace('/<br\s*\/?>/i', '', (string) $text);

        // Normalizza spazi multipli
        $text = preg_replace('/\s+/', ' ', (string) $text);

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

        $modules_data = aggiornamentiReadJsonFile($modules_json_path);

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

        // Traccia tutti i moduli definiti nei file modules.json/module.json delle sottocartelle (per mostrarli sempre)
        $premium_modules_all = [];
        $premium_modules_data = aggiornamentiGetPremiumModuleDefinitions();

        foreach ($premium_modules_data['definitions'] as $module_name => $data) {
            $premium_modules_all[$module_name] = [
                'options' => $data['options'] ?? '',
                'options2' => $data['options2'] ?? '',
            ];
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
            $expected_options = '';
            $expected_options2 = '';

            // Normalizza le options del modulo corrente
            $current_options = normalizeModuleOptions($module['options']);
            $current_options2 = normalizeModuleOptions($module['options2']);

            if (empty($module_name)) {
                continue;
            }

            // Verifica se questo modulo è definito in un file modules.json di una sottocartella (modulo premium)
            if (isset($premium_modules_all[$module_name])) {
                // Questo è un modulo premium, mostralo sempre con l'etichetta blu
                $is_custom = true;
                $reason = 'Modulo Premium';
                // Per i moduli premium, usa le options definite nel file premium
                $expected_options = $premium_modules_all[$module_name]['options'] ?? '';
                $expected_options2 = $premium_modules_all[$module_name]['options2'] ?? '';
            } elseif (!isset($standard_modules[$module_name])) {
                $is_custom = true;
                $reason = 'Modulo non previsto';
            } else {
                // Normalizza le options standard
                $expected_options = normalizeModuleOptions($standard_modules[$module_name]['options']);
                $expected_options2 = $standard_modules[$module_name]['options2'] ?? '';

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
                    'expected_options' => $expected_options,
                    'expected_options2' => $expected_options2,
                ];
            }
        }

        return $custom_modules;
    }
}
