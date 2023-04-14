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

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    /*
     * Opzioni utilizzate:
     * - idanagrafica
     */
    case 'ordini-cliente':
        if (isset($superselect['idanagrafica'])) {
            $query = 'SELECT or_ordini.id AS id,
                CONCAT("Ordine ", numero_esterno, " del ", DATE_FORMAT(data, "%d/%m/%Y"), " [", (SELECT `descrizione` FROM `or_statiordine` WHERE `or_statiordine`.`id` = `idstatoordine`) , "]") AS descrizione
            FROM or_ordini
                INNER JOIN or_tipiordine ON or_ordini.idtipoordine = or_tipiordine.id
                INNER JOIN an_anagrafiche ON or_ordini.idanagrafica = an_anagrafiche.idanagrafica
            |where|
            ORDER BY or_ordini.id';

            foreach ($elements as $element) {
                $filter[] = 'or_ordini.id='.prepare($element);
            }

            $where[] = 'or_tipiordine.dir='.prepare('entrata');
            if (empty($elements)) {
                $where[] = 'an_anagrafiche.idanagrafica='.prepare($superselect['idanagrafica']);

                $stato = !empty($superselect['stato']) ? $superselect['stato'] : 'is_fatturabile';
                $where[] = 'idstatoordine IN (SELECT `id` FROM `or_statiordine` WHERE '.$stato.' = 1)';
            }
        }

        break;

    case 'sedi-partenza':
        $gestisciMagazzini = $dbo->fetchOne('SELECT * FROM zz_settings WHERE nome = "Gestisci soglia minima per magazzino"');

        if ($gestisciMagazzini['valore'] == '1') {
            $query = '(
                    SELECT "0" AS id,
                    IF(
                        indirizzo != "",
                        CONCAT_WS(" - ", "Sede legale", CONCAT(citta, " (", indirizzo, ")")),
                        CONCAT_WS(" - ", "Sede legale", citta)
                    ) AS descrizione
                    FROM an_anagrafiche WHERE idanagrafica = "1"
                ) UNION (
                    SELECT id,
                    IF(
                        indirizzo != "",
                        CONCAT_WS(" - ", nomesede, CONCAT(citta, " (", indirizzo, ")")),
                        CONCAT_WS(" - ", nomesede, citta )
                    ) AS descrizione
                    FROM an_sedi WHERE idanagrafica="1"
                )';
            } else {
                $query = '(
                    SELECT "0" AS id,
                    IF(
                        indirizzo != "",
                        CONCAT_WS(" - ", "Sede legale", CONCAT(citta, " (", indirizzo, ")")),
                        CONCAT_WS(" - ", "Sede legale", citta)
                    ) AS descrizione
                    FROM an_anagrafiche WHERE idanagrafica = "1"
                )';
            }

        break;
}
