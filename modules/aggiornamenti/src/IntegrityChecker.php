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

namespace Modules\Aggiornamenti;

/**
 * Classe per il controllo di integrità dei dati.
 */
class IntegrityChecker
{
    /**
     * Calcola le differenze tra i dati attesi e quelli attuali (ricorsivo).
     *
     * @param array $expected Dati attesi
     * @param array $current  Dati attuali
     *
     * @return array Array delle differenze
     */
    public static function diff($expected, $current)
    {
        $difference = [];

        foreach ($expected as $key => $value) {
            if (array_key_exists($key, $current) && is_array($value)) {
                if (!is_array($current[$key])) {
                    $difference[$key] = $value;
                } else {
                    $new_diff = self::diff($value, $current[$key]);
                    if (!empty($new_diff)) {
                        $difference[$key] = $new_diff;
                    }
                }
            } elseif (!array_key_exists($key, $current) || ($current[$key] != $value && !empty($value))) {
                $difference[$key] = [
                    'current' => $current[$key] ?? null,
                    'expected' => $value,
                ];
            }
        }

        return $difference;
    }

    /**
     * Calcola le differenze per le impostazioni.
     *
     * @param array $expected Impostazioni attese
     * @param array $current  Impostazioni attuali
     *
     * @return array Array delle differenze
     */
    public static function settingsDiff($expected, $current)
    {
        $difference = [];

        foreach ($expected as $key => $value) {
            if (array_key_exists($key, $current)) {
                if (!is_array($current[$key])) {
                    if ($current[$key] !== $value) {
                        $difference[$key] = [
                            'current' => $current[$key],
                            'expected' => $value,
                        ];
                    }
                } else {
                    $new_diff = self::diff($value, $current[$key]);
                    if (!empty($new_diff)) {
                        $difference[$key] = $new_diff;
                    }
                }
            } else {
                $difference[$key] = [
                    'current' => null,
                    'expected' => $value,
                ];
            }
        }

        return $difference;
    }

    /**
     * Normalizza le opzioni del modulo per il confronto.
     *
     * @param mixed $options Opzioni da normalizzare
     *
     * @return string Opzioni normalizzate
     */
    public static function normalizeModuleOptions($options)
    {
        if (is_array($options)) {
            $options = json_encode($options);
        }

        return (string) $options;
    }

    /**
     * Genera un identificatore univoco per una chiave esterna basato sul contenuto.
     *
     * @param array $fk_data Dati della chiave esterna
     *
     * @return string Identificatore univoco della chiave esterna
     */
    public static function getForeignKeyHash($fk_data)
    {
        if (!is_array($fk_data) || empty($fk_data)) {
            return '';
        }

        // Normalizza i dati della chiave esterna
        $column = $fk_data['column'] ?? '';
        $referenced_table = $fk_data['referenced_table'] ?? '';
        $referenced_column = $fk_data['referenced_column'] ?? '';
        $delete_rule = $fk_data['delete_rule'] ?? 'RESTRICT';
        $update_rule = $fk_data['update_rule'] ?? 'RESTRICT';

        // Genera un hash basato sul contenuto della chiave esterna
        return md5($column . '|' . $referenced_table . '|' . $referenced_column . '|' . $delete_rule . '|' . $update_rule);
    }

    /**
     * Verifica se una chiave esterna esiste in un array basandosi sul contenuto.
     *
     * @param array $fk_data Dati della chiave esterna da cercare
     * @param array $foreign_keys Array di chiavi esterne in cui cercare
     *
     * @return bool True se la chiave esterna esiste, false altrimenti
     */
    public static function foreignKeyExistsByContent($fk_data, $foreign_keys)
    {
        if (!is_array($fk_data) || empty($fk_data) || !is_array($foreign_keys)) {
            return false;
        }

        $fk_hash = self::getForeignKeyHash($fk_data);

        foreach ($foreign_keys as $fk_name => $fk_info) {
            if (self::getForeignKeyHash($fk_info) === $fk_hash) {
                return true;
            }
        }

        return false;
    }

    /**
     * Filtra le chiavi esterne rimuovendo quelle che esistono per contenuto.
     *
     * @param array $foreign_keys Array di chiavi esterne da filtrare
     * @param array $expected_foreign_keys Array di chiavi esterne attese
     *
     * @return array Array di chiavi esterne filtrate
     */
    public static function filterForeignKeysByContent($foreign_keys, $expected_foreign_keys)
    {
        if (!is_array($foreign_keys) || !is_array($expected_foreign_keys)) {
            return $foreign_keys;
        }

        $filtered = [];
        foreach ($foreign_keys as $fk_name => $fk_info) {
            if (!self::foreignKeyExistsByContent($fk_info, $expected_foreign_keys)) {
                $filtered[$fk_name] = $fk_info;
            }
        }

        return $filtered;
    }
}
