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

use Plugins\PianificazioneFatturazione\Pianificazione;

include_once __DIR__.'/../../core.php';

use Modules\Contratti\Stato;

$action = post('action');
$ret = '';
switch ($action) {
    case 'update_table':
        $month = post('currentMonth');
        $year = post('currentYear');
        $pianificazioni = Pianificazione::doesntHave('fattura')
            ->whereHas('contratto', function ($q) {
                $q->whereHas('stato', function ($q) {
                    $stato_concluso = (new Stato())->getByField('title', 'Concluso', Models\Locale::getPredefined()->id);
                    $q
                        ->where('is_fatturabile', 1)
                        ->where('id', '!=', $stato_concluso);
                });
            })
            ->whereYear('co_fatturazione_contratti.data_scadenza', $year)
            ->whereMonth('co_fatturazione_contratti.data_scadenza', $month);

        $pianificazioni = $pianificazioni->get();

        $ret = [];
        foreach ($pianificazioni as $pianificazione) {
            $contratto = $pianificazione->contratto;
            $anagrafica = $contratto->anagrafica;
            $numero_pianificazioni = $contratto->pianificazioni()->count();

            $ret[] = [
                'idPianificazione' => $pianificazione->id,
                'idContratto' => $pianificazione->idcontratto,
                'dataScadenza' => dateFormat($pianificazione->data_scadenza),
                'contratto' => reference($contratto),
                'ragioneSociale' => Modules::link('Anagrafiche', $anagrafica->id, nl2br($anagrafica->ragione_sociale)),
                'totale' => moneyFormat($pianificazione->totale),
                'importo' => tr('Rata _IND_/_NUM_ (totale: _TOT_)', [
                    '_IND_' => numberFormat($pianificazione->getNumeroPianificazione(), 0),
                    '_NUM_' => numberFormat($numero_pianificazioni, 0),
                    '_TOT_' => moneyFormat($contratto->totale),
                ]),
            ];
        }

        break;

    case 'update_month':
        $year = post('currentYear');

        $pianificazioni = Pianificazione::doesntHave('fattura')
            ->whereHas('contratto', function ($q) {
                $q->whereHas('stato', function ($q) {
                    $stato_concluso = (new Stato())->getByField('title', 'Concluso', Models\Locale::getPredefined()->id);
                    $q
                        ->where('is_fatturabile', 1)
                        ->where('id', '!=', $stato_concluso);
                });
            })
            ->whereYear('co_fatturazione_contratti.data_scadenza', $year)
            ->get();

        $raggruppamenti = $pianificazioni->groupBy(fn ($item) => ucfirst($item->data_scadenza->format('m')));

        $ret = [];
        foreach ($raggruppamenti as $i => $item) {
            $ret[intval($i)] = count($item);
        }

        break;
}

echo json_encode($ret);
