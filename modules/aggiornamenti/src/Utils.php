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
 * Classe di utilità per il modulo aggiornamenti.
 */
class Utils
{
    /**
     * Determina il colore della card in base ai conteggi di errori.
     *
     * @param int $danger_count  Numero di errori danger
     * @param int $warning_count Numero di errori warning
     * @param int $info_count    Numero di errori info
     *
     * @return array Array con chiavi: color, icon, header_class, title_class
     */
    public static function determineCardColor($danger_count = 0, $warning_count = 0, $info_count = 0)
    {
        if ($danger_count > 0) {
            return [
                'color' => 'danger',
                'icon' => 'fa-exclamation-triangle',
                'header_class' => 'requirements-card-header-danger',
                'title_class' => 'requirements-card-title-danger',
                'card_class' => 'card-danger',
            ];
        } elseif ($warning_count > 0) {
            return [
                'color' => 'warning',
                'icon' => 'fa-exclamation-triangle',
                'header_class' => 'requirements-card-header-warning',
                'title_class' => 'requirements-card-title-warning',
                'card_class' => 'card-warning',
            ];
        } elseif ($info_count > 0) {
            return [
                'color' => 'info',
                'icon' => 'fa-info-circle',
                'header_class' => 'requirements-card-header-info',
                'title_class' => 'requirements-card-title-info',
                'card_class' => 'card-info',
            ];
        }

        return [
            'color' => 'success',
            'icon' => 'fa-check-circle',
            'header_class' => 'requirements-card-header-success',
            'title_class' => 'requirements-card-title-success',
            'card_class' => 'card-success',
        ];
    }

    /**
     * Genera HTML per le badge di errore.
     *
     * @param int $danger_count  Numero di errori danger
     * @param int $warning_count Numero di errori warning
     * @param int $info_count    Numero di errori info
     *
     * @return string HTML delle badge
     */
    public static function generateBadgeHtml($danger_count = 0, $warning_count = 0, $info_count = 0)
    {
        $html = '';

        if ($danger_count > 0) {
            $html .= '<span class="badge badge-danger ml-2">'.$danger_count.'</span>';
        }
        if ($warning_count > 0) {
            $html .= '<span class="badge badge-warning ml-2">'.$warning_count.'</span>';
        }
        if ($info_count > 0) {
            $html .= '<span class="badge badge-info ml-2">'.$info_count.'</span>';
        }

        return $html;
    }

    /**
     * Determina il colore del bordo in base ai conteggi.
     *
     * @param int $danger_count  Numero di errori danger
     * @param int $warning_count Numero di errori warning
     *
     * @return string Colore hex del bordo
     */
    public static function determineBorderColor($danger_count = 0, $warning_count = 0)
    {
        if ($danger_count > 0) {
            return '#dc3545'; // danger
        } elseif ($warning_count > 0) {
            return '#ffc107'; // warning
        }

        return '#17a2b8'; // info
    }

    /**
     * Conta gli errori per tipo da un array di risultati.
     *
     * @param array $errors       Array di errori
     * @param array $foreign_keys Array di chiavi esterne
     *
     * @return array Array con chiavi: danger, warning, info
     */
    public static function countErrorsByType($errors, $foreign_keys = [])
    {
        $danger_count = 0;
        $warning_count = 0;
        $info_count = 0;

        foreach ($errors as $name => $diff) {
            if ($name === 'foreign_keys') {
                continue;
            }
            if (array_key_exists('key', $diff)) {
                if ($diff['key']['expected'] == '') {
                    ++$info_count;
                } else {
                    ++$danger_count;
                }
            } elseif (array_key_exists('current', $diff) && is_null($diff['current'])) {
                ++$danger_count;
            } else {
                ++$warning_count;
            }
        }

        foreach ($foreign_keys as $name => $diff) {
            if (is_array($diff) && isset($diff['expected'])) {
                ++$danger_count;
            } elseif (is_array($diff) && isset($diff['current'])) {
                ++$info_count;
            } else {
                ++$warning_count;
            }
        }

        return [
            'danger' => $danger_count,
            'warning' => $warning_count,
            'info' => $info_count,
        ];
    }
}
