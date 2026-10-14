<?php

/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

include_once __DIR__.'/../../core.php';
require_once __DIR__.'/src/ContrattoFornitoreService.php';

use Modules\ContrattiFornitori\ContrattoFornitoreService;

if (!empty($id_record)) {
    $manualWithoutDuration = ($record['tipo_validita'] ?? null) === 'manual' && empty($record['mesi_rinnovo']);
    $isActive = ($record['stato_nome'] ?? null) === ContrattoFornitoreService::STATO_ATTIVO;
    $canRenew = $isActive && !empty($record['data_scadenza']) && empty($record['id_contratto_successivo']) && !$manualWithoutDuration;

    echo '<div class="tip d-inline-block" data-widget="tooltip" title="'.tr('Il rinnovo è disponibile solo per contratti Attivi, con data di scadenza, senza un contratto successivo e, per le scadenze manuali, con una durata di rinnovo.').'">';
    echo '<button type="button" class="btn btn-warning ask '.($canRenew ? '' : 'disabled').'" data-title="'.tr('Rinnovare questo contratto?').'" data-msg="'.tr('Verrà creato un nuovo contratto in stato Bozza, collegato a quello corrente.').'" data-op="renew" data-button="'.tr('Rinnova').'" data-class="btn btn-lg btn-warning" data-backto="record-edit"><i class="fa fa-refresh"></i> '.tr('Rinnova').'...</button>';
    echo '</div> ';

    echo '<button type="button" class="btn btn-primary ask" data-title="'.tr('Duplicare questo contratto?').'" data-msg="'.tr('Verrà creato un nuovo contratto in stato Bozza con un nuovo numero.').'" data-op="copy" data-button="'.tr('Duplica').'" data-class="btn btn-lg btn-primary" data-backto="record-edit"><i class="fa fa-copy"></i> '.tr('Duplica contratto').'</button>';
}
