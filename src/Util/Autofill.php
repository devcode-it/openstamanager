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
 * Classe dedicata alla gestione delle righe fantasma per il miglioramento grafico delle stampe tabellari.
 *
 * @since 2.3
 */
class Autofill
{
    protected $space = 0;
    protected $current = 0;

    protected $max_rows = 26;
    protected $max_rows_first_page = 38;
    protected $max_additional = 0;

    public function __construct(protected $column_number, protected $char_number = 70)
    {
    }

    public function setRows($rows, $additional = null, $first_page = null)
    {
        $this->max_rows = $rows;

        $this->max_additional = $additional ?: $this->max_rows;
        $this->max_rows_first_page = $first_page ?? $this->max_rows_first_page;
    }

    public function count($text, $small = false)
    {
        $count = ceil(strlen((string) $text) / $this->char_number);

        // Ricerca dei caratteri a capo
        preg_match_all("/(\r\n|\r|\n)/", (string) $text, $matches);
        $count += count($matches[0]);
        $count = ($count == 1 ? $count : $count / 1.538461538);

        $this->set($count);
    }

    public function set($count)
    {
        $this->current += $count;
    }

    public function next()
    {
        $this->space += $this->current;
        $this->current = 0;
    }

    public function getAdditionalNumber()
    {
        if ($this->space <= $this->max_rows) {
            $page = 1;
        } else {
            if ($this->space <= $this->max_rows_first_page) {
                $page = 2;
            } else {
                $page = ceil(1 + (($this->space - $this->max_rows_first_page) / $this->max_rows));
            }
        }

        if ($page > 1) {
            $rows = $this->space - $this->max_rows_first_page * ($page - 1);
        } else {
            $rows = floor($this->space);
        }

        $number = $this->max_additional - $rows;

        return $number > 0 ? $number : 0;
    }

    public function generate()
    {
        $this->next();

        $result = '';

        $number = $this->getAdditionalNumber();

        for ($i = 0; $i < $number; ++$i) {
            $result .= '
                    <tr>';

            for ($c = 0; $c < $this->column_number; ++$c) {
                $result .= '
                        <td>&nbsp;</td>';
            }

            $result .= '
                    </tr>';
        }

        return $result;
    }
}
