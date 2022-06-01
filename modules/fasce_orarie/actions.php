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

switch (post('op')) {
    case 'update':
        $nome = post('nome');
        $giorni = (array) post('giorni');
        $ora_inizio = post('ora_inizio');
        $ora_fine = post('ora_fine');
        $include_bank_holidays = post('include_bank_holidays');
        $is_predefined = post('is_predefined');

        if ($dbo->fetchNum('SELECT * FROM `in_fasceorarie` WHERE `nome`='.prepare($nome).' AND `id`!='.prepare($id_record)) == 0) {

            if (!empty($is_predefined)) {
                $dbo->query('UPDATE in_fasceorarie SET is_predefined = 0');
            }
            
            $dbo->update('in_fasceorarie', [
                'nome' => $nome,
                'giorni' => $giorni ? implode(',' , $giorni) : null,
                'ora_inizio' => $ora_inizio,
                'ora_fine' => $ora_fine,
                'include_bank_holidays' => $include_bank_holidays,
                'is_predefined' => $is_predefined,
            ], [
                'id' => $id_record,
            ]);

            flash()->info(tr('Salvataggio completato.'));
        } else {
            flash()->error(tr("E' già presente una _TYPE_ con lo stesso nome", [
                '_TYPE_' => 'fascia oraria',
            ]));
        }

        break;

    case 'add':
        $nome = post('nome');
        $ora_inizio = post('ora_inizio');
        $ora_fine = post('ora_fine');

        if ($dbo->fetchNum('SELECT * FROM `in_fasceorarie` WHERE `nome`='.prepare($nome)) == 0) {
           
            $dbo->insert('in_fasceorarie', [
                'nome' => $nome,
                'ora_inizio' => $ora_inizio,
                'ora_fine' => $ora_fine,
            ]);

            $id_record = $dbo->lastInsertedID();

            $tipi_intervento = $dbo->select('in_tipiintervento', '*');
            foreach ($tipi_intervento as $tipo_intervento) {
                $dbo->insert('in_fasceorarie_tipiintervento', [
                    'idfasciaoraria' => $id_record,
                    'idtipointervento' => $tipo_intervento['idtipointervento'],
                    'costo_orario' => $tipo_intervento['costo_orario'],
                    'costo_km' => $tipo_intervento['costo_km'],
                    'costo_diritto_chiamata' => $tipo_intervento['costo_diritto_chiamata'],
                    'costo_orario_tecnico' => $tipo_intervento['costo_orario_tecnico'],
                    'costo_km_tecnico' => $tipo_intervento['costo_km_tecnico'],
                    'costo_diritto_chiamata_tecnico' => $tipo_intervento['costo_km_tecnico'],
                ]);
            }

            if (isAjaxRequest()) {
                echo json_encode(['id' => $id_record, 'text' => $nome]);
            }

            flash()->info(tr('Aggiunta nuova _TYPE_', [
                '_TYPE_' => 'fascia oraria',
            ]));
        } else {
            flash()->error(tr("E' già presente una _TYPE_ con lo stesso nome", [
                '_TYPE_' => 'fascia oraria',
            ]));
        }

        break;

    case 'delete':
        $dbo->update('in_fasceorarie', [
            'deleted_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id_record, 'can_delete' => 1]);

        $dbo->delete('in_fasceorarie_tipiintervento', ['idfasciaoraria' => $id_record]);

        flash()->info(tr('_TYPE_ eliminata con successo.', [
            '_TYPE_' => 'Fascia oraria',
        ]));

        break;
}
