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
require_once __DIR__.'/src/ContrattoFornitoreService.php';

use Modules\ContrattiFornitori\ContrattoFornitoreService;

$service = new ContrattoFornitoreService($dbo, (int) $id_module);

try {
    switch (post('op')) {
        case 'add':
            $id_record = $service->create([
                'id_segment' => post('id_segment'),
                'nome' => post('nome'),
                'id_fornitore' => post('id_fornitore'),
                'id_categoria' => post('id_categoria'),
                'data_inizio' => post('data_inizio'),
                'validita' => post('validita'),
                'tipo_validita' => post('tipo_validita'),
            ]);

            flash()->info(tr('Contratto fornitore aggiunto.'));
            break;

        case 'update':
            $service->update((int) $id_record, [
                'numero' => post('numero'),
                'nome' => post('nome'),
                'id_fornitore' => post('id_fornitore'),
                'id_referente' => post('id_referente'),
                'idagente' => post('idagente'),
                'id_stato' => post('id_stato'),
                'id_categoria' => post('id_categoria'),
                'numero_fornitore' => post('numero_fornitore'),
                'data_stipula' => post('data_stipula'),
                'data_inizio' => post('data_inizio'),
                'validita' => post('validita'),
                'tipo_validita' => post('tipo_validita'),
                'data_scadenza' => post('data_scadenza'),
                'giorni_preavviso' => post('giorni_preavviso'),
                'rinnovo_automatico' => post('rinnovo_automatico'),
                'mesi_rinnovo' => post('mesi_rinnovo'),
                'condizioni_rinnovo' => post('condizioni_rinnovo'),
                'importo' => post('importo'),
                'periodicita' => post('periodicita'),
                'note_economiche' => post('note_economiche'),
                'note' => post('note'),
            ], !empty(post('force_state_transition')));

            flash()->info(tr('Contratto fornitore aggiornato.'));
            break;

        case 'copy':
            $id_record = $service->duplicate((int) $id_record);
            flash()->info(tr('Contratto duplicato correttamente.'));
            break;

        case 'renew':
            $id_record = $service->renew((int) $id_record);
            flash()->info(tr('Contratto rinnovato correttamente.'));
            break;

        case 'delete':
            $service->deleteDraft((int) $id_record);
            flash()->info(tr('Contratto fornitore eliminato.'));
            break;
    }
} catch (Throwable $e) {
    flash()->error($e->getMessage());
}
