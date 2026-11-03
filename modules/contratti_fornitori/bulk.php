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
$operations = [];

function contrattiFornitoriBulkErrors(array $errors): string
{
    $total = count($errors);
    $visible = array_slice($errors, 0, 5);
    $message = implode(' | ', $visible);

    if ($total > count($visible)) {
        $message .= ' | '.tr('altri _NUM_ errori', [
            '_NUM_' => $total - count($visible),
        ]);
    }

    return $message;
}

$selectedCount = count($id_records ?? []);

if (!empty(post('op')) && $selectedCount > ContrattoFornitoreService::MAX_BULK_RECORDS) {
    flash()->error(tr('È possibile elaborare al massimo _MAX_ contratti per volta.', [
        '_MAX_' => ContrattoFornitoreService::MAX_BULK_RECORDS,
    ]));

    return [];
}

switch (post('op')) {
    case 'change_status':
        $updated = 0;
        $errors = [];

        foreach ($id_records as $id) {
            try {
                $service->changeState((int) $id, (int) post('id_stato'), !empty(post('force_state_transition')));
                ++$updated;
            } catch (Throwable $e) {
                $errors[] = '#'.(int) $id.': '.$e->getMessage();
            }
        }

        if ($updated > 0) {
            flash()->info(tr('Stato aggiornato per _NUM_ contratti fornitori.', ['_NUM_' => $updated]));
        }

        if (!empty($errors)) {
            flash()->warning(tr('Alcuni contratti non sono stati aggiornati: _ERRORS_', [
                '_ERRORS_' => contrattiFornitoriBulkErrors($errors),
            ]));
        }
        break;

    case 'copy_bulk':
        $copied = 0;
        $errors = [];

        foreach ($id_records as $id) {
            try {
                $service->duplicate((int) $id);
                ++$copied;
            } catch (Throwable $e) {
                $errors[] = '#'.(int) $id.': '.$e->getMessage();
            }
        }

        if ($copied > 0) {
            flash()->info(tr('_NUM_ contratti fornitori duplicati.', ['_NUM_' => $copied]));
        }

        if (!empty($errors)) {
            flash()->warning(tr('Alcuni contratti non sono stati duplicati: _ERRORS_', [
                '_ERRORS_' => contrattiFornitoriBulkErrors($errors),
            ]));
        }
        break;

    case 'renew_bulk':
        $renewed = 0;
        $errors = [];

        foreach ($id_records as $id) {
            try {
                $service->renew((int) $id);
                ++$renewed;
            } catch (Throwable $e) {
                $errors[] = '#'.(int) $id.': '.$e->getMessage();
            }
        }

        if ($renewed > 0) {
            flash()->info(tr('_NUM_ contratti fornitori rinnovati.', ['_NUM_' => $renewed]));
        }

        if (!empty($errors)) {
            flash()->warning(tr('Alcuni contratti non sono stati rinnovati: _ERRORS_', [
                '_ERRORS_' => contrattiFornitoriBulkErrors($errors),
            ]));
        }
        break;

    case 'delete_drafts_bulk':
        $deleted = 0;
        $errors = [];

        foreach ($id_records as $id) {
            try {
                $service->deleteDraft((int) $id);
                ++$deleted;
            } catch (Throwable $e) {
                $errors[] = '#'.(int) $id.': '.$e->getMessage();
            }
        }

        if ($deleted > 0) {
            flash()->info(tr('_NUM_ bozze eliminate.', ['_NUM_' => $deleted]));
        }

        if (!empty($errors)) {
            flash()->warning(tr('Alcune bozze non sono state eliminate: _ERRORS_', [
                '_ERRORS_' => contrattiFornitoriBulkErrors($errors),
            ]));
        }
        break;
}

$operations['change_status'] = [
    'text' => '<span><i class="fa fa-refresh"></i> '.tr('Cambia stato').'</span>',
    'data' => [
        'title' => tr('Aggiornare lo stato dei contratti selezionati?'),
        'msg' => '<div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i> '.tr('Alcuni passaggi di stato possono riaprire o modificare contratti già attivi o conclusi. Verificare attentamente i record selezionati.').'</div><br>{[ "type": "select", "label": "'.tr('Stato').'", "name": "id_stato", "required": 1, "values": "query=SELECT `id`, `nome` AS descrizione, `colore` AS _bgcolor_ FROM `ac_stati_contratti_fornitori` WHERE `enabled` = 1 AND `nome` != \"In scadenza\" ORDER BY `ordine`, `nome`" ]}<br>{[ "type": "checkbox", "label": "'.tr('Confermo anche eventuali transizioni di stato non standard').'", "name": "force_state_transition", "value": "1" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => false,
    ],
];

$operations['copy_bulk'] = [
    'text' => '<span><i class="fa fa-copy"></i> '.tr('Duplica contratti').'</span>',
    'data' => [
        'title' => tr('Duplicare i contratti selezionati?'),
        'msg' => tr('Ogni copia sarà creata in stato Bozza con una nuova numerazione. Gli allegati non saranno copiati.'),
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => false,
    ],
];

$operations['renew_bulk'] = [
    'text' => '<span><i class="fa fa-repeat"></i> '.tr('Rinnova contratti').'</span>',
    'data' => [
        'title' => tr('Rinnovare i contratti selezionati?'),
        'msg' => tr('Saranno rinnovati soltanto i contratti Attivi, non già rinnovati e con una data di scadenza. Gli allegati non saranno copiati.'),
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => false,
    ],
];

$operations['delete_drafts_bulk'] = [
    'text' => '<span class="text-danger"><i class="fa fa-trash"></i> '.tr('Elimina bozze').'</span>',
    'data' => [
        'title' => tr('Eliminare le bozze selezionate?'),
        'msg' => tr('Saranno eliminati soltanto i contratti in stato Bozza senza allegati.'),
        'button' => tr('Elimina'),
        'class' => 'btn btn-lg btn-danger',
        'blank' => false,
    ],
];

return $operations;
