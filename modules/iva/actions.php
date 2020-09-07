<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'update':
        $descrizione = post('descrizione');
        $codice = post('codice');

        $esente = post('esente');
        $percentuale = empty($esente) ? post('percentuale') : 0;

        if ($dbo->fetchNum('SELECT * FROM `co_iva` WHERE (`descrizione` = '.prepare($descrizione).' AND `codice` = '.prepare($codice).') AND `id` != '.prepare($id_record)) == 0) {
            $codice_natura = post('codice_natura_fe') ?: null;
            $esigibilita = post('esigibilita');

            $dbo->update('co_iva', [
                'descrizione' => $descrizione,
                'esente' => $esente,
                'percentuale' => $percentuale,
                'indetraibile' => post('indetraibile'),
                'dicitura' => post('dicitura'),
                'codice' => $codice,
                'codice_natura_fe' => $codice_natura,
                'esigibilita' => $esigibilita,
            ], ['id' => $id_record]);

            // Messaggio di avvertenza
            if ($codice_natura == 'N6' && $esigibilita == 'S') {
                flash()->warning(tr('Combinazione di natura IVA N6 ed esigibilità non compatibile'));
            }

            flash()->info(tr('Salvataggio completato!'));
        } else {
            flash()->error(tr("E' già presente una tipologia di _TYPE_ con lo stesso codice e descrizione", [
                '_TYPE_' => 'IVA',
            ]));
        }
        break;

    case 'add':
        $descrizione = post('descrizione');
        $codice = post('codice');
        $esente = post('esente');
        $percentuale = empty($esente) ? post('percentuale') : 0;
        $codice_natura = post('codice_natura_fe') ?: null;
        if ($dbo->fetchNum('SELECT * FROM `co_iva` WHERE `descrizione` = '.prepare($descrizione).' AND `codice` = '.prepare($codice)) == 0) {
            $dbo->insert('co_iva', [
                'descrizione' => $descrizione,
                'esente' => $esente,
                'codice' => $codice,
                'codice_natura_fe' => $codice_natura,
                'percentuale' => $percentuale,
                'indetraibile' => post('indetraibile'),
            ]);
            $id_record = $dbo->lastInsertedID();

            flash()->info(tr('Aggiunta nuova tipologia di _TYPE_', [
                '_TYPE_' => 'IVA',
            ]));
        } else {
            flash()->error(tr("E' già presente una tipologia di _TYPE_ con lo stesso codice e descrizione", [
                '_TYPE_' => 'IVA',
            ]));
        }

        break;

    case 'delete':
        if (isset($id_record)) {
            $dbo->query('UPDATE `co_iva` SET deleted_at = NOW() WHERE `id`='.prepare($id_record));

            flash()->info(tr('Tipologia di _TYPE_ eliminata con successo', [
                '_TYPE_' => 'IVA',
            ]));
        }

        break;
}
