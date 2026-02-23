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

$resource = ($resource ?: $_GET['op']);

switch ($resource) {
    // Elenco e-mail
    case 'get_email':
        $indirizzi_proposti = filter('indirizzi_proposti');
        $where = '';

        // Definizione filtri per tipo anagrafica
        $tipo_anagrafica = match ($indirizzi_proposti) {
            1 => '"Cliente"',
            2 => '"Fornitore"',
            default => null,
        };

        if ($tipo_anagrafica !== null) {
            $where .= 'AND an_tipianagrafiche.name = '.$tipo_anagrafica;
        }

        $results = [];

        // Funzione helper per aggiungere risultati all'array
        $addResults = function ($records) use (&$results) {
            foreach ($records as $record) {
                $results[] = [
                    'value' => $record['email'],
                    'label' => $record['ragione_sociale'].' <'.$record['email'].'>',
                ];
            }
        };

        // Tutte le anagrafiche (query specifica senza join su se stessa)
        $q = "
            SELECT DISTINCT(an_anagrafiche.email) AS email,
                   an_anagrafiche.idanagrafica,
                   an_anagrafiche.ragione_sociale
            FROM an_anagrafiche
            INNER JOIN an_tipianagrafiche_anagrafiche ON an_tipianagrafiche_anagrafiche.idanagrafica = an_anagrafiche.idanagrafica
            INNER JOIN an_tipianagrafiche ON an_tipianagrafiche.id = an_tipianagrafiche_anagrafiche.idtipoanagrafica
            INNER JOIN an_tipianagrafiche_lang ON (an_tipianagrafiche_lang.id_lang = 1 AND an_tipianagrafiche_lang.id_record = an_tipianagrafiche.id)
            WHERE an_anagrafiche.email != '' $where
            ORDER BY ragione_sociale
        ";
        $addResults($dbo->fetchArray($q));

        // Funzione helper per sedi e referenti (tabelle che richiedono join con an_anagrafiche)
        $fetchEmails = function ($table, $email_column, $name_column) use ($dbo, $where) {
            $query = "
                SELECT DISTINCT($table.$email_column) AS email,
                       $table.idanagrafica,
                       $name_column AS ragione_sociale
                FROM $table
                INNER JOIN an_anagrafiche ON an_anagrafiche.idanagrafica = $table.idanagrafica
                INNER JOIN an_tipianagrafiche_anagrafiche ON an_tipianagrafiche_anagrafiche.idanagrafica = an_anagrafiche.idanagrafica
                INNER JOIN an_tipianagrafiche ON an_tipianagrafiche.id = an_tipianagrafiche_anagrafiche.idtipoanagrafica
                INNER JOIN an_tipianagrafiche_lang ON (an_tipianagrafiche_lang.id_lang = 1 AND an_tipianagrafiche_lang.id_record = an_tipianagrafiche.id)
                WHERE $table.$email_column != '' $where
                ORDER BY ragione_sociale
            ";

            return $dbo->fetchArray($query);
        };

        // Tutte le sedi
        $addResults($fetchEmails('an_sedi', 'email', 'nomesede'));

        // Tutti i referenti
        $addResults($fetchEmails('an_referenti', 'email', 'nome'));

        echo json_encode($results);

        break;
}
