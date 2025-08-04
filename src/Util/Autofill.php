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
    // Costanti per i fattori di scala ottimizzati
    private const MULTILINE_FACTOR = 0.8;
    private const SMALL_TEXT_FACTOR = 0.65;
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
        $text = (string) $text;

        // Usa mb_strlen per supporto multibyte corretto
        if (empty($text)) {
            $this->set(0);

            return;
        }

        $textLines = explode("\n", $text);

        foreach ($textLines as $line) {
            // Usa mb_strlen per caratteri multibyte
            $line_length = mb_strlen(trim($line), 'UTF-8');
            if ($line_length > 0) {
                $count += ceil($line_length / $this->char_number);
            }
        }

        // Applica fattori di scala ottimizzati
        if ($count > 1) {
            $count *= self::MULTILINE_FACTOR;
        }

        if ($small) {
            $count *= self::SMALL_TEXT_FACTOR;
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

    /**
     * Reset completo dello stato dell'oggetto.
     * Utile per riutilizzare la stessa istanza per documenti diversi.
     */
    public function reset()
    {
        $this->space = 0;
        $this->current = 0;
    }

    public function getAdditionalNumber()
    {
        $space = $this->space;
        $number = 0;

        // Algoritmo per il calcolo delle pagine
        if ($space <= $this->max_rows) {
            // Prima pagina - calcolo diretto
            $number = max(0, $this->max_rows - ceil($space));
        } elseif ($space <= $this->max_rows_first_page) {
            // Seconda pagina - calcolo diretto
            $number = max(0, $this->max_rows - ceil($space - $this->max_rows_first_page));
        } else {
            // Pagine successive - calcolo
            $remaining_space = $space - $this->max_rows_first_page;
            $additional_pages = ceil($remaining_space / $this->max_rows);
            $rows_on_last_page = $remaining_space - (($additional_pages - 1) * $this->max_rows);
            $number = max(0, $this->max_rows - ceil($rows_on_last_page));
        }

        return $number;
    }

    public function generate()
    {
        $this->next();

        $number = $this->getAdditionalNumber();

        // Se non ci sono righe da generare, ritorna stringa vuota
        if ($number <= 0) {
            return '';
        }

        // Genera template per una singola riga
        $single_row_template = "\n                    <tr>";
        for ($c = 0; $c < $this->column_number; ++$c) {
            $single_row_template .= "\n                        <td>&nbsp;</td>";
        }
        $single_row_template .= "\n                    </tr>";

        // Usa str_repeat per generazione
        return str_repeat($single_row_template, $number);
    }

    /**
     * Restituisce lo spazio attualmente utilizzato.
     *
     * @return float
     */
    public function getSpace()
    {
        return $this->space;
    }
}
