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

        if ($dbo->fetchNum('SELECT * FROM `in_fasceorarie` WHERE `nome`='.prepare($nome)) == 0) {
           
            $dbo->insert('in_fasceorarie', [
                'nome' => $nome,
            ]);

            $id_record = $dbo->lastInsertedID();

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
        $tipi_interventi = $dbo->fetchNum('SELECT idtipointervento FROM in_fasceorarie_tipiintervento WHERE idfasciaoraria='.prepare($id_record));

        if (isset($id_record) && empty($tipi_interventi)) {
            
            $dbo->delete('in_fasceorarie', [
                'id' => $id_record,
                'can_delete' => 1,
            ]);

            flash()->info(tr('_TYPE_ eliminata con successo.', [
                '_TYPE_' => 'Fascia oraria',
            ]));

        } else {

            flash()->error(tr('Sono presenti dei tipi interventi collegate a questa fascia oraria.'));
            
            # soft delete
            /*$dbo->update('in_fasceorarie', [
                'deleted_at' => date('Y-m-d H:i:s'),
            ], ['id' => $id_record, 'can_delete' => 1]);*/
        }

        break;
}
