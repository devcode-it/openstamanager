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

    protected $max_rows = 27;
    protected $max_rows_first_page = 39;
    protected $max_additional = 0;

    public function __construct(protected $column_number, protected $char_number = 70)
    {
    }

    public function setRows($rows, $additional = null, $first_page = null)
    {
        $this->max_rows = $rows;
        $this->max_additional = $additional ?? $this->max_rows;
        $this->max_rows_first_page = $first_page ?? $this->max_rows_first_page;
    }

    public function count($text, $small = null)
    {
        $count = 0; 
        $textLines = explode("\n", (string)$text); 

        foreach ($textLines as $line) {
            $count += ceil(strlen($line) / $this->char_number); 
        }

        if ($count > 1) {
            $count /= 1.25;
        }

        if ($small) {
            $count /= 1.538461538;
        } 
        
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
            $rows = ceil($this->space);
        }

        $number = $this->max_rows - $rows;

        return $number;
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
