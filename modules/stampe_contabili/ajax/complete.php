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
    case 'controlla_stampa':
        $date_start = get('date_start');
        $date_end = get('date_end');
        $id_print = get('id_print');
        $id_sezionale = get('id_sezionale');
        $dir = get('dir');

        $where_conditions = [
            'id_print='.prepare($id_print),
            'date_start='.prepare($date_start),
            'date_end='.prepare($date_end),
        ];

        if (!empty($id_sezionale)) {
            $where_conditions[] = 'id_sezionale='.prepare($id_sezionale);
        } else {
            $where_conditions[] = '(id_sezionale IS NULL OR id_sezionale = "")';
        }

        if (!empty($dir)) {
            $where_conditions[] = 'dir='.prepare($dir);
        } else {
            $where_conditions[] = '(dir IS NULL OR dir = "")';
        }

        $where_clause = implode(' AND ', $where_conditions);
        $stampa_definitiva = $dbo->fetchOne('SELECT id FROM co_stampe_contabili WHERE '.$where_clause)['id'];

        echo json_encode($stampa_definitiva ?: 0);

        break;

    case 'controlla_sbilanci_libro_giornale':
        $date_start = get('date_start');
        $date_end = get('date_end');

        // Includo il file modutil per utilizzare la funzione verificaSbilanciLibroGiornale
        include_once __DIR__.'/../modutil.php';

        $risultato = verificaSbilanciLibroGiornale($date_start, $date_end);

        echo json_encode($risultato);

        break;

    case 'controlla_sequenza_stampa':
        $date_start = get('date_start');
        $id_print = get('id_print');
        $id_sezionale = get('id_sezionale');
        $dir = get('dir');

        $risultato = [
            'sequenziale' => true,
            'messaggio' => '',
        ];

        if (!empty($date_start)) {
            $where_conditions = [
                'id_print='.prepare($id_print),
            ];

            if (!empty($dir)) {
                $where_conditions[] = 'dir='.prepare($dir);
            } else {
                $where_conditions[] = '(dir IS NULL OR dir = "")';
            }

            if (!empty($id_sezionale)) {
                $where_conditions[] = 'id_sezionale='.prepare($id_sezionale);
            }

            $where_clause = implode(' AND ', $where_conditions);

            // Ultima stampa definitiva effettuata per la stampa e la direzione selezionata
            $ultima_stampa = $dbo->fetchOne('SELECT date_start, date_end FROM co_stampe_contabili WHERE '.$where_clause.' ORDER BY date_end DESC, date_start DESC LIMIT 1');

            // Se non è presente alcuna stampa definitiva precedente, la stampa è da considerarsi sequenziale
            if (!empty($ultima_stampa)) {
                $ultima_fine = new DateTime($ultima_stampa['date_end']);
                $trimestrale = setting('Liquidazione iva') == 'Trimestrale';

                // Calcolo del periodo atteso successivo all'ultima stampa, in base alla periodicità IVA (mensile o trimestrale)
                if ($trimestrale) {
                    $mese_inizio_trimestre = (int) (floor(((int) $ultima_fine->format('n') - 1) / 3) * 3 + 1);
                    $attesa_inizio = new DateTime($ultima_fine->format('Y').'-'.str_pad((string) $mese_inizio_trimestre, 2, '0', STR_PAD_LEFT).'-01');
                    $attesa_inizio->modify('+3 months');
                    $attesa_fine = (clone $attesa_inizio)->modify('+3 months -1 day');
                } else {
                    $attesa_inizio = new DateTime($ultima_fine->format('Y-m-01'));
                    $attesa_inizio->modify('+1 month');
                    $attesa_fine = (clone $attesa_inizio)->modify('last day of this month');
                }

                $selezionata_inizio = new DateTime($date_start);
                $sequenziale = $selezionata_inizio->format('Y-m-d') === $attesa_inizio->format('Y-m-d');

                if (!$sequenziale) {
                    $periodicita = $trimestrale ? tr('trimestrale') : tr('mensile');
                    $risultato['sequenziale'] = false;
                    $risultato['messaggio'] = tr('L\'ultima stampa definitiva risulta dal _ULTIMA_INIZIO_ al _ULTIMA_FINE_: con periodicità _PERIODICITA_ il periodo atteso risulta dal _ATTESA_INIZIO_ al _ATTESA_FINE_.', [
                        '_ULTIMA_INIZIO_' => Translator::dateToLocale($ultima_stampa['date_start']),
                        '_ULTIMA_FINE_' => Translator::dateToLocale($ultima_stampa['date_end']),
                        '_PERIODICITA_' => $periodicita,
                        '_ATTESA_INIZIO_' => Translator::dateToLocale($attesa_inizio->format('Y-m-d')),
                        '_ATTESA_FINE_' => Translator::dateToLocale($attesa_fine->format('Y-m-d')),
                    ]);
                }
            }
        }

        echo json_encode($risultato);

        break;
}
