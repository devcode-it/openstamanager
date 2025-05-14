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
include_once __DIR__.'/../init.php';
use Models\Module;

$id_contratto_precedente = $record['idcontratto_prev'];
$module = Module::find($id_module);

if (!empty($id_contratto_precedente)) {
    echo '
<h4>'.tr('Storico rinnovi').'</h4>
<table class="table table-hover table-sm table-bordered table-striped">
    <thead>
        <tr>
            <th>'.tr('Descrizione').'</th>
            <th width="100">'.tr('Totale').'</th>
            <th width="150">'.tr('Data inizio').'</th>
            <th width="150">'.tr('Data conclusione').'</th>
        </tr>
    </thead>

    <tbody>';
} else {
    echo '
<div class="alert alert-info">
    <i class="fa fa-info-circle"></i> '.tr('Non sono presenti voci da visualizzare nello storico dei rinnovi.').'
</div>';
}

$counter = 0;
while (!empty($id_contratto_precedente) && $counter < 50) {
    ++$counter;
    $rs = $dbo->fetchArray('SELECT nome, numero, data_accettazione, data_conclusione, budget, idcontratto_prev FROM co_contratti WHERE id='.prepare($id_contratto_precedente));

    echo '
        <tr>
            <td>
                '.Modules::link($module->getTranslation('title'), $id_contratto_precedente, tr('Contratto num. _NUM_', [
        '_NUM_' => $rs[0]['numero'],
    ]).'<br><small class="text-muted">'.$rs[0]['nome'].'</small>').'
            </td>
            <td class="text-right">'.moneyFormat($rs[0]['budget']).'</td>
            <td align="center">'.Translator::dateToLocale($rs[0]['data_accettazione']).'</td>
            <td align="center">'.Translator::dateToLocale($rs[0]['data_conclusione']).'</td>
        </tr>';

    $id_contratto_precedente = $rs[0]['idcontratto_prev'];
}

// Chiudo la tabella solo se ci sono record da visualizzare
if (!empty($record['idcontratto_prev'])) {
    echo '
    </tbody>
</table>';
}

echo '
<script type="text/javascript">
    input("rinnovabile").on("change", function() {
        const disabled = parseInt($(this).val()) === 0;

        input("giorni_preavviso_rinnovo").setDisabled(disabled);
        input("ore_preavviso_rinnovo").setDisabled(disabled);
        input("rinnovo_automatico").setDisabled(disabled);
    });
</script>';
