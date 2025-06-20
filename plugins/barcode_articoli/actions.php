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

$operazione = filter('op');

switch ($operazione) {
    case 'addbarcode':
         if (!empty(post('barcode'))) {
             $dbo->insert('mg_articoli_barcode', [
                'idarticolo' => $id_parent,
                'barcode' => post('barcode'),
            ]);
            $id_record = $dbo->lastInsertedID();
            
            flash()->info(tr('Aggiunto un nuovo barcode!'));
         } else {
             flash()->warning(tr('Errore durante aggiunta del barcode'));
         }

        break;

    case 'updatebarcode':
        $dbo->update('mg_articoli_barcode', [
            'barcode' => post('barcode'),
        ], ['id' => $id_record]);

        flash()->info(tr('Salvataggio completato!'));

        break;

    case 'deletebarcode':
        $id = filter('id');
        $dbo->query('DELETE FROM `mg_articoli_barcode` WHERE `id` = '.prepare($id).'');

        flash()->info(tr('Barcode eliminato!'));

        break;

    case 'manage-btn':

        $btnid = post('btnid');

        if( empty($btnid) ){
            $dbo->insert('mg_btn_articoli', [
                'colore' => post('colore'),
                'descrizione' => post('descrizione_pulsante'),
                'usa_immagine' => post('usa_immagine'),
                'idarticolo' => post('idarticolo'),
            ]);

            $btnid = $dbo->lastInsertedID();
            flash()->info(tr('Pulsante aggiornato!'));
        }else{
            if( empty(post('colore')) && empty(post('descrizione_pulsante')) && empty(post('usa_immagine')) ){
                $dbo->query("DELETE FROM mg_btn_articoli WHERE id=".prepare($btnid));
                flash()->info(tr('Pulsante rimosso!'));
            }else{
                $dbo->update('mg_btn_articoli', [
                    'colore' => post('colore'),
                    'descrizione' => post('descrizione_pulsante'),
                    'usa_immagine' => post('usa_immagine'),
                ], ['id' => $btnid]);
                flash()->info(tr('Pulsante aggiornato!'));
            }
        }

        $dbo->query("DELETE FROM mg_btn_magazzini WHERE btn_id=".prepare($btnid));
        foreach(post('idmagazzini') as $idmagazzino){
            $dbo->insert('mg_btn_magazzini', [
                'btn_id' => $btnid,
                'idmagazzino' => $idmagazzino,
            ]);
        }

        break;
}
