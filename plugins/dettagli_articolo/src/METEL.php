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

namespace Plugins\DettagliArticolo;

/**
 * Formato: https://www.metel.it/wp-content/uploads/2020/04/536_L001_1r18_listino_021.pdf.
 *
 * @since 2.4.18
 */
class METEL
{
    const HEADER = [
        1 => 'Identificazione tracciato',
        21 => 'Sigla Azienda',
        24 => 'Partita IVA',
        35 => 'Numero listino prezzi',
        41 => 'Decorrenza listino prezzi',
        49 => 'Data ultima variazione/immissione',
        57 => 'Descrizione listino prezzi',
        87 => 'Filler (spazi)',
        126 => 'Versione tracciato listino prezzi',
        129 => 'Data decorrenza listino grossista',
        137 => 'Isopartita',
        153 => 'Filler (spazi)',
    ];

    const ROW = [
        1 => 'Sigla Marchio',
        4 => 'Codice Prodotto Azienda',
        20 => 'Codice EAN',
        33 => 'Descrizione prodotto',
        76 => 'Quantità cartone',
        81 => 'Quantità multipla ordinazione',
        86 => 'Quantità minima ordinazione',
        91 => 'Quantità massima ordinazione',
        97 => 'Lead Time',
        98 => 'Prezzo al rivenditore',
        109 => 'Prezzo al Pubblico',
        120 => 'Moltiplicatore prezzo',
        126 => 'Codice Valuta',
        129 => 'Unità di misura',
        132 => 'Prodotto Composto',
        133 => 'Stato del prodotto',
        134 => 'Data ultima variazione',
        142 => 'Famiglia di sconto',
        160 => 'Famiglia statistica',
        178 => 'Codice Electrocod',
        188 => 'Codice Etim',
        198 => 'Codice Barcode',
        233 => 'Qualificatore Codice Barcode',
    ];

    public function parse($string, $fields)
    {
        $fields_number = count($fields);
        $keys = array_keys($fields);

        $results = [];
        for ($i = 0; $i < $fields_number; ++$i) {
            $key = $keys[$i];
            $start = $key - 1;

            if ($fields_number - 1 == $i) {
                $end = strlen($string);
            } else {
                $end = $keys[$i + 1] - 1;
            }

            $length = $end - $start;

            $piece = substr($string, $start, $length);
            $results[$fields[$key]] = trim($piece);
        }

        return $results;
    }

    public function parseHeader($content)
    {
        return $this->parse($content, static::HEADER);
    }

    public function parseRow($content)
    {
        return $this->parse($content, static::ROW);
    }
}
