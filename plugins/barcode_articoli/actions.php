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

include_once __DIR__.'/../../core.php';

use Modules\Articoli\Articolo;

$operazione = filter('op');

switch ($operazione) {
    case 'addbarcode':
        if (!empty(post('barcode'))) {
            // Controllo aggiuntivo per verificare l'unicità del barcode prima dell'inserimento
            // anche se la validazione dovrebbe già averlo controllato
            $barcode_value = post('barcode');

            // Verifica che il barcode non sia già presente nella tabella mg_articoli
            $esistente_articoli = Articolo::where('barcode', $barcode_value)->count() > 0;

            // Verifica che il barcode non sia già presente nella tabella mg_articoli_barcode
            $esistente_barcode = $dbo->table('mg_articoli_barcode')
                ->where('barcode', $barcode_value)
                ->count() > 0;

            // Verifica che il barcode non coincida con un codice articolo esistente
            $coincide_codice = Articolo::where([
                ['codice', $barcode_value],
                ['barcode', '=', ''],
            ])->count() > 0;

            // Se il barcode è unico, procede con l'inserimento
            if (!$esistente_articoli && !$esistente_barcode && !$coincide_codice) {
                $dbo->insert('mg_articoli_barcode', [
                    'idarticolo' => $id_parent,
                    'barcode' => $barcode_value,
                ]);
                $id_record = $dbo->lastInsertedID();

                flash()->info(tr('Aggiunto un nuovo barcode!'));
            } else {
                flash()->error(tr('Il barcode è già utilizzato in un altro articolo o nei suoi barcode aggiuntivi'));
            }
        } else {
            flash()->warning(tr('Errore durante aggiunta del barcode'));
        }

        break;

    case 'updatebarcode':
        // Controllo aggiuntivo per verificare l'unicità del barcode prima dell'aggiornamento
        // anche se la validazione dovrebbe già averlo controllato
        $barcode_value = post('barcode');

        // Verifica che il barcode non sia già presente nella tabella mg_articoli
        $esistente_articoli = Articolo::where('barcode', $barcode_value)->count() > 0;

        // Verifica che il barcode non sia già presente nella tabella mg_articoli_barcode
        // escludendo il record corrente che stiamo modificando
        $esistente_barcode = $dbo->table('mg_articoli_barcode')
            ->where('barcode', $barcode_value)
            ->where('id', '<>', $id_record)
            ->count() > 0;

        // Verifica che il barcode non coincida con un codice articolo esistente
        $coincide_codice = Articolo::where([
            ['codice', $barcode_value],
            ['barcode', '=', ''],
        ])->count() > 0;

        // Se il barcode è unico, procede con l'aggiornamento
        if (!$esistente_articoli && !$esistente_barcode && !$coincide_codice) {
            $dbo->update('mg_articoli_barcode', [
                'barcode' => $barcode_value,
            ], ['id' => $id_record]);

            flash()->info(tr('Salvataggio completato!'));
        } else {
            flash()->error(tr('Il barcode è già utilizzato in un altro articolo o nei suoi barcode aggiuntivi'));
        }

        break;

    case 'deletebarcode':
        $id = filter('id');
        $dbo->query('DELETE FROM `mg_articoli_barcode` WHERE `id` = '.prepare($id).'');

        flash()->info(tr('Barcode eliminato!'));

        break;

    case 'manage-btn':
        $btnid = post('btnid');

        if (empty($btnid)) {
            $dbo->insert('mg_btn_articoli', [
                'colore' => post('colore'),
                'descrizione' => post('descrizione_pulsante'),
                'usa_immagine' => post('usa_immagine'),
                'idarticolo' => post('idarticolo'),
            ]);

            $btnid = $dbo->lastInsertedID();
            flash()->info(tr('Pulsante aggiornato!'));
        } else {
            if (empty(post('colore')) && empty(post('descrizione_pulsante')) && empty(post('usa_immagine'))) {
                $dbo->query('DELETE FROM mg_btn_articoli WHERE id='.prepare($btnid));
                flash()->info(tr('Pulsante rimosso!'));
            } else {
                $dbo->update('mg_btn_articoli', [
                    'colore' => post('colore'),
                    'descrizione' => post('descrizione_pulsante'),
                    'usa_immagine' => post('usa_immagine'),
                ], ['id' => $btnid]);
                flash()->info(tr('Pulsante aggiornato!'));
            }
        }

        $dbo->query('DELETE FROM mg_btn_magazzini WHERE btn_id='.prepare($btnid));
        foreach (post('idmagazzini') as $idmagazzino) {
            $dbo->insert('mg_btn_magazzini', [
                'btn_id' => $btnid,
                'idmagazzino' => $idmagazzino,
            ]);
        }

        break;
}
