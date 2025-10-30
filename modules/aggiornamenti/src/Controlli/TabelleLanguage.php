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

namespace Modules\Aggiornamenti\Controlli;

use Models\Locale;

class TabelleLanguage extends Controllo
{
    public function getName()
    {
        return tr('Integrità tabelle multilingua');
    }

    public function getType($record)
    {
        return 'warning';
    }

    public function getOptions($record)
    {
        return [
            [
                'name' => tr('Correggi'),
                'icon' => 'fa fa-check',
                'color' => 'success',
                'params' => [],
            ],
        ];
    }

    /**
     * Indica se questo controllo supporta azioni globali
     */
    public function hasGlobalActions()
    {
        return true;
    }

    /**
     * Restituisce le azioni globali disponibili per questo controllo
     */
    public function getGlobalActions()
    {
        return [
            [
                'name' => tr('Correggi tutti'),
                'icon' => 'fa fa-check-circle',
                'color' => 'success',
                'params' => [],
            ],
        ];
    }

    public function check()
    {
        $database = database();

        // Ottieni tutte le lingue disponibili
        $languages = Locale::all();
        $total_languages = count($languages);

        if ($total_languages == 0) {
            return; // Salta se non ci sono lingue configurate
        }

        // Ottieni tutte le tabelle del database
        $tables = $database->fetchArray('SHOW TABLES');
        $table_column = 'Tables_in_' . $database->getDatabaseName();

        $main_tables = [];
        $lang_tables = [];

        // Separa le tabelle principali da quelle _lang
        foreach ($tables as $table) {
            $table_name = $table[$table_column];
            if (substr($table_name, -5) === '_lang') {
                $lang_tables[] = $table_name;
            } else {
                $main_tables[] = $table_name;
            }
        }

        // Raggruppa i risultati per tabella
        $results_by_table = [];

        // Per ogni tabella _lang, trova la corrispondente tabella principale
        foreach ($lang_tables as $lang_table) {
            $main_table = str_replace('_lang', '', $lang_table);

            // Verifica se la tabella principale esiste
            if (!in_array($main_table, $main_tables)) {
                continue;
            }

            // Verifica se la tabella principale ha un campo 'id'
            $main_columns = $database->fetchArray("SHOW COLUMNS FROM `{$main_table}`");
            $has_id_field = false;
            foreach ($main_columns as $column) {
                if ($column['Field'] === 'id') {
                    $has_id_field = true;
                    break;
                }
            }

            if (!$has_id_field) {
                continue;
            }

            // Verifica se la tabella _lang ha i campi necessari
            $lang_columns = $database->fetchArray("SHOW COLUMNS FROM `{$lang_table}`");
            $has_id_record = false;
            $has_id_lang = false;
            $other_fields = [];

            foreach ($lang_columns as $column) {
                $field_name = $column['Field'];
                if ($field_name === 'id_record') {
                    $has_id_record = true;
                } elseif ($field_name === 'id_lang') {
                    $has_id_lang = true;
                } elseif (!in_array($field_name, ['id', 'id_record', 'id_lang'])) {
                    $other_fields[] = $field_name;
                }
            }

            if (!$has_id_record || !$has_id_lang) {
                continue;
            }

            // Determina il campo da usare per il nome del record
            $name_field = $this->getNameField($main_table, $main_columns);

            // Trova i record che non hanno traduzioni per tutte le lingue
            $name_select = $name_field ? "mt.`{$name_field}` as record_name," : "CONCAT('ID: ', mt.id) as record_name,";

            $missing_records = $database->fetchArray("
                SELECT
                    mt.id,
                    {$name_select}
                    '{$main_table}' as main_table,
                    '{$lang_table}' as lang_table,
                    COUNT(DISTINCT lt.id_lang) as lingue_presenti,
                    {$total_languages} as lingue_totali,
                    GROUP_CONCAT(DISTINCT lt.id_lang ORDER BY lt.id_lang) as id_lingue_presenti
                FROM `{$main_table}` mt
                LEFT JOIN `{$lang_table}` lt ON mt.id = lt.id_record
                GROUP BY mt.id
                HAVING COUNT(DISTINCT lt.id_lang) < {$total_languages}
            ");

            if (!empty($missing_records)) {
                $results_by_table[$main_table] = [
                    'main_table' => $main_table,
                    'lang_table' => $lang_table,
                    'total_missing' => count($missing_records),
                    'records' => []
                ];

                foreach ($missing_records as $record) {
                    $lingue_mancanti = [];
                    $lingue_presenti = !empty($record['id_lingue_presenti']) ? explode(',', $record['id_lingue_presenti']) : [];

                    foreach ($languages as $language) {
                        if (!in_array($language->id, $lingue_presenti)) {
                            $lingue_mancanti[] = $language->name;
                        }
                    }

                    $results_by_table[$main_table]['records'][] = [
                        'id' => $record['id'],
                        'record_name' => $record['record_name'] ?: 'ID: ' . $record['id'],
                        'lingue_presenti' => $record['lingue_presenti'],
                        'lingue_totali' => $record['lingue_totali'],
                        'lingue_mancanti' => $lingue_mancanti,
                    ];
                }
            }
        }

        // Aggiungi i risultati raggruppati
        foreach ($results_by_table as $table_data) {
            // Crea un record di riepilogo per la tabella
            $record_ids = array_column($table_data['records'], 'id');
            $record_names = array_column($table_data['records'], 'record_name');

            $this->addResult([
                'id' => 'table_' . $table_data['main_table'],
                'main_table' => $table_data['main_table'],
                'lang_table' => $table_data['lang_table'],
                'total_missing' => $table_data['total_missing'],
                'record_ids' => $record_ids,
                'nome' => tr('Tabella: _TABLE_', ['_TABLE_' => $table_data['main_table']]),
                'descrizione' => tr('_COUNT_ record con traduzioni mancanti: _NAMES_', [
                    '_COUNT_' => $table_data['total_missing'],
                    '_NAMES_' => implode(', ', array_slice($record_names, 0, 5)) . (count($record_names) > 5 ? '...' : '')
                ]),
            ]);
        }
    }

    /**
     * Determina il campo da usare per il nome del record
     */
    private function getNameField($table_name, $columns)
    {
        // Campi comuni per i nomi
        $name_fields = ['name', 'nome', 'title', 'descrizione', 'ragione_sociale', 'codice'];

        $available_fields = array_column($columns, 'Field');

        foreach ($name_fields as $field) {
            if (in_array($field, $available_fields)) {
                return $field;
            }
        }

        return null;
    }

    public function execute($record, $params = [])
    {
        $database = database();
        $main_table = $record['main_table'];
        $lang_table = $record['lang_table'];

        // Se è un record raggruppato per tabella, correggi tutti i record della tabella
        if (isset($record['record_ids']) && is_array($record['record_ids'])) {
            $record_ids = $record['record_ids'];
        } else {
            // Singolo record (per compatibilità)
            $record_ids = [$record['id']];
        }

        // Ottieni tutte le lingue disponibili
        $languages = Locale::all();

        // Ottieni i campi della tabella _lang (esclusi id, id_record, id_lang)
        $lang_columns = $database->fetchArray("SHOW COLUMNS FROM `{$lang_table}`");
        $fields_to_populate = [];

        foreach ($lang_columns as $column) {
            $field_name = $column['Field'];
            if (!in_array($field_name, ['id', 'id_record', 'id_lang'])) {
                $fields_to_populate[] = $field_name;
            }
        }

        $corrected_count = 0;

        // Per ogni record ID da correggere
        foreach ($record_ids as $record_id) {
            // Per ogni lingua, inserisci il record se non esiste
            foreach ($languages as $language) {
                $existing = $database->fetchOne("
                    SELECT id FROM `{$lang_table}`
                    WHERE id_record = " . prepare($record_id) . "
                    AND id_lang = " . prepare($language->id)
                );

                if (!$existing) {
                    // Prepara i dati per l'inserimento
                    $insert_data = [
                        'id_record' => $record_id,
                        'id_lang' => $language->id
                    ];

                    // Cerca di ottenere valori di default dalla tabella principale
                    $main_record = $database->fetchOne("SELECT * FROM `{$main_table}` WHERE id = " . prepare($record_id));

                    if ($main_record) {
                        foreach ($fields_to_populate as $field) {
                            // Cerca campi corrispondenti nella tabella principale
                            $default_value = '';

                            // Mappature comuni per i campi
                            $field_mappings = [
                                'name' => ['nome', 'name', 'descrizione', 'title'],
                                'title' => ['title', 'nome', 'name', 'descrizione'],
                                'description' => ['descrizione', 'description', 'nome', 'name']
                            ];

                            if (isset($field_mappings[$field])) {
                                foreach ($field_mappings[$field] as $possible_field) {
                                    if (isset($main_record[$possible_field]) && !empty($main_record[$possible_field])) {
                                        $default_value = $main_record[$possible_field];
                                        break;
                                    }
                                }
                            } elseif (isset($main_record[$field])) {
                                $default_value = $main_record[$field];
                            }

                            $insert_data[$field] = $default_value;
                        }
                    }

                    // Inserisci il record
                    $database->table($lang_table)->insert($insert_data);
                    $corrected_count++;
                }
            }
        }

        return $corrected_count > 0;
    }

    /**
     * Esegue la correzione globale per tutti i record trovati
     */
    public function solveGlobal($params = [])
    {
        $results = [];
        foreach ($this->results as $record) {
            $results[$record['id']] = $this->execute($record, $params);
        }

        return $results;
    }
}
