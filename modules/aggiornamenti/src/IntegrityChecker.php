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
}
